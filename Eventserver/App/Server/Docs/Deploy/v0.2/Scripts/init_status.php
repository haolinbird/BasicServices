<?php
require __DIR__.'/../../../../constants.php';
require SYS_ROOT . 'Lib/Autoloader.php';
Lib\Autoloader::setNamespacePrefixPath ( SYS_ROOT );
spl_autoload_register ( array ('Lib\Autoloader','loadByNamespace') );
$max_retry_times = App\Server\Cfg\Service::MAX_MESSAGE_SEND_RETRY_TIMES;
$sql = 'UPDATE broadcast_failure_log SET alive=0 WHERE `alive`=1 AND retry_times >= '.$max_retry_times;
$sql2 = 'UPDATE broadcast_failure_log SET alive=0 WHERE `alive`=1 AND final_status=1';
$db = new Lib\Db;
if($db->write()->exec($sql) && $db->write()->exec($sql2))
{
    echo 'success'."\r\n";
    exit(0);
}

echo 'Failed!'."\r\n";
exit(1);