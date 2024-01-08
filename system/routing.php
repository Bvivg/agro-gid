<?php
class Router
{
  private $pages = array();

  public function addRoute($url, $path)
  {
    $this->pages[$url] = $path;
  }

  public function route($url)
  {
    $urlParts = explode('/', trim($url, '/'));
    $urlKey = '/' . $urlParts[0]; 

    if (isset($this->pages[$urlKey])) {
      $path = $this->pages[$urlKey];
      $file_dir = "pages/" . $path;
      $_GET['username'] = isset($urlParts[1]) ? $urlParts[1] : null;

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
