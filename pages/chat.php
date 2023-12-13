<?php
session_start();
require './system/db.php';
date_default_timezone_set('Asia/Bishkek');

$receiverId = isset($_GET['id']) ? $_GET['id'] : null;

if ($receiverId !== null) {
  $user_id = $_SESSION['user_id'];
  $query = "SELECT * FROM users WHERE id = :receiverId";
  $stmt = $connect->prepare($query);
  $stmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
  $stmt->execute();
  $userHeaders = $stmt->fetch(PDO::FETCH_ASSOC);
  $title = $userHeaders['username'];
} else {
  echo "Parameter 'id' is missing.";
  exit;
}
?>
<?php
include "incs/header.php";
?>

<?php
$hideSideBar = 'style="display: none;"';
include "incs/navbar.php";
?>
<div id="main-content">
  <div class="message-box" id="messageBox"></div>
</div>

<form class="message-form" id="messageForm">
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
  const ws = new WebSocket('ws://localhost:2346');
  const messageBox = document.getElementById('messageBox');
  const messageForm = document.getElementById('messageForm');
  const receiverId = <?php echo $receiverId; ?>;
  const user_id = <?php echo $user_id; ?>;

  ws.addEventListener('open', function(event) {
    ws.send('WebSocket connection opened!');
    const data = {
      receiverId: receiverId,
      userId: userId
    };
    socket.send(JSON.stringify(data));
  });

  ws.addEventListener('message', function(event) {
    try {
      const message = JSON.parse(event.data);
      if (message) {
        const isMyMessage = message.sender_id == <?= $user_id; ?>;
        const messageClass = isMyMessage ? 'my-message' : 'companion-message';

        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${messageClass}`;
        messageDiv.innerHTML = `
            <span class="message-msg">${escapeHTML(message.message_text)}</span>
            <div class="message-info">
              <span>${message.time}</span>
              ${isMyMessage ? `<span>${message.read_status == 1 ? '<i class="fa-solid fa-check-double"></i>' : '<i class="fa-solid fa-check"></i>'}</span>` : ''}
            </div>
          `;

        messageBox.appendChild(messageDiv);
      } else {
        console.error('Invalid message format:', event.data);
      }
    } catch (error) {
      console.error('JSON parsing error:', error);
    }
  });



  messageForm.addEventListener('submit', function(event) {
    event.preventDefault();

    const formData = new FormData(messageForm);
    const data = Object.fromEntries(formData);
    ws.send(JSON.stringify(data));

    messageForm.reset();
  });

  function escapeHTML(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
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
    return day + '.' + month + '.' + year;
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