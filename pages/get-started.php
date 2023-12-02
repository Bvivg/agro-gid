<?php
session_start();
if (isset($_SESSION["user_id"])) {
  header("Location: /");
  exit();
}


$hideSideBar = 'style="display: none;"';


$title = "Начать";
include "incs/header.php";
?>
<div class="start">

  <img src="./assets/img/mainlogo.png" class="main-logo">
  <a href="/profession" class="button first-page-btn">Начать</a>
</div>

<?php
include "incs/footer.php";

?>