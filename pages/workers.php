<?php
require './system/db.php';
$stmt = $connect->prepare("SELECT * FROM users WHERE profession = 'unemployed'");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: get-started");
  exit();
}
$pageTitleValue = "Поиск рабочих";
$pageTitle = '<p class="title-block">' . $pageTitleValue . '</p>';
$title = "Поиск рабочих";

$table = "users";
$column = "username";
$column2 = "";
$special = "profession = 'unemployed' AND " . $column2 . " LIKE :searchQuery OR ";


include "incs/search.php";



if (!empty($result)) {
  $users = $result;
}


?>


<?php
include "incs/header.php";
?>

<?php
include "incs/navbar.php";
?>
<div class="workers">

  <form method="GET" action="" class="search">
    <input type="text" class="search-input" name="searchInput" id="searchInput" placeholder="<?php echo $pageTitleValue ?> " required value="<?php echo isset($_GET['searchInput']) ? $_GET['searchInput'] : ''; ?>">
    <button type="submit" name="search"></button>
  </form>
  <?php if (!empty($resultUndef)) : ?>
    <?php echo $resultUndef ?>
  <?php else : ?>
    <?php foreach ($users as $user) : ?>
      <a href="worker?id=<?php echo $user['id'] ?>" class="workers-card">
        <?php
        $avatar = $user['avatar_path'];
        if ($avatar && file_exists($avatar)) : ?>
          <img src="<?php echo $avatar; ?>" alt="">
        <?php else : ?>
          <img src="./assets/img/placeholder-image.png" alt="Placeholder Avatar">
        <?php endif; ?>
        <div class="workers-card-info">
          <p><?php echo highlightSearch($user['username'], $searchQuery); ?></p>
          <p>
            <?php
            if (!empty($user['rating'])) {
              $rating = $user['rating'];
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
        </div>
      </a>
    <?php endforeach; ?>

  <?php endif ?>
</div>
<?php
include "incs/footer.php";
?>