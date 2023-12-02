<?php
$searchQuery = "";
$result = [];
$spec = "";
$resultUndef = '';
if (!empty($special)) {
  $spec = $special;
}

function highlightSearch($text, $searchQuery)
{
  return preg_replace("/($searchQuery)/iu", '<span class="highlight">$1</span>', $text);
}

if (isset($_GET['search'])) {
  $searchQuery = $_GET['searchInput'];
  if (!empty($searchQuery)) {
    $query = "SELECT * FROM " . $table . " WHERE" . $spec . $column . " LIKE :searchQuery";

    $stmt = $connect->prepare($query);
    $searchPattern = '%' . $searchQuery . '%';
    $stmt->bindValue(':searchQuery', $searchPattern);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($result)) {
      $resultUndef = 'Ничего не найдено';
      
    }
  } elseif (empty($result)) {
    $resultUndef = 'Ничего не найдено'; 
  }
}

