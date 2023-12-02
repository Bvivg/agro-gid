  <?php
  session_start();
  
$hideSideBar = 'style="display: none;"';


  require './system/db.php';
  if (isset($_SESSION["user_id"])) {
    header("Location: get-started");
    exit();
  }
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['farmer'])) {
      $_SESSION['profession'] = 'farmer';
      header('Location: sign-up');
      exit();
    } elseif (isset($_POST['unemployed'])) {
      $_SESSION['profession'] = 'unemployed';
      header('Location: sign-up');
      exit();
    }
  }
  if (isset($_SESSION["user_id"])) {
    header("Location: /");
    exit();
  }

  $title = "Профессия";
  include "incs/header.php";
  ?>
  <div class="start">

    <img src="./assets/img/mainlogo.png" class="main-logo">
    <form class="button-block" method="post" action="">
      <button class="button" name="farmer">я фермер</button>
      <button class="button" name="unemployed">я в поисках работы</button>
    </form>
  </div>

  <?php
  include "incs/footer.php";

  ?>