<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require __DIR__ . '/../bootstrap/init.php';

$router = new App\Router();
$router->load($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], $_REQUEST);