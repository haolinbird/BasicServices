<?php
namespace App\Server;
require __DIR__.'/init.php';

$sv = new Service\Broadcast\StatisticMonitor();

$sv->execute();