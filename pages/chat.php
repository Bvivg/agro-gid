<?php
require './system/db.php';
date_default_timezone_set('Asia/Bishkek');

$receiverId = $_GET['id'];
$query = "SELECT * FROM users WHERE id = :receiverId";
$stmt = $connect->prepare($query);
$stmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
$stmt->execute();
$userHeaders = $stmt->fetch(PDO::FETCH_ASSOC);
$title = $userHeaders['username'];
session_start();

if (isset($_GET['id'])) {
  $receiverId = $_GET['id'];
  $user_id = $_SESSION['user_id'];
  $query = "SELECT *, TIME_FORMAT(time, '%H:%i') AS formatted_time FROM messages 
          WHERE (sender_id = :userId AND receiver_id = :receiverId) 
          OR (sender_id = :receiverId AND receiver_id = :userId)
          ORDER BY date, time";

  $stmt = $connect->prepare($query);
  $stmt->bindParam(':userId', $user_id, PDO::PARAM_INT);
  $stmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
  $stmt->execute();
  $chatMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (isset($_POST['send_message'])) {
    $receiverId = $_POST['receiver_id'];
    $user_id = $_SESSION['user_id'];
    $messageText = $_POST['message_text'];
    $query = "INSERT INTO messages (sender_id, receiver_id, message_text, date, time, read_status) 
              VALUES (:userId, :receiverId, :messageText, NOW(), NOW(), 0)";
    $stmt = $connect->prepare($query);
    $stmt->bindParam(':userId', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
    $stmt->bindParam(':messageText', $messageText, PDO::PARAM_STR);

    if ($stmt->execute()) {
      header("Location: chat?id=$receiverId");
      exit();
    } else {
      echo "Error sending message";
    }
  }

  if (isset($_GET['load_messages'])) {
    $result = array('messages' => $chatMessages);
    echo json_encode($result);
    exit();
  }

  $updateReadStatusQuery = "UPDATE messages SET read_status = 1 
                            WHERE receiver_id = :userId AND sender_id = :receiverId AND read_status = 0";
  $updateReadStatusStmt = $connect->prepare($updateReadStatusQuery);
  $updateReadStatusStmt->bindParam(':userId', $user_id, PDO::PARAM_INT);
  $updateReadStatusStmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
  $updateReadStatusStmt->execute();
}
?>
<?php
include "incs/header.php";
?>

<?php
$hideSideBar = 'style="display: none;"';
include "incs/navbar.php";
?>
<div class="user-headers">

</div>
<div id="main-content">
  <div class="message-box">

  </div>
</div>
</div>

<form class="message-form" action="" method="post">
  <div class="message-form-block">
    <input type="hidden" name="receiver_id" value="<?php echo $_GET['id']; ?>">
    <label class="message-form-text" for="message_text">
      <textarea id="message_text" class="" name="message_text" placeholder="Введите сообщение..." required></textarea>
    </label>
    <button class="message-form-button" type="submit" name="send_message">
      <svg>
        <use href="./assets/img/sprite.svg#send"></use>
      </svg>
    </button>
  </div>
</form>
</div>
<script>
  $(document).ready(function() {
    scrollToBottom()
    var messageCache = {};

    function scrollToBottom() {
      var messageBox = $('.message-box');
      messageBox.scrollTop(messageBox.prop('scrollHeight'));
    }
    var messageCache = {};

    function loadMessages() {
      if (messageCache.hasOwnProperty(<?php echo $_GET['id']; ?>)) {
        updateMessages(messageCache[<?php echo $_GET['id']; ?>]);
      } else {
        $.ajax({
          url: 'chat',
          type: 'GET',
          data: {
            load_messages: true,
            id: <?php echo $_GET['id']; ?>
          },
          dataType: 'json',
          success: function(response) {
            messageCache[<?php echo $_GET['id']; ?>] = response.messages;
            updateMessages(response.messages);
          },
          error: function() {
            console.log('Error loading messages');
          }
        });
      }
    }

    function updateMessages(messages) {
      $('.message-box').empty();

      var currentDate = null;
      messages.forEach(function(message) {
        var messageDate = new Date(message.date);
        if (messageDate.toDateString() !== currentDate) {
          $('.message-box').append('<div class="date-separator">' + formatDateSeparator(messageDate) + '</div>');
          currentDate = messageDate.toDateString();
        }

        var messageHTML = '<div class="message ' + (message.sender_id == <?php echo $user_id; ?> ? 'my-message' : 'companion-message') + '">' +
          '<span class="message-msg">' + escapeHTML(message.message_text) + '</span>' +
          '<div class="message-info">' +
          '<span>' + message.formatted_time + '</span>' +
          (message.sender_id == <?php echo $user_id; ?> ? '<span>' + (message.read_status == 1 ? '<i class="fa-solid fa-check-double"></i>' : '<i class="fa-solid fa-check"></i>') + '</span>' : '') +
          '</div>' +
          '</div>';

        $('.message-box').append(messageHTML);
      });
      $('html, body').animate({
        scrollTop: $(document).height()
      }, 'slow');
      scrollToBottom();
    }

    function formatDateSeparator(date) {
      var now = new Date();
      now.setHours(0, 0, 0, 0);
      date.setHours(0, 0, 0, 0);

      var interval = Math.floor((now - date) / (1000 * 60 * 60 * 24));

      if (interval === 0) {
        return "Сегодня";
      } else if (interval === 1) {
        return "Вчера";
      } else if (interval === 2) {
        return "Позавчера";
      } else if (interval > 2 && interval <= 7) {
        var dayOfWeek = translateDayOfWeek(date.getDay());
        return dayOfWeek;
      } else {
        return formatDate(date);
      }
    }

    function formatDate(date) {
      var day = date.getDate();
      var month = date.getMonth() + 1;
      var year = date.getFullYear();
      var hours = date.getHours();
      var minutes = date.getMinutes();

      // Ensure that minutes and hours are two digits
      minutes = minutes < 10 ? '0' + minutes : minutes;
      hours = hours < 10 ? '0' + hours : hours;

      return day + '.' + month + '.' + year + ' ' + hours + ':' + minutes;
    }

    function translateDayOfWeek(day) {
      var days = [
        'В Воскресенье',
        'В Понедельник',
        'Во Вторник',
        'В Среду',
        'В Четверг',
        'В Пятницу',
        'В Субботу'
      ];

      return days[day];
    }

    function escapeHTML(html) {
      var escape = document.createElement('textarea');
      escape.textContent = html;
      return escape.innerHTML;
    }

    function formatDate(date, time) {
      return date + ' ' + time;
    }

    loadMessages();
    setInterval(function() {
      loadMessages();
    }, 1000);
  });
</script>

<?php
include "incs/footer.php";
?>

<?php

function formatDateSeparator($date)
{
  $now = new DateTime();
  $now->setTime(0, 0, 0);
  $date->setTime(0, 0, 0);

  $interval = $now->diff($date);

  if ($interval->days == 0) {
    return "Сегодня";
  } elseif ($interval->days == 1) {
    return "Вчера";
  } elseif ($interval->days == 2) {
    return "Позавчера";
  } elseif ($interval->days > 2 && $interval->days <= 7) {
    $dayOfWeek = translateDayOfWeek($date->format('l'));
    return $dayOfWeek;
  } else {
    return $date->format('d.m.Y');
  }
}
function translateDayOfWeek($day)
{
  $days = [
    'Monday' => 'В Понедельник',
    'Tuesday' => 'Во Вторник',
    'Wednesday' => 'В Среду',
    'Thursday' => 'В Четверг',
    'Friday' => 'В Пятницу',
    'Saturday' => 'В Субботу',
    'Sunday' => 'В Воскресенье',
  ];

  return $days[$day];
}

?>