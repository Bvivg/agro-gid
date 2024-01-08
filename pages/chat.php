<?php
require './system/db.php';
$title = "Чат";
session_start();
$user_id = $_SESSION['user_id'];

$receiverId = isset($_GET['id']) ? $_GET['id'] : null;
$receiverUsername = isset($_GET['username']) ? $_GET['username'] : null;
$receiverUsername = str_replace('@', '', $receiverUsername);

if ($receiverUsername !== null) {
  $receiverUsername = str_replace('_', ' ', $receiverUsername);
  $query = "SELECT * FROM users WHERE username = :receiverUsername";
  $stmt = $connect->prepare($query);
  $stmt->bindParam(':receiverUsername', $receiverUsername, PDO::PARAM_STR);
  $stmt->execute();
  $userHeaders = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($userHeaders) {
    $title = $userHeaders['username'];
    $receiverId = $userHeaders['id'];
  } else {
    echo "Пользователь не найден.";
    exit;
  }
} elseif ($receiverId !== null) {
  $query = "SELECT * FROM users WHERE id = :receiverId";
  $stmt = $connect->prepare($query);
  $stmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
  $stmt->execute();
  $userHeaders = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$userHeaders) {
    echo "Пользователь не найден.";
    exit;
  }
} else {
  echo "Parameter 'id' or 'username' is missing.";
  exit;
}

?>
<?php
include "incs/header.php";
$hideSideBar = 'style="display: none;"';
include "incs/navbar.php";
?>
<div id="main-content">
  <div class="message-box" id="messageBox"></div>
</div>

<form class="message-form" id="messageForm">
  <div class="message-form-block">
    <input type="hidden" name="receiver_id" value="<?php echo $receiverId; ?>">
    <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
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
  const receiverId = <?php echo $receiverId; ?>;
  const user_id = <?php echo $user_id; ?>;
</script>

<?php
include "incs/footer.php";
?>