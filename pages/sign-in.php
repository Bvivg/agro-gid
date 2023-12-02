<?php
session_start();
require './system/db.php';

$hideSideBar = 'style="display: none;"';

session_unset();
session_destroy();

session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['email'], $_POST['password'])) {
    $email = trim($_POST['email']); 
    $password = trim($_POST['password']);


    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $connect->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];

      header('Location: /');
      exit();
    } else {
      $error = '<p class="errorMessage">Неверный email или пароль.</p>';
    }
  } else {
    $error = "Пожалуйста, заполните все необходимые поля.";
  }
}
if (isset($_SESSION["user_id"])) {
  header("Location: /");
  exit();
}



$title = "Вход";

include "incs/header.php";
?>

<form method="post" action="" class="start">
  <div class="input-box">
    <h1>Вход</h1>

    <label for="sign-input-email" class="input-form">
      <svg>
        <use href="./assets/img/sprite.svg#mail"></use>
      </svg>
      <input class="sign-input" id="sign-input-email" type="email" name="email" required placeholder="Email*">
    </label>
    <label for="sign-input-password" class="input-form">
      <svg>
        <use href="./assets/img/sprite.svg#password"></use>
      </svg>
      <input class="sign-input" id="sign-input-password-in" type="password" name="password" required placeholder="Пароль*">
      <i class="fa-solid fa-eye toggle-password" style="margin-left:  auto;" id="toggle-password-icon-in"></i>
    </label>
    <a class="link ms-auto" href="forgot-password">Забыл пароль?</a>
    <?php if (!empty($error)) {
      echo $error;
    } ?>
  </div>
  <div class="btn-policy">
    <button type="submit" class="button " id="registerButton">Войти</button>
    <!-- <div class="agreement">
      <label for="acceptData">
        <input type="checkbox" id="acceptData" name="acceptData" required>
        <span class="checkmark"></span>
      </label>
      <p>
        При входе вы соглашаетесь на
        <a href="data-processing" class="link">обработку ваших данных</a>
        и
        <a href="data-processing-policy" class="link">политику соглашения</a>.
      </p> 
    </div> -->

  </div>
  <span class="haveAcc">
    У вас еще нет Аккаунта?
    <a href="sign-up" class="link">Создать</a>
  </span>

  </div>
</form>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const togglePasswordIconIn = document.getElementById('toggle-password-icon-in');
    const passwordInputIn = document.getElementById('sign-input-password-in');
    const errorMessageElement = document.getElementById('errorMessage');
    togglePasswordIconIn.addEventListener('click', function() {
      if (passwordInputIn.type === 'password') {
        passwordInputIn.type = 'text';
        togglePasswordIconIn.classList.remove('fa-eye');
        togglePasswordIconIn.classList.add('fa-eye-slash');
      } else {
        passwordInputIn.type = 'password';
        togglePasswordIconIn.classList.remove('fa-eye-slash');
        togglePasswordIconIn.classList.add('fa-eye');
      }
    });


  });
</script>



<?php
include "incs/footer.php";
?>