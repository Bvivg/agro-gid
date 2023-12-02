<?php
$server = '192.168.7.214';
$db = 'AGRO GID';
$name = 'root';
$password = '';
try {
  $connect = new PDO("mysql:host=$server; dbname=$db", $name, $password);
  $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  echo "Подключение прерванно: " . $e->getMessage();
}
