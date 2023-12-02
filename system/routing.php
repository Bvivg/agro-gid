<?php
class Router
{
  private $pages = array();

  function addRoute($url, $path)
  {
    $this->pages[$url] = $path;
  }
  function route($url)
  {
    if (isset($this->pages[$url])) {
      $path = $this->pages[$url];
      $file_dir = "pages/" . $path;
      if ($path == "") {
        require "./404.php";
        die();
      }
      if (file_exists($file_dir)) {
        require $file_dir;
      } else {
        require "./404.php";
        die();
      }
    } else {
      require "./404.php";
      die();
    }
  }
}
