<?php
session_start();

$hideSideBar = 'style="display: none;"';

if (isset($_SESSION["user_id"])) {
  header("Location: /");
  exit();
}
require './system/db.php';
if (isset($_SESSION['profession'])) {
  $selectedProfession = $_SESSION['profession'];
} else {
  header('Location: profession');
  exit();
} 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['username'], $_POST['email'], $_POST['password'], $_POST['confirm-password'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm-password']);
    $accepted = (isset($_POST['acceptData']) && trim($_POST['acceptData']) == 'on') ? 'accept' : 'decline';


    $emailCheckQuery = "SELECT COUNT(*) FROM users WHERE email = ?";
    $emailCheckStmt = $connect->prepare($emailCheckQuery);
    $emailCheckStmt->execute([$email]);
    $emailExists = $emailCheckStmt->fetchColumn();

    if ($emailExists > 0) {
      $error = '<p class="errorMessage">Этот адрес электронной почты уже используется.</p>';
    } else {
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

      $sql = "INSERT INTO users (profession, username, email, password, accepted) VALUES (?, ?, ?, ?, ?)";
      $stmt = $connect->prepare($sql);
      $stmt->execute([$selectedProfession, $username, $email, $hashedPassword, $accepted]);

      header('Location: sign-in');
      exit();
    }
  } else {
    $error = "Пожалуйста, заполните все необходимые поля.";
  }
}

$title = "Регистрация";

include "incs/header.php";
?>

<form method="post" action="" class="start">
  <input type="hidden" name="profession" value="<?php echo $selectedProfession; ?>">
  <div class="input-box">
    <h1>Регистрация</h1>
    <label for="sign-input-user" class="input-form">
      <svg>
        <use href="./assets/img/sprite.svg#user"></use>
      </svg>
      <input class="sign-input" id="sign-input-user" type="text" name="username" required placeholder="ФИО">
    </label>
    <label for="sign-input-email" class="input-form">
      <svg>
        <use href="./assets/img/sprite.svg#mail"></use>
      </svg>
      <input class="sign-input" id="sign-input-email" type="email" name="email" required placeholder="Email*">
    </label>

    <?php if (!empty($error)) {
      echo $error;
    } ?>
    <label for="sign-input-password" class="input-form">
      <svg>
        <use href="./assets/img/sprite.svg#password"></use>
      </svg>
      <input class="sign-input" id="sign-input-password" type="password" name="password" required placeholder="Пароль*">
      <i class="fa-solid fa-eye toggle-password" style="margin-left:  auto;" id="toggle-password-icon"></i>
    </label>


    <label for="sign-input-confirm-password" class="input-form">
      <svg>
        <use href="./assets/img/sprite.svg#password"></use>
      </svg>
      <input class="sign-input" id="sign-input-confirm-password" type="password" name="confirm-password" required placeholder="Подтверждение пароля*">
    </label>

    <div id="errorMessage" class="errorMessage"></div>



  </div>

  <div class="btn-policy">
    <button type="submit" class="button " id="registerButton">Зарегистрироваться</button>
    <div class="agreement">
      <label for="acceptData">
        <input type="checkbox" id="acceptData" name="acceptData" required>
        <span class="checkmark"></span>
      </label>
      <p>
        При регистрации вы соглашаетесь на
        <a href="data-processing" class="link">обработку ваших данных</a>
        и
        <a href="data-processing-policy" class="link">политику соглашения</a>.
      </p>
    </div>
  </div>
  <span class="haveAcc">
    У вас уже есть Аккаунт?
    <a href="sign-in">Войти</a>
  </span>

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