<?php
session_start();
use Workerman\Worker;
use Workerman\Lib\Timer;
$GLOBALS['receiverId'] = $receiverId;
$GLOBALS['user_id'] = $user_id;

require './vendor/autoload.php';
$connect = require './system/db.php';

$ws_worker = new Worker("websocket://0.0.0.0:2346");
$ws_worker->count = 4;

$ws_worker->onWorkerStart = function ($ws_worker) use ($connect) {
  $time_interval = 0.1;

  Timer::add($time_interval, function () use ($ws_worker, $connect) {
    $receiverId = $GLOBALS['receiverId'];
    $user_id = $GLOBALS['user_id'];
    echo $receiverId;
    echo $user_id;
    $query = "SELECT *, TIME_FORMAT(time, '%H:%i') AS formatted_time FROM messages 
            WHERE (sender_id = :userId AND receiver_id = :receiverId) 
            OR (sender_id = :receiverId AND receiver_id = :userId)
            ORDER BY date, time";

    $stmt = $connect->prepare($query);
    $stmt->bindParam(':userId', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($ws_worker->connections as $connection) {
      foreach ($rows as $row) {
        $connection->send(json_encode($row));
        $updateReadStatusQuery = "UPDATE messages SET read_status = 1 
                              WHERE receiver_id = :userId AND sender_id = :receiverId AND read_status = 0";
        $updateReadStatusStmt = $connect->prepare($updateReadStatusQuery);
        $updateReadStatusStmt->bindParam(':userId', $user_id, PDO::PARAM_INT);
        $updateReadStatusStmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
      }
    };
  });
};

$ws_worker->onConnect = function () {
  echo "соединение открыто\n";
};

$ws_worker->onMessage = function ($connection, $data) use ($connect) {
  $stmt = $connect->query('SELECT * FROM messages');
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($rows as $row) {
    $connection->send(json_encode($row));
  }
  $data = json_decode($data, true);
  $receiverId = $GLOBALS['receiverId'];
  $user_id = $GLOBALS['user_id'];
  $messageText = isset($data['message_text']) ? trim($data['message_text']) : '';
  $query = "INSERT INTO messages (sender_id, receiver_id, message_text, date, time, read_status) 
                VALUES (:userId, :receiverId, :messageText, NOW(), NOW(), 0)";
  $stmt = $connect->prepare($query);
  $stmt->bindParam(':userId', $user_id, PDO::PARAM_INT);
  $stmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
  $stmt->bindParam(':messageText', $messageText, PDO::PARAM_STR);
};

$ws_worker->onClose = function () {
  echo "соединение закрыто\n";
};

Worker::runAll();
