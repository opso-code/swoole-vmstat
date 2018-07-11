<?php

define('DS', DIRECTORY_SEPARATOR);
define('WEB_ROOT', realpath(__DIR__));
define('LOG_PATH', WEB_ROOT . DS . 'logs');
define('PUBLIC_PATH', WEB_ROOT . DS . 'public');
define('WS_HOST', '0.0.0.0');
define('WS_PORT', 9100);

date_default_timezone_set('Asia/Shanghai');

require WEB_ROOT . '/server.php';

$server = new StatServer();
$server->run();