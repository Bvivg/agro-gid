<?php
session_start();

$user_id = $_SESSION["user_id"];
require './system/db.php';

$errorPassword = '';

$unicodeChar = "\u{0027}";
$afterError = '<script>
          document.addEventListener("DOMContentLoaded", function() {
            var settingsButton = document.getElementById("settingsBtn");
            var clickEvent = new Event("click");
            settingsButton.dispatchEvent(clickEvent);
          });
        </script>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user_id = $_SESSION["user_id"];
  $newUsername = trim($_POST['username']);
  $newEmail = trim($_POST['email']);
  $password = trim($_POST['password']);
  $newPassword = trim($_POST['new_password']);

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
$avatar = $user["avatar_path"];
?>




<header class="header">

  <a href="/">
    <img src="./assets/img/agro-gid.png" alt="">
  </a>

</header>
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