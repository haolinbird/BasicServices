<?php
require __DIR__.'/init.php';
var_dump(memory_get_usage());
$ports = array();
$st = microtime(true);
$total = $count = 320;
while($count-- > 0)
{
 $bt = \Lib\Beanstalk::instance();
$port = $bt->getConnection()->getPort();
$ports[$port] = isset($ports[$port]) ? $ports[$port]+1 : 1;
}
foreach ($ports as $k=>$v)
{
    echo "$k:". ($v/$total)."\n";
}
var_dump(memory_get_usage());
printf('%.10f', microtime(true) - $st);
die;

$bts = $bt->getInstancePools();
// var_dump($bts);
$bt = $bts[0];
// $content = file_get_contents('http://www.jumei.com/');
//  $bt->useTube ( 'kk4' );
//  $bt->put('flwefl');
$bt->watch('kk4');
$job = $bt->reserve();
var_dump($job->getId(),$bt->statsJob($job));
die;
$i = 10;
while($i-- > 0)
{
    $st = microtime(true);
    $re = $bt->put($content);
    $et = microtime(true);
    printf('consumed %.6f ms  ',1000*( $et-$st));
    var_dump($re);
}

// $bt->watch ( 'kk3' );
// $re = $bt->reserve ();
// $bt->watch('kk2');
// $re1 = $bt->reserve (1 );
// $bt->delete($re1);
// var_dump ( $re ,$re1);