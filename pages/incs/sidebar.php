<?php
$currentPage = basename($_SERVER['REQUEST_URI']);

if (!empty($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];

  $stmt = $connect->prepare("SELECT * FROM users WHERE id = :user_id");
  $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($row['profession'] == 'farmer') : ?>
    <div class="banner-card-row" id="professionLimit" <?php if (!empty($hideSideBar)) {
                                                        echo $hideSideBar;
                                                      } ?>>
      <a href="/employments" class="banner-card">
        <i class="fa-solid fa-handshake <?= ($currentPage == 'employments') ? ' active' : ''; ?>"></i>
      </a>
      <a href="/workers" class="banner-card">
        <i class="fa-solid fa-tractor <?= ($currentPage == 'workers') ? ' active' : ''; ?>"></i>
      </a>
      <a href="/" class="banner-card">
        <i class="fa-solid fa-house <?= ($currentPage == '') ? ' active' : ''; ?>"></i>
      </a>
      <a href="/chatsidebar" class="banner-card">
        <i class="fa-solid fa-message <?= ($currentPage == 'chatsidebar') ? ' active' : ''; ?>"></i>
      </a>
      <a href="/aboutme" class="banner-card">
        <i class="fa-solid fa-user <?= ($currentPage == 'aboutme') ? ' active' : ''; ?>"></i>
      </a>
    </div>
  <?php elseif ($row['profession'] == 'unemployed') : ?>
    <div class="banner-card-row" id="professionLimit" <?php if (!empty($hideSideBar)) {
                                                        echo $hideSideBar;
                                                      } ?>>
      <a href="/jobinvitations" class="banner-card">
        <i class="fa-solid fa-handshake <?= ($currentPage == 'jobinvitations') ? ' active' : ''; ?>"></i>
      </a>
      <a href="/farmers" class="banner-card">
        <i class="fa-solid fa-tractor <?= ($currentPage == 'farmers') ? ' active' : ''; ?>"></i>
      </a>
      <a href="/" class="banner-card">
        <i class="fa-solid fa-house <?= ($currentPage == '') ? ' active' : ''; ?>"></i>
      </a>
      <a href="/chatsidebar" class="banner-card">
        <i class="fa-solid fa-message <?= ($currentPage == 'chatsidebar') ? ' active' : ''; ?>"></i>
      </a>
      <a href="/aboutme" class="banner-card">
        <i class="fa-solid fa-user <?= ($currentPage == 'aboutme') ? ' active' : ''; ?>"></i>
      </a>
    </div>
<?php endif;
}
?>