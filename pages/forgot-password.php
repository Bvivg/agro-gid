<?php
session_start();
if (isset($_SESSION["user_id"])) {
  header("Location: /");
  exit();
}
session_start();
if (isset($_SESSION["user_id"])) {
  header("Location: /");
  exit();
}
require './system/db.php';

$error = "";
$enteredCode = '';
$storedCode = '';
$codeTime = '';
function generateVerificationCode()
{
  return mt_rand(100000, 999999);
}

function sendVerificationCode($email, $code)
{
  $to = $email;
  $subject = 'Код подтверждения';
  $message = 'Ваш код подтверждения: ' . $code;

  $headers = 'From: your_email@example.com' . "\r\n" .
    'Reply-To: your_email@example.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

  $success = mail($to, $subject, $message, $headers);

  if ($success) {
    echo "Код подтверждения отправлен на почту.";
  } else {
    echo "Ошибка при отправке кода на почту.";
  }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (isset($_POST["email"])) {
    $email = $_POST["email"];

    $query = "SELECT * FROM `users` WHERE `email` = ?";
    $stmt = $connect->prepare($query);
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
      $verificationCode = generateVerificationCode();
      $_SESSION['verification_code'] = $verificationCode;
      $_SESSION['email'] = $email;
      $codeTime = time() + 3600;
      $codeTimeFormatted = date("Y-m-d H:i:s", $codeTime);

      $_SESSION['code_time'] = $codeTime;

      $updateCodeQuery = "UPDATE `users` SET `password_restore` = ?, `password_restore_time` = ? WHERE `email` = ?";
      $stmtUpdateCode = $connect->prepare($updateCodeQuery);
      $stmtUpdateCode->execute([$verificationCode, $codeTimeFormatted, $email]);

      sendVerificationCode($email, $verificationCode);

      header('Location: forgot-password');
      exit;
    } else {
      $error = '<p class="errorMessage">Пользователь с таким email не найден.</p>';
    }
  } elseif (isset($_POST["code"], $_SESSION['verification_code'], $_SESSION['email'])) {
    $enteredCode = $_POST["code"];
    $storedCode = $_SESSION['verification_code'];
    $email = $_SESSION['email'];
    $codeTime = $_SESSION['code_time'];

    if ($enteredCode == $storedCode && time() < $codeTime) {
    } else {
      $error = '<p class="errorMessage">Неверный код восстановления или истек срок его действия.</p>';
    }
  } elseif (isset($_POST["password"], $_POST["confirm-password"], $_SESSION['email'])) {
    $newPassword = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $email = $_SESSION['email'];

    $updateQuery = "UPDATE `users` SET `password` = ?, `password_restore` = NULL, `password_restore_time` = NULL WHERE `email` = ?";
    $stmt = $connect->prepare($updateQuery);
    $stmt->execute([$newPassword, $email]);

    $clearCodeQuery = "UPDATE `users` SET `password_restore` = NULL, `password_restore_time` = NULL WHERE `email` = ?";
    $stmtClearCode = $connect->prepare($clearCodeQuery);
    $stmtClearCode->execute([$email]);

    unset($_SESSION['verification_code']);
    unset($_SESSION['email']);
    unset($_SESSION['code_time']);

    $successMessage = '<p class="errorMessage">Пароль успешно обновлен.</p>';
    header('Location: sign-in');
    exit;
  }
}

$title = "Востановление";

include "incs/header.php";
?>
<form method="post" action="" class="start pb-0">
  <div class="input-box">
    <h1>Востановление</h1>

    <?php if (isset($error)) : ?>
      <div class="error-message"><?= $error ?></div>
    <?php endif; ?>

    <?php if (isset($successMessage)) : ?>
      <div class="success-message"><?= $successMessage ?></div>
    <?php endif; ?>

    <?php if (!isset($_SESSION['verification_code'])) : ?>
      <label for="sign-input-email" class="input-form">
        <svg>
          <use href="./assets/img/sprite.svg#mail"></use>
        </svg>
        <input class="sign-input" id="sign-input-email" type="email" name="email" required placeholder="Email*">
      </label>
      <div class="btn-policy">
        <button type="submit" class="button">Отправить код</button>
        <a href="sign-in" class="button button-cancel">Отмена</a>
      </div>
    <?php elseif (isset($_SESSION['verification_code']) && $enteredCode == $storedCode && time() < $codeTime) : ?>
      <label for="sign-input-password" class="input-form">
        <svg>
          <use href="./assets/img/sprite.svg#password"></use>
        </svg>
        <input class="sign-input" id="sign-input-password" type="password" name="password" required placeholder="Пароль*">
        <i class="fa fa-eye toggle-password" style="margin-left:  auto;" id="toggle-password-icon"></i>
      </label>
      <label for="sign-input-confirm-password" class="input-form">
        <svg>
          <use href="./assets/img/sprite.svg#password"></use>
        </svg>
        <input class="sign-input" id="sign-input-confirm-password" type="password" name="confirm-password" required placeholder="Подтверждение пароля*">
      </label>
      <div id="errorMessage" class="errorMessage"></div>
      <div class="btn-policy">
        <button type="submit" class="button" id="registerButton">Восстановить</button>
        <a href="sign-in" class="button button-cancel">Отмена</a>
      </div>
    <?php elseif (isset($_SESSION['verification_code'])) : ?>
      <label for="sign-input-code" class="input-form">
        <svg>
          <use href="./assets/img/sprite.svg#code"></use>
        </svg>
        <input class="sign-input" id="sign-input-code" type="text" name="code" required placeholder="Код восстановления*">
      </label>
      <div class="btn-policy">
        <button type="submit" class="button" id="registerButton">Восстановить</button>
        <a href="sign-in" class="button button-cancel">Отмена</a>
      </div>

    <?php endif; ?>
    <div id="errorMessage" class="errorMessage"></div>





  </div>
</form>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const togglePasswordIcon = document.getElementById('toggle-password-icon');
    const passwordInput = document.getElementById('sign-input-password');
    const confirmPasswordInput = document.getElementById('sign-input-confirm-password');
    const errorMessageElement = document.getElementById('errorMessage');
    const registerButton = document.getElementById('registerButton');

    togglePasswordIcon.addEventListener('click', function() {
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        confirmPasswordInput.type = 'text';
        togglePasswordIcon.classList.remove('fa-eye');
        togglePasswordIcon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
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
</script>

<?php
include "incs/footer.php";
?>