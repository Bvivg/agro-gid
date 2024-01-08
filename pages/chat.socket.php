<?php

use Workerman\Worker;
use Workerman\Lib\Timer;

require './vendor/autoload.php';
$connect = require './system/db.php';
$ws_worker = new Worker("websocket://0.0.0.0:2346");

function sendMessage($userId, $receiverId, $messageText)
{
  global $connect;

  $query = "INSERT INTO messages (sender_id, receiver_id, message_text, status, read_status, date, time)
              VALUES (:userId, :receiverId, :messageText, 1, 0, NOW(), NOW())";
  $stmt = $connect->prepare($query);
  $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
  $stmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
  $stmt->bindParam(':messageText', $messageText, PDO::PARAM_STR);
  $stmt->execute();

  $lastMessageId = $connect->lastInsertId();

  foreach ($GLOBALS['ws_worker']->connections as $connection) {
    if (isset($connection->userId) && $connection->userId == $userId && isset($connection->receiverId) && $connection->receiverId == $receiverId) {
      $connection->lastMessageId = $lastMessageId;
      break;
    }
  }
}


function getMessages($userId, $receiverId)
{
  global $connect;

  $query = "SELECT * FROM messages 
              WHERE ((sender_id = :userId AND receiver_id = :receiverId) 
              OR (sender_id = :receiverId AND receiver_id = :userId))
              AND status = 2
              ORDER BY date, time";

  $stmt = $connect->prepare($query);
  $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
  $stmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
  $stmt->execute();

  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getNewMessages($userId, $receiverId, $lastMessageId)
{
  global $connect;

  $query = "SELECT * FROM messages 
              WHERE id > :lastMessageId AND read_status = 0 AND status = 1 
              AND ((sender_id = :userId AND receiver_id = :receiverId) 
              OR (sender_id = :receiverId AND receiver_id = :userId))
              ORDER BY date, time";

  $stmt = $connect->prepare($query);
  $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
  $stmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
  $stmt->bindParam(':lastMessageId', $lastMessageId, PDO::PARAM_INT);
  $stmt->execute();

  $updateStatusQuery = "UPDATE messages SET status = 2 WHERE status = 1 AND ((sender_id = :userId AND receiver_id = :receiverId) OR (sender_id = :receiverId AND receiver_id = :userId))";
  $updateStatusStmt = $connect->prepare($updateStatusQuery);
  $updateStatusStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
  $updateStatusStmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
  $updateStatusStmt->execute();

  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUnreadMessages($userId, $receiverId)
{
  global $connect;

  $query = "SELECT * FROM messages 
              WHERE sender_id = :receiverId AND receiver_id = :userId
              AND read_status = 0 
              ORDER BY date, time";

  $stmt = $connect->prepare($query);
  $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
  $stmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
  $stmt->execute();

  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function markMessageAsRead($userId, $receiverId, $messageId)
{
  global $connect;

  $updateQuery = "UPDATE messages SET read_status = 1 
                    WHERE id = :messageId AND receiver_id = :userId AND sender_id = :receiverId AND read_status = 0";
  $updateStmt = $connect->prepare($updateQuery);
  $updateStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
  $updateStmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
  $updateStmt->bindParam(':messageId', $messageId, PDO::PARAM_INT);
  $updateStmt->execute();
}

$ws_worker->count = 4;


$ws_worker->onConnect = function ($connection) {
  echo "Соединение открыто\n";
};

$ws_worker->onClose = function ($connection) {
  echo "Соединение закрыто\n";
};



$ws_worker->onMessage = function ($connection, $data) {
  global $connect;

  $decodedData = json_decode($data, true);

  if (isset($decodedData['user_id']) && isset($decodedData['receiver_id'])) {
    $userId = $decodedData['user_id'];
    $receiverId = $decodedData['receiver_id'];

    Timer::add(1, function () use ($connection, $userId, $receiverId) {
      try {
        $lastMessageId = $connection->lastMessageId ?? 0;

        $unreadMessages = getUnreadMessages($receiverId, $userId);

        $data = [
          'unread_messages' => $unreadMessages ?? [],
        ];

        $jsonData = json_encode($data);
        $connection->send($jsonData);
      } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
      }
    });

    if (isset($decodedData['message_text'])) {
      sendMessage($userId, $receiverId, $decodedData['message_text']);
      $connection->lastMessageId = $connect->lastInsertId();

      // Add echo statement to display the new message in the console
      echo "New message from User $userId to Receiver $receiverId: {$decodedData['message_text']}\n";
    }

    $lastMessageId = $decodedData['last_message_id'] ?? 0;

    if (isset($decodedData['load_all']) && $decodedData['load_all'] === true) {
      $allMessages = getMessages($userId, $receiverId);
      $responseData = [
        'all_messages' => array_unique($allMessages, SORT_REGULAR),
        'new_messages' => [],
      ];
    } else {
      $newMessages = getNewMessages($userId, $receiverId, $lastMessageId);
      markMessageAsRead($userId, $receiverId, $lastMessageId);
      $responseData = [
        'new_messages' => $newMessages,
      ];
    }

    $responseData['user_id'] = $userId;
    $responseData['receiver_id'] = $receiverId;

    $response = json_encode($responseData);
    $connection->send($response);
  }
};



Worker::runAll();
