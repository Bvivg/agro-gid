<?php
require './system/db.php';
$title = "Чат";
session_start();
$user_id = $_SESSION['user_id'];

$query = "SELECT DISTINCT u.id AS user_id, u.username, u.avatar_path
FROM users u
JOIN messages m ON u.id = m.sender_id OR u.id = m.receiver_id
WHERE u.id != :user_id AND (:user_id = m.sender_id OR :user_id = m.receiver_id)
";

$stmt = $connect->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "incs/header.php";
include "incs/navbar.php";
?>
<div class="chatsidebar">

  <?php foreach ($users as $user) : ?>

    <a href="chat?id=<?php echo $user['user_id']; ?>" class="chat-user">
      <?php
      $avatar = $user['avatar_path'];
      if ($avatar && file_exists($avatar)) : ?>
        <div class="chatsidebar-img">
          <img src="<?php echo $avatar; ?>" alt="">
        </div>
      <?php else : ?>
        <div class="chatsidebar-img">
          <img src="./assets/img/placeholder-image.png" alt="Placeholder Avatar">
        </div>
      <?php endif; ?>
      <div class="chat-user-info">
        <p> <?php echo $user['username']; ?></p>
      </div>
    </a>
  <?php endforeach ?>

</div>
<?php
include "incs/footer.php";
?>
