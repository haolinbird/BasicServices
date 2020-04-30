<?php
require __DIR__. '/../App/Admin/constants.php';
require __DIR__ . '/../Vendor/Bootstrap/Autoloader.php';
Bootstrap\Autoloader::instance()->addRoot(__DIR__ . '/../')->addRoot(__DIR__ . '/../Vendor/')->init();

! defined('APP_NAME') && define('APP_NAME', 'Admin');
