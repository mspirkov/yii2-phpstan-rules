<?php

$id = $_GET['id'];
$payload = $_POST;
$avatar = $_FILES['avatar'];
$theme = $_COOKIE['theme'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];
$request = $_REQUEST;
$session = $_SESSION['user'] ?? null;
$name = 'declaredVariable';
$declaredVariable = null;
$dynamic = ${$name};
