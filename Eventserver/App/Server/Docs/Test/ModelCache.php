<?php
require __DIR__.'/init.php';
use App\Server\Model\Subscriber;
$s = Subscriber::instance();
 $s->clearModelCache();
$r = $s->getNormalSubscriberWithCache('new_koubei_center');
var_dump($r);