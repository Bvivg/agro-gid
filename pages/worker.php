<?php
require './system/db.php';

if (isset($_GET['id'])) {
  $userId = $_GET['id'];

  $stmt = $connect->prepare("SELECT * FROM users WHERE id = :userId");
  $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
  $stmt->execute();
  $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
  $stmtFeedback = $connect->prepare("SELECT feedback.*, users.avatar_path AS sender_avatar_path, users.username AS sender_username
                                  FROM feedback 
                                  INNER JOIN users ON feedback.sender_id = users.id 
                                  WHERE feedback.receiver_id = :receiver_id
                                  ORDER BY feedback.sender_id = :current_user_id DESC");
  $stmtFeedback->bindParam(':receiver_id', $userId, PDO::PARAM_INT);
  $stmtFeedback->bindParam(':current_user_id', $user_id, PDO::PARAM_INT);
  $stmtFeedback->execute();
  $feedbackInfo = $stmtFeedback->fetchAll(PDO::FETCH_ASSOC);

  usort($feedbackInfo, function ($a, $b) use ($user_id) {
    if ($a['sender_id'] == $user_id) {
      return -1;
    } elseif ($b['sender_id'] == $user_id) {
      return 1;
    } else {
      return $b['id'] - $a['id'];
    }
  });
} else {
  header("Location: /");
  exit();
}
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: get-started");
  exit();
}
$pageTitleValue = "Рабочий " . $userInfo['username'];
$pageTitle = '<p class="title-block">' . $pageTitleValue . '</p>';
$title = "Рабочий " . $userInfo['username'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION["user_id"])) {
  $senderId = $_SESSION["user_id"];
  $receiverId = $_POST['receiver_id'];
  $feedbackText = $_POST['feedback_text'];
  $rating = $_POST['rating'];
  if (isset($_POST['send'])) {
    $stmt = $connect->prepare("INSERT INTO feedback (sender_id, receiver_id, feedback_text, rating) VALUES (:senderId, :receiverId, :feedbackText, :rating)");
    $stmt->bindParam(':senderId', $senderId, PDO::PARAM_INT);
    $stmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
    $stmt->bindParam(':feedbackText', $feedbackText, PDO::PARAM_STR);
    $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);

    if ($stmt->execute()) {
      header("Location: " . $_SERVER['REQUEST_URI']);
      exit();
    }
  }
  if (isset($_POST['edit-feedback'])) {
    $feedbackId = $_POST['feedback_id'];
    $editRating = $_POST['edit-rating'];
    $editFeedbackText = $_POST['edit-feedback-text'];

    $editStmt = $connect->prepare("UPDATE feedback SET rating = :editRating, feedback_text = :editFeedbackText WHERE id = :feedbackId AND sender_id = :senderId");
    $editStmt->bindParam(':editRating', $editRating, PDO::PARAM_INT);
    $editStmt->bindParam(':editFeedbackText', $editFeedbackText, PDO::PARAM_STR);
    $editStmt->bindParam(':feedbackId', $feedbackId, PDO::PARAM_INT);
    $editStmt->bindParam(':senderId', $senderId, PDO::PARAM_INT);

    if ($editStmt->execute()) {
      header("Location: " . $_SERVER['REQUEST_URI']);
      exit();
    }
  }
  if (isset($_POST['delete-feedback'])) {
    $feedbackId = $_POST['feedback_id'];

    $deleteStmt = $connect->prepare("DELETE FROM feedback WHERE id = :feedbackId AND sender_id = :senderId");
    $deleteStmt->bindParam(':feedbackId', $feedbackId, PDO::PARAM_INT);
    $deleteStmt->bindParam(':senderId', $senderId, PDO::PARAM_INT);

    if ($deleteStmt->execute()) {
      header("Location: " . $_SERVER['REQUEST_URI']);
      exit();
    }
  }
  $senderId = $_SESSION["user_id"];
  $receiverId = $userInfo['id'];
  $hireDate = date("Y-m-d");
  $hireTime = date("H:i:s");
  $status = 0;

  if (!$hasJobRequest) {
    $stmtInsertJobRequest = $connect->prepare("INSERT INTO job_requests (sender_id, receiver_id, hire_date, hire_time, status) VALUES (:senderId, :receiverId, :hireDate, :hireTime, :status)");
    $stmtInsertJobRequest->bindParam(':senderId', $senderId, PDO::PARAM_INT);
    $stmtInsertJobRequest->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
    $stmtInsertJobRequest->bindParam(':hireDate', $hireDate, PDO::PARAM_STR);
    $stmtInsertJobRequest->bindParam(':hireTime', $hireTime, PDO::PARAM_STR);
    $stmtInsertJobRequest->bindParam(':status', $status, PDO::PARAM_INT);

    if ($stmtInsertJobRequest->execute()) {
      header("Location: " . $_SERVER['REQUEST_URI']);
      exit();
    } else {
      echo "Error submitting job request.";
    }
  }
}
$averageRating = 0;
$totalReviews = count($feedbackInfo);

foreach ($feedbackInfo as $feedback) {
  $averageRating += $feedback['rating'];
}

if ($totalReviews > 0) {
  $averageRating /=  $totalReviews;
  $averageRating = round($averageRating, 1);
  $updateRatingStmt = $connect->prepare("UPDATE users SET rating = :rating WHERE id = :userId");
  $updateRatingStmt->bindParam(':rating', $averageRating, PDO::PARAM_STR);
  $updateRatingStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
  $updateRatingStmt->execute();
}

?>



<?php
include "incs/header.php";
?>

<?php
include "incs/navbar.php";
?>
<div class="worker">

  <div class="worker-card">
    <div class="worker-info">
      <?php
      $avatar = $userInfo['avatar_path'];
      if ($avatar && file_exists($avatar)) : ?>
        <div class="worker-info-img">
          <img src="<?php echo $avatar; ?>" alt="">
        </div>
      <?php else : ?>
        <div class="worker-info-img">
          <img src="./assets/img/placeholder-image.png" alt="Placeholder Avatar">
        </div>
      <?php endif; ?>
      <p><?php echo $userInfo['username']; ?></p>
      <p>
        <?php
        if (!empty($userInfo['rating'])) {
          $rating = $userInfo['rating'];
          $fullStars = floor($rating);
          $decimalPart = $rating - $fullStars;
          for ($i = 1; $i <= 5; $i++) {
            if ($i <= $fullStars) {
              echo '<svg><use href="./assets/img/sprite.svg?version=' . time() . '#star"></use></svg>';
            } elseif ($i == $fullStars + 1 && $decimalPart >= 0.25 && $decimalPart < 0.75) {
              echo '<svg><use href="./assets/img/sprite.svg?version=' . time() . '#halfstar"></use></svg>';
            } else {
              echo '<svg><use href="./assets/img/sprite.svg?version=' . time() . '#emptystar"></use></svg>';
            }
          }
        }
        ?>
      </p>
      <?php
      $stmtCheckJobRequest = $connect->prepare("SELECT COUNT(*) FROM job_requests WHERE sender_id = :senderId AND receiver_id = :receiverId");
      $stmtCheckJobRequest->bindParam(':senderId', $user_id, PDO::PARAM_INT);
      $stmtCheckJobRequest->bindParam(':receiverId', $userInfo['id'], PDO::PARAM_INT);
      $stmtCheckJobRequest->execute();
      $hasJobRequest = $stmtCheckJobRequest->fetchColumn();

      ?> <form method="post" class="" style="padding: 10px 2px; display:flex; flex-direction:column; gap: 5px; text-align: center;">
        <?php if ($hasJobRequest) : ?>
          <span class="button-modal">Приглашение оправлено</span>
        <?php else : ?>
          <button type="submit" class="button-modal" name="jobRequests">Нанять</button>
        <?php endif; ?>
        <a class="button-modal-cancel" href="chat?id=<?php echo $userInfo['id']; ?>">Связаться</a>
      </form>

      <p><?php echo $userInfo['description'] ?></p>



    </div>
  </div>
  <form method="post" class="feedback">

    <h3>Оставьте отзыв</h3>
    <?php
    $receiverId = $userInfo['id'];
    $senderId = $_SESSION["user_id"];

    $stmtCheckFeedback = $connect->prepare("SELECT COUNT(*) FROM feedback WHERE sender_id = :senderId AND receiver_id = :receiverId");
    $stmtCheckFeedback->bindParam(':senderId', $senderId, PDO::PARAM_INT);
    $stmtCheckFeedback->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
    $stmtCheckFeedback->execute();
    $hasFeedback = $stmtCheckFeedback->fetchColumn();

    if (!$hasFeedback) {
    ?>
      <input type="hidden" name="receiver_id" value="<?php echo $userInfo['id']; ?>">
      <label for="add-feedback" class="input-form">
        <textarea type="text" name="feedback_text" id="add-feedback" class="input-form-area" required placeholder="Оставьте отзыв..."></textarea>
      </label>
      <div class="">
        <label class="rating" for="add-rait">
          <input type="range" name="rating" id="add-rait" required min="0" max="5" value="0">
          <div class="stars" id="stars"></div>
        </label>
        <button type="submit" class="button-modal" name="send">Отправить отзыв</button>
      </div>
    <?php }
    ?>
  </form>
  <div class="feedbacks">
    <h3>Отзывы от фермеров</h3>
    <?php foreach ($feedbackInfo as $feedback) : ?>
      <div class="feedback-item" id="feedback_<?php echo $feedback['id']; ?>">
        <div class="feedback-avatar">
          <div class="feedback-avatar-img">
            <img src="<?php echo $feedback['sender_avatar_path']; ?>" alt="">
          </div>
          <p><?php echo $feedback['sender_username']; ?></p>
        </div>
        <p class="fixed-stars">
          <?php
          $rating = $feedback['rating'];
          for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
              echo '<svg><use href="./assets/img/sprite.svg#star"></use></svg>';
            } else {
              echo '<svg><use href="./assets/img/sprite.svg#emptystar"></use></svg>';
            }
          }
          ?>
        </p>
        <div class="feedback-button">
          <p><?php echo $feedback['feedback_text']; ?></p>
          <?php if ($feedback['sender_id'] == $user_id) : ?>
            <div class="controllers">
              <button onclick="editFeedback(<?php echo $feedback['id']; ?>)"><i class="fa-solid fa-pen-to-square"></i></button>
              <form method="post" style="display: inline;">
                <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                <button type="submit" name="delete-feedback"><i class="fa-solid fa-trash"></i></button>
              </form>
            </div>
          <?php endif; ?>
        </div>

      </div>
      <form method="post" class="feedback-item" id="editForm_<?php echo $feedback['id']; ?>" style="display: none;">
        <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
        <div class="feedback-avatar">
          <div class="feedback-avatar-img">
            <img src="<?php echo $feedback['sender_avatar_path']; ?>" alt="">
          </div>
          <p><?php echo $feedback['sender_username']; ?></p>
        </div>
        <label class="rating" for="edit-rait<?php echo $feedback['id'] ?>">
          <input type="range" name="edit-rating" id="edit-rait<?php echo $feedback['id'] ?>" required min="0" max="5" value="<?php echo $feedback['rating']; ?>">
          <div class="stars" id="edit-stars<?php echo $feedback['id'] ?>"></div>
        </label>
        <div class="feedback-button">
          <textarea name="edit-feedback-text"><?php echo $feedback['feedback_text']; ?></textarea>
          <div class="controllers">
            <button type="submit" name="edit-feedback"><i class="fa-solid fa-floppy-disk"></i></button>
            <button type="button" onclick="cancelEdit(<?php echo $feedback['id']; ?>)"><i class="fa-solid fa-xmark"></i></button>
          </div>
        </div>
      </form>

    <?php endforeach; ?>

  </div>


</div>
<script>
  function editFeedback(feedbackId) {
    document.getElementById('feedback_' + feedbackId).style.display = 'none';
    document.getElementById('editForm_' + feedbackId).style.display = 'block';
  }

  function cancelEdit(feedbackId) {
    document.getElementById('editForm_' + feedbackId).style.display = 'none';
    document.getElementById('feedback_' + feedbackId).style.display = 'block';
  }

  document.addEventListener('DOMContentLoaded', function() {
    const range1 = document.getElementById('add-rait');
    const starsContainer1 = document.getElementById('stars');
    <?php foreach ($feedbackInfo as $feedback) : ?>
      const range2 = document.getElementById('edit-rait<?php echo $feedback['id'] ?>');
      const starsContainer2 = document.getElementById('edit-stars<?php echo $feedback['id'] ?>');

      function updateStars(range, starsContainer) {
        if (range && starsContainer) {
          const rating = parseFloat(range.value);
          const stars = Math.floor(rating);
          let starsHTML = '';
          for (let i = 1; i <= 5; i++) {
            if (i <= stars) {
              starsHTML += '<svg><use href="./assets/img/sprite.svg#star"></use></svg>';
            } else {
              starsHTML += '<svg><use href="./assets/img/sprite.svg#emptystar"></use></svg>';
            }
          }
          starsContainer.innerHTML = starsHTML;
        }
      }



      if (range2 && starsContainer2) {
        range2.addEventListener('input', function() {
          updateStars(range2, starsContainer2);
        });
        range2.dispatchEvent(new Event('input'));
      }
    <?php endforeach; ?>

    function updateStars(range, starsContainer) {
      if (range && starsContainer) {
        const rating = parseFloat(range.value);
        const stars = Math.floor(rating);
        let starsHTML = '';
        for (let i = 1; i <= 5; i++) {
          if (i <= stars) {
            starsHTML += '<svg><use href="./assets/img/sprite.svg#star"></use></svg>';
          } else {
            starsHTML += '<svg><use href="./assets/img/sprite.svg#emptystar"></use></svg>';
          }
        }
        starsContainer.innerHTML = starsHTML;
      }
    }

    if (range1 && starsContainer1) {
      range1.addEventListener('input', function() {
        updateStars(range1, starsContainer1);
      });
      range1.dispatchEvent(new Event('input'));
    }
  });
</script>
<?php
include "incs/footer.php";
?>