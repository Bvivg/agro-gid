<?php

require './system/db.php';

session_start();

$user_id = $_SESSION['user_id'];

try {
  $stmtCheckJobRequest = $connect->prepare("
    SELECT job_requests.*, users.username, users.email, users.avatar_path
    FROM job_requests
    JOIN users ON job_requests.sender_id = users.id
    WHERE job_requests.receiver_id = :receiverId AND job_requests.status = 0
  ");
  $stmtCheckJobRequest->bindParam(':receiverId', $user_id, PDO::PARAM_INT);
  $stmtCheckJobRequest->execute();

  if ($stmtCheckJobRequest->errorCode() !== '00000') {
    print_r($stmtCheckJobRequest->errorInfo());
  }

  $jobRequests = $stmtCheckJobRequest->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Error: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (isset($_POST["accept"])) {
    $requestId = $_POST["request_id"];
    $updateStatusQuery = "UPDATE job_requests SET status = 1 WHERE id = :requestId";
    $stmtUpdateStatus = $connect->prepare($updateStatusQuery);
    $stmtUpdateStatus->bindParam(':requestId', $requestId, PDO::PARAM_INT);
    $stmtUpdateStatus->execute();
  } elseif (isset($_POST["reject"])) {
    $requestId = $_POST["request_id"];
    $updateStatusQuery = "UPDATE job_requests SET status = 2 WHERE id = :requestId";
    $stmtUpdateStatus = $connect->prepare($updateStatusQuery);
    $stmtUpdateStatus->bindParam(':requestId', $requestId, PDO::PARAM_INT);
    $stmtUpdateStatus->execute();
  }
}
$title = 'Приглашения на работу';
?>

<?php include "incs/header.php"; ?>

<?php include "incs/navbar.php"; ?>

<div>
  <div class="requests">
    <?php foreach ($jobRequests as $request) : ?>
      <div class="request">
        <div class="request-block">
          <?php if ($request['avatar_path'] && file_exists($request['avatar_path'])) : ?>
            <div class="chatsidebar-img">
              <img src="<?php echo  $request['avatar_path']; ?>" alt="">
            </div>
          <?php else : ?>
            <div class="chatsidebar-img">
              <img src="./assets/img/placeholder-image.png" alt="Placeholder Avatar">
            </div>
          <?php endif; ?>
          <p><?php echo $request['username']; ?></p>
          <p><?php echo $request['email']; ?></p>
        </div>
        <form method="post"><input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">

          <button type="submit" name="accept" class=button-modal>Принять</button>
          <a href="chat?id=<?php echo $request['sender_id'] ?>" type="submit" class=button-modal-cancel>Связаться</a>
          <button type="submit" name="reject" class=button-modal-logout>Отклонить</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php include "incs/footer.php"; ?>