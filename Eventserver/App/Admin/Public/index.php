<?php
require __DIR__.'/../constants.php';
session_start();
require SYS_ROOT.'/Vendor/Bootstrap/Autoloader.php';
Bootstrap\Autoloader::instance()->addRoot(SYS_ROOT)->addRoot(SYS_ROOT.'Vendor/')->init();
$app = new \Lib\BaseController();
$app->run('Admin');
