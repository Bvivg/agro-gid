<?php
require "./system/routing.php";
$url = key($_GET);
$r = new Router();

// pages
$r->addRoute("/", "home.php");
$r->addRoute("/profession", "profession.php");
$r->addRoute("/get-started", "get-started.php");
$r->addRoute("/sign-in", "sign-in.php");
$r->addRoute("/sign-up", "sign-up.php");
$r->addRoute("/data-processing", "data-processing.php");
$r->addRoute("/data-processing-policy", "data-processing-policy.php");
$r->addRoute("/workers", "workers.php");
$r->addRoute("/worker", "worker.php");
$r->addRoute("/map", "map.php");
$r->addRoute("/goods", "goods.php");;
$r->addRoute("/farmer", "farmer.php");;
$r->addRoute("/farmers", "farmers.php");;
$r->addRoute("/aboutme", "aboutme.php");;
$r->addRoute("/jobinvitations", "jobinvitations.php");;
$r->addRoute("/employments", "employments.php");;
$r->addRoute("/forgot-password", "forgot-password.php");
$r->addRoute("/chat", "chat.php");
$r->addRoute("/chatsidebar", "chatsidebar.php");
$r->addRoute("/logout", "logout.php");
$r->route("/" . $url);
