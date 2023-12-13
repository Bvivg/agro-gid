<?php
$server = 'localhost';
$db = 'AGRO GID';
$name = 'root';
$password = '';
try {
  $connect = new PDO("mysql:host=$server; dbname=$db", $name, $password);
  $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  echo "Подключение прерванно: " . $e->getMessage();
}

return $connect;