<?php

require './system/db.php';
$title = "Главная";
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: get-started");
  exit();
}
if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];
  $stmt = $connect->prepare("SELECT * FROM users WHERE id = :user_id");
  $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
}
// $api_key = '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';
// $openai_url = 'https://api.openai.com/v1/engines/davinci/completions';
// $topic = 'Совет для фермеров: ';
// $data = array(
//   'prompt' => $topic,
//   'max_tokens' => 100,
//   'temperature' => 0.7,
// );
// $ch = curl_init($openai_url);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POST, 1);
// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
// curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//   'Content-Type: application/json',
//   'Authorization: Bearer ' . $api_key,
// ));
// $response = curl_exec($ch);
// if (curl_errno($ch)) {
//   echo 'Ошибка cURL: ' . curl_error($ch);
// } else {
//   $result = json_decode($response, true);
//   if (isset($result['choices']) && is_array($result['choices']) && count($result['choices']) > 0) {
//     echo 'Совет дня для фермеров: ' . $result['choices'][0]['text'] . "\n\n";
//   }
//   echo 'Полный ответ: ' . print_r($result, true);
// }
// curl_close($ch);
// ?>
<?php
include "incs/header.php";
?>

<?php
include "incs/navbar.php";
?>

<div class="advice-row">
  <div class="advice">
    <div class="advice-title-block">
      <p>Совет дня:</p>
      <?php
      echo '<span>' .  date("Y-m-d") . '</span>';
      ?>
    </div>

    <span class="advice-title">Внимание к деталям — ключ к успешному урожаю.</span>
    <p>Постарайтесь создать оптимальные условия для роста растений, регулярно проверяйте почву, следите за погодными условиями и не забывайте о своих растениях. Тщательный уход и внимание к каждой детали помогут вам достичь максимального урожая на вашей ферме.</p>
  </div>
</div>

<?php
include "incs/footer.php";
?>