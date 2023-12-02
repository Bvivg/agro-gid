<?php
require './system/db.php';
session_start();
$user_id = $_SESSION["user_id"];

session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: get-started");
  exit();
}

$errorPassword = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user_id = $_SESSION["user_id"];
  $newUsername = trim($_POST['username']);
  $newEmail = trim($_POST['email']);
  $newDesc = trim($_POST['desc']);
  $password = trim($_POST['password']);
  $newPassword = trim($_POST['new_password']);
  if (!empty($newDesc)) {
    $stmt = $connect->prepare("UPDATE users SET description = :newDesc WHERE id = :user_id");
    $stmt->bindParam(':newDesc', $newDesc);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
  }
  if ($_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
    $avatarName = 'avatar-' . $user_id . '-' . date('dmY') . '-' . substr(md5(uniqid(rand(), true)), 0, 8) . '.png'; // Adjust the extension based on the file type
    $avatarPath = './uploads/avatars/' . $avatarName;
    move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarPath);
    $stmtOldAvatar = $connect->prepare("SELECT avatar_path FROM users WHERE id = :user_id");
    $stmtOldAvatar->bindParam(':user_id', $user_id);
    $stmtOldAvatar->execute();
    $oldAvatarPath = $stmtOldAvatar->fetchColumn();

    if ($oldAvatarPath && file_exists($oldAvatarPath)) {
      unlink($oldAvatarPath);
    }
    $stmt = $connect->prepare("UPDATE users SET avatar_path = :avatarPath WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':avatarPath', $avatarPath);
    $stmt->execute();
  }
  if (!empty($newUsername)) {
    $stmt = $connect->prepare("UPDATE users SET username = :newUsername WHERE id = :user_id");
    $stmt->bindParam(':newUsername', $newUsername);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
  }
  if (!empty($newEmail)) {
    $emailCheckQuery = "SELECT COUNT(*) FROM users WHERE email = ? AND id != ?";
    $emailCheckStmt = $connect->prepare($emailCheckQuery);
    $emailCheckStmt->execute([$newEmail, $user_id]);
    $emailExists = $emailCheckStmt->fetchColumn();
    if ($emailExists > 0) {
      $error = $afterError . '<p class="errorMessage">Aдрес электронной почты ' . $unicodeChar . $newEmail . $unicodeChar . ' уже занят.</p>';
    } else {
      $stmt = $connect->prepare("UPDATE users SET email = :newEmail WHERE id = :user_id");
      $stmt->bindParam(':newEmail', $newEmail);
      $stmt->bindParam(':user_id', $user_id);
      if ($stmt->execute()) {
      } else {
        echo "Произошла ошибка при обновлении email.";
        print_r($stmt->errorInfo());
      }
    }
  }
  if (!empty($password)) {
    $stmtVerifyPassword = $connect->prepare("SELECT password FROM users WHERE id = :user_id");
    $stmtVerifyPassword->bindParam(':user_id', $user_id);
    $stmtVerifyPassword->execute();
    $hashedPassword = $stmtVerifyPassword->fetchColumn();

    if (password_verify($password, $hashedPassword)) {
      $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

      $stmtUpdatePassword = $connect->prepare("UPDATE users SET password = :newPassword WHERE id = :user_id");
      $stmtUpdatePassword->bindParam(':user_id', $user_id);
      $stmtUpdatePassword->bindParam(':newPassword', $hashedNewPassword);
      $stmtUpdatePassword->execute();
    } else {
      $errorPassword = $afterError . '<p class="errorMessage">Неправильный пароль, попробуйте еще раз.</p>';
    }
  }
  if ($_FILES['cv']['error'] == UPLOAD_ERR_OK) {
    $stmtOldCV = $connect->prepare("SELECT cv_path FROM users WHERE id = :user_id");
    $stmtOldCV->bindParam(':user_id', $user_id);
    $stmtOldCV->execute();
    $oldCVPath = $stmtOldCV->fetchColumn();

    if ($oldCVPath && file_exists($oldCVPath)) {
      unlink($oldCVPath);
    }

    $cvName = 'cv-' . $user_id . '-' . date('dmY') . '-' . substr(md5(uniqid(rand(), true)), 0, 8) . '.pdf';
    $cvPath = './uploads/cv/' . $cvName;
    move_uploaded_file($_FILES['cv']['tmp_name'], $cvPath);

    $stmt = $connect->prepare("UPDATE users SET cv_path = :cvPath WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':cvPath', $cvPath);
    $stmt->execute();
  }


  if (empty($error) && empty($errorPassword)) {
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
  }
}

$stmt = $connect->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$oldUsername = $user["username"];
$oldEmail = $user["email"];
$desc = $user["description"];
$avatar = $user["avatar_path"];
$cvPath = $user["cv_path"];

$pageTitleValue = "Пользователь " . $user['username'];
$pageTitle = '<p class="title-block">' . $pageTitleValue . '</p>';
$title = "Пользователь " . $user['username'];

?>


<?php
include "incs/header.php";
?>

<div class="worker">

  <form method="post" enctype="multipart/form-data" class="worker-card">

    <div class="modal-body start">
      <label for="avatar" class="input-form-avatar" ondrop="handleDrop(event)" ondragover="allowDrop(event)">
        <input type="file" accept="image/*" id="avatar" name="avatar" onchange="previewOrRestoreAvatar(this);">
        <div id="avatar-preview-container" ondrop="handleDrop(event)" ondragover="allowDrop(event)">
          <?php if ($avatar && file_exists($avatar)) : ?>
            <img id="avatar-preview" src="<?php echo $avatar; ?>" alt="Avatar">
          <?php else : ?>
            <img id="avatar-preview" src="./assets/img/placeholder-image.png" alt="Placeholder Avatar">
          <?php endif; ?>
        </div>
      </label>
      <label for="sign-input-cv" class="input-form">
        <svg>
          <use href="./assets/img/sprite.svg#cv"></use>
        </svg>
        <input class="sign-input" id="sign-input-cv" type="file" name="cv" accept=".pdf, .doc, .docx" placeholder="Выберите резюме" value="<?php echo $cvPath; ?>">
      </label>
      <label for="sign-input-user" class="input-form">
        <svg>
          <use href="./assets/img/sprite.svg#user"></use>
        </svg>
        <input class="sign-input" id="sign-input-user" type="text" name="username" placeholder="Новое ФИО" value="<?php echo $oldUsername; ?>">
      </label>
      <div class="error-block">
        <label for=" sign-input-email" class="input-form">
          <svg>
            <use href="./assets/img/sprite.svg#mail"></use>
          </svg>
          <input class="sign-input" id="sign-input-email" type="email" name="email" placeholder="Новый Email*" value="<?php echo $oldEmail; ?>">
        </label>
        <?php if (!empty($error)) {
          echo $error;
        } ?>
      </div>
      <label for="sign-input-desc" class="input-form input-form-area">
        <svg>
          <use href="./assets/img/sprite.svg#user"></use>
        </svg>
        <textarea class="sign-input sign-input-area" id="sign-input-desc" type="text" name="desc" placeholder="Введите описание" value=""><?php echo $desc; ?></textarea>
      </label>
      <div class="error-block">
        <label for="sign-input-password-old" class="input-form">
          <svg>
            <use href="./assets/img/sprite.svg#password"></use>
          </svg>
          <input class="sign-input" id="sign-input-password-old" type="password" name="password" placeholder="Старый Пароль*">
          <i class="fa-regular fa-eye toggle-password" style="margin-left: auto;" id="toggle-password-icon"></i>
        </label>
        <?php if (!empty($errorPassword)) {
          echo $errorPassword;
        } ?>
      </div>

      <label for="sign-input-password" class="input-form">
        <svg>
          <use href="./assets/img/sprite.svg#password"></use>
        </svg>
        <input class="sign-input" id="sign-input-password" type="password" name="new_password" placeholder="Новый Пароль*">
      </label>

      <label for="sign-input-confirm-password" class="input-form">
        <svg>
          <use href="./assets/img/sprite.svg#password"></use>
        </svg>
        <input class="sign-input" id="sign-input-confirm-password" type="password" name="confirm-password" placeholder="Подтверждение Пароля*">
      </label>
      <div id="errorMessage" class="errorMessage"></div>


    </div>
    <div class="modal-footer ">
      <a href="logout" class="button-modal-logout" style="margin-right:auto;">Выйти</a>
      <a href="/" class="button-modal-cancel">Вернуться</a>
      <button type="submit" class="button-modal" style="margin-left: 8px;" name="update">Сохранить</button>
    </div>
  </form>

</div>
<script>
  function allowDrop(event) {
    event.preventDefault();
  }

  function handleDrop(event) {
    event.preventDefault();
    const files = event.dataTransfer.files;

    if (files.length > 0) {
      const file = files[0];
      const reader = new FileReader();

      reader.onload = function(e) {
        document.getElementById('avatar-preview').src = e.target.result;
      };

      reader.readAsDataURL(file);
    }
  }
  document.addEventListener('DOMContentLoaded', function() {
    const togglePasswordIcon = document.getElementById('toggle-password-icon');
    const passwordInput = document.getElementById('sign-input-password');
    const passwordInputOld = document.getElementById('sign-input-password-old');
    const confirmPasswordInput = document.getElementById('sign-input-confirm-password');
    const errorMessageElement = document.getElementById('errorMessage');
    const registerButton = document.getElementById('registerButton');

    togglePasswordIcon.addEventListener('click', function() {
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordInputOld.type = 'text';
        confirmPasswordInput.type = 'text';
        togglePasswordIcon.classList.remove('fa-eye');
        togglePasswordIcon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        passwordInputOld.type = 'password';
        confirmPasswordInput.type = 'password';
        togglePasswordIcon.classList.remove('fa-eye-slash');
        togglePasswordIcon.classList.add('fa-eye');
      }
    });

    confirmPasswordInput.addEventListener('input', function() {
      const password = passwordInput.value;
      const confirmPassword = confirmPasswordInput.value;

      if (password === confirmPassword) {
        errorMessageElement.innerHTML = '';
        registerButton.disabled = false;
      } else {
        errorMessageElement.innerHTML = 'Пароль и подтверждение пароля не совпадают.';
        registerButton.disabled = true;
      }
    });
  });
  var originalAvatar = '<?php echo $avatar; ?>';

  function previewOrRestoreAvatar(input) {
    var previewContainer = document.getElementById('avatar-preview-container');
    var previewImage = document.getElementById('avatar-preview');

    if (input.files && input.files[0]) {
      var reader = new FileReader();

      reader.onload = function(e) {
        previewImage.src = e.target.result;
      };

      reader.readAsDataURL(input.files[0]);
      previewContainer.style.display = 'block';
    } else {
      previewImage.src = originalAvatar || './assets/img/placeholder-image.png';
      previewContainer.style.display = 'block';
    }
  }
</script>
<?php
include "incs/footer.php";
?>