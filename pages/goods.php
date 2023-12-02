<?php
require './system/db.php';
$stmt = $connect->prepare("SELECT * FROM goods");
$stmt->execute();
$goods = $stmt->fetchAll(PDO::FETCH_ASSOC);
$goodsImg = isset($goods["img"]) ? $goods["img"] : null;

session_start();
$user_id = $_SESSION['user_id'];
if (!isset($_SESSION["user_id"])) {
  header("Location: get-started");
  exit();
}
$searchQuery = "";

$table = "goods";
$column = "name";

if (!empty($result)) {
  $goods = $result;
}

include "incs/search.php";

$pageTitleValue = "Материалы и оборудование";
$pageTitle = '<p class="title-block">' . $pageTitleValue . '</p>';
$title = "Материалы и оборудование";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $good_id = $_POST['good_id'];
  $goodname = $_POST['goodname'];
  $gooddescription = $_POST['gooddescription'];
  $goodprice = $_POST['goodprice'];


  if (isset($_POST['add'])) {
    if ($_FILES['goodImg']['error'] == UPLOAD_ERR_OK) {
      $goodsImg = 'goodImg-' . $user_id . '-' . $good_id . '-' . date('dmY') . '-' . substr(md5(uniqid(rand(), true)), 0, 8) . '.png'; // Adjust the extension based on the file type
      $GoodPath = './uploads/goodsImg/' . $goodsImg;
      move_uploaded_file($_FILES['goodImg']['tmp_name'], $GoodPath);
      $stmt = $connect->prepare("UPDATE goods SET img = :img WHERE id = :good_id");
      $stmt->bindParam(':good_id', $good_id);
      $stmt->bindParam(':img', $GoodPath);
      $stmt->execute();
      $stmtOldGoodImg = $connect->prepare("SELECT img FROM goods WHERE id = :good_id");
      $stmtOldGoodImg->bindParam(':good_id', $good_id);
      $stmtOldGoodImg->execute();
      $oldGoodPath = $stmtOldGoodImg->fetchColumn();
      if ($oldGoodPath && file_exists($oldGoodPath)) {
        unlink($oldGoodPath);
      }
    }
    $insertQuery = "INSERT INTO goods (name, info, price, user_id, img) VALUES (:name, :info, :price, :user_id, :img)";
    $stmt = $connect->prepare($insertQuery);
    $stmt->bindParam(':name', $goodname);
    $stmt->bindParam(':info', $gooddescription);
    $stmt->bindParam(':price', $goodprice);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':img', $GoodPath);
    $stmt->execute();
    header("Location: goods");
    exit();
  } elseif (isset($_POST['update'])) {
    $uploadDir = './uploads/goodsImg/';
    $goodsImg = 'goodImg-' . $user_id . '-' . $good_id . '-' . date('dmY') . '-' . substr(md5(uniqid(rand(), true)), 0, 8) . '.png';
    $uploadPath = $uploadDir . $goodsImg;
    $uploadDir = './uploads/goodsImg/';

    if ($_FILES['goodImg']['error'] == 0) {
      move_uploaded_file($_FILES['goodImg']['tmp_name'], $uploadPath);
    }
    $updateQuery = "UPDATE goods SET name = :name, info = :info, img = :img, price = :price WHERE id = :good_id";
    $stmt = $connect->prepare($updateQuery);
    $stmt->bindParam(':name', $goodname);
    $stmt->bindParam(':info', $gooddescription);
    $stmt->bindParam(':price', $goodprice);
    $stmt->bindParam(':good_id', $good_id);
    $stmt->bindParam(':img', $uploadPath);

    $stmt->execute();

    header("Location: goods");
    exit();
  } elseif (isset($_POST['delete'])) {
    $deleteQuery = "DELETE FROM goods WHERE id = :id";
    $stmt = $connect->prepare($deleteQuery);
    $stmt->bindParam(':id', $good_id);

    if ($stmt->execute()) {
      header("Location: goods");
      exit();
    } else {
      echo "Error deleting record from the database";
    }
  }
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
    <input type="text" class="search-input" name="searchInput" id="searchInput" placeholder="<?php echo $pageTitleValue ?> " value="<?php echo isset($_GET['searchInput']) ? $_GET['searchInput'] : ''; ?>">
    <button type="submit" name="search"></button>
  </form> <?php if (!empty($resultUndef)) : ?>
    <?php echo $resultUndef ?>
  <?php else : ?>
    <?php foreach ($goods as $good) : ?>
      <div class="workers-card <?php echo ($_SESSION['user_id'] == $good['user_id']) ? 'edit' : ''; ?>">
        <?php
              $goodsImg = $good['img'];
              if ($goodsImg && file_exists($goodsImg)) : ?>
          <img src="<?php echo $goodsImg; ?>" alt="">
        <?php else : ?>
          <img src="./assets/img/placeholder-image.png" alt="Placeholder img">
        <?php endif; ?>
        <div class="workers-card-info">
          <p><?php echo highlightSearch($good['name'], $searchQuery); ?></p>
          <p><?php echo $good['info']; ?></p>
          <p><?php echo $good['price']; ?></p>
        </div>
        <div class="edit-buttons">
          <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#update-good<?php echo $good['id'] ?>">
            <i class="fa-solid fa-pen-to-square"></i>
          </button>
          <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#delete-good<?php echo $good['id'] ?>">
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>
      </div>
      <div class="modal fade" id="update-good<?php echo $good['id'] ?>" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <form enctype="multipart/form-data" method="post" class="modal-content">

            <input type="hidden" name="good_id" value="<?php echo $good['id'] ?>">
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="exampleModalLabel">Редактировать карточку <?php echo $good['name'] ?></h1>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body ">
              <div class="start">
                <label for="UpdategoodImg<?php echo $good['id'] ?>" class="input-form-goodImg">
                  <input type="file" accept="image/*" id="UpdategoodImg<?php echo $good['id'] ?>" name="goodImg" onchange="previewOrRestoreImage(this, 'UpdategoodImg-preview<?php echo $good['id'] ?>', '<?php echo $goodsImg ?>');">
                  <div id="UpdategoodImg-preview-container<?php echo $good['id'] ?>" class="goodImg-preview-container">
                    <img id="UpdategoodImg-preview<?php echo $good['id'] ?>" class="goodImg-preview" src="<?php echo $goodsImg ?>">
                  </div>
                </label>
                <label for="update-sign-input-name<?php echo $good['id'] ?>" class="input-form">
                  <input class="sign-input" id="update-sign-input-name<?php echo $good['id'] ?>" type="text" value="<?php echo $good['name'] ?>" required name="goodname" placeholder="Наименование товара*">
                </label>
                <label for="update-sign-input-desc<?php echo $good['id'] ?>" class="input-form">
                  <input class="sign-input" id="update-sign-input-desc<?php echo $good['id'] ?>" type="textarea" value="<?php echo $good['info'] ?>" required name="gooddescription" placeholder="Описание товара*">
                </label>
                <label for="update-sign-input-price<?php echo $good['id'] ?>" class="input-form">
                  <input class="sign-input" id="update-sign-input-price<?php echo $good['id'] ?>" type="text" value="<?php echo $good['price'] ?>" required name="goodprice" placeholder="Цена товара*">
                </label>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="button-modal-cancel" data-bs-dismiss="modal">Отмена</button>
              <button type="submit" sclass="button-modal" name="update">Сохранить</button>
            </div>

          </form>
        </div>
      </div>
      <div class="modal fade" id="delete-good<?php echo $good['id'] ?>" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <form enctype="multipart/form-data" method="post" class="modal-content">
            <input type="hidden" name="good_id" value="<?php echo $good['id'] ?>">
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="exampleModalLabel">Удалить карточку <?php echo $good['name'] ?>?</h1>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>


            <div class="modal-footer">
              <button type="button" class="button-modal-cancel" data-bs-dismiss="modal">Отмена</button>
              <button type="submit" class="button-modal" name="delete">Удалить</button>
            </div>

          </form>
        </div>
      </div>
    <?php endforeach; ?>


  <?php endif ?>
  <div>

  </div>

  <button type="button" class="add-goods-btn" data-bs-toggle="modal" data-bs-target="#add-good">
    +
  </button>
</div>


<div class="modal fade" id="add-good" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form enctype="multipart/form-data" method="post" class="modal-content">

      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Создать карточку</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body ">
        <div class="start">

          <label for="goodImg" class="input-form-goodImg">
            <div id="goodImg-preview-container" class="goodImg-preview-container">
              <img id="goodImg-preview" class="goodImg-preview">
            </div>
            <input type="file" accept="image/*" id="goodImg" name="goodImg" onchange="previewOrRestoreImage(this, 'goodImg-preview', '');">
          </label>

          <label for="sign-input-name" class="input-form">
            <input class="sign-input" id="sign-input-name" type="text" required name="goodname" placeholder="Наименование товара*">
          </label>
          <label for="sign-input-desc" class="input-form">
            <input class="sign-input" id="sign-input-desc" type="textarea" required name="gooddescription" placeholder="Описание товара*">
          </label>
          <label for="sign-input-price" class="input-form">
            <input class="sign-input" id="sign-input-price" type="text" required name="goodprice" placeholder="Цена товара*">
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="button-modal-cancel" data-bs-dismiss="modal">Отмена</button>
        <button type="submit" class="button-modal" name="add">Добавить</button>
      </div>

    </form>
  </div>
</div>

<script>
  function setupDragDrop(fileInputId, previewContainerId, previewId) {
    var dropZone = document.getElementById(previewContainerId);

    dropZone.addEventListener('dragover', function(e) {
      e.preventDefault();
      dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', function(e) {
      e.preventDefault();
      dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', function(e) {
      e.preventDefault();
      dropZone.classList.remove('dragover');

      var fileInput = document.getElementById(fileInputId);
      var file = e.dataTransfer.files[0];

      if (file && file.type.startsWith('image')) {
        var reader = new FileReader();

        reader.onload = function(event) {
          document.getElementById(previewId).src = event.target.result;
        };

        reader.readAsDataURL(file);

        fileInput.files = e.dataTransfer.files;
      }
    });
  }

  document.addEventListener("DOMContentLoaded", function() {
    setupDragDrop('goodImg', 'goodImg-preview-container', 'goodImg-preview');
    setupDragDrop('UpdategoodImg<?php echo $good['id'] ?>', 'UpdategoodImg-preview-container<?php echo $good['id'] ?>', 'UpdategoodImg-preview<?php echo $good['id'] ?>');
  });

  function previewOrRestoreImage(input, previewId, defaultImage) {
    var previewImage = document.getElementById(previewId);

    if (input.files && input.files[0]) {
      var reader = new FileReader();

      reader.onload = function(e) {
        previewImage.src = e.target.result;
      };

      reader.readAsDataURL(input.files[0]);
    } else {
      previewImage.src = defaultImage;
    }
  }
</script>


<?php
include "incs/footer.php";
?>