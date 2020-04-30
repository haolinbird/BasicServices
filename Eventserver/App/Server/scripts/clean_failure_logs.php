<?php
date_default_timezone_set("Asia/Chongqing");
require __DIR__.'/../Gateway/init.php';

// 清理一个月以前的。
$time_threshold = strtotime(date("Y-m-d 00:00:00")) - 3600*24*30*1;

// 每次删除的日志数。
define('DELETE_STEPS', 20000);

echo "开始删除: ".date('Y-m-d H:i:s', $time_threshold)."前的日志, 每次删除 ".DELETE_STEPS." 条....\r\n";

// 获取开始的log_id

$db = new \Lib\Db;
$db = $db->write();

$stm = $db->query("SELECT log_id FROM `broadcast_failure_log` WHERE `time` <= $time_threshold ORDER BY log_id ASC LIMIT 1");
if($stm === false){
    echo "query failed!\r\n";
    exit(1);
}
$start_log_id = $stm->fetchColumn();
if($start_log_id === false){
    echo "query failed when fetch first log id.\r\n";
    exit(1);
}

echo "获取到起始日志ID: $start_log_id\n";

while(true){
    $upper_log_id = $start_log_id + DELETE_STEPS;
    echo "开始删除log_id 小于等于 $upper_log_id 的日志...\n";
    $sqlDel = "DELETE FROM `broadcast_failure_log` WHERE `time` <= $time_threshold AND log_id <= $upper_log_id";
    echo $sqlDel, "\n";
    $st = microtime(true);
    $n = $db->exec($sqlDel);
    if($n === false){
        echo "delete failed! SQL: $sqlDel\r\n";
        exit(1);
    } else if($n === 0){
        echo "clean completed!\r\n";
        exit(0);
    } else{
        $time_elapsed = sprintf("%.2f ms", (microtime(true) - $st) * 1000);
        $start_log_id += $n;
        echo "本次成功删除 $n 条, 耗时($time_elapsed). 尝试继续从 log id: $start_log_id 开始删除".DELETE_STEPS." 日志...\n";
        $sleep = rand(5, 12);
        echo "暂停 $sleep 秒...\n";
        sleep($sleep);
    }
}