<?php
/**
 *  failure fallback up  Send service. Store messages to local disk when normal service are all down.
 *  ROUTE TO THIS SCRIPT ONLY THE PREVIOUS NORMAL RPC SERVER ALL DOWN!
 */

require __DIR__.'/../constants.php';
require SYS_ROOT . 'Lib/Autoloader.php';
Lib\Autoloader::setNamespacePrefixPath ( SYS_ROOT );
spl_autoload_register ( array (
'Lib\Autoloader',
'loadByNamespace'
) );

class RpcServerFailbackup extends Lib\RpcServer
{
    public function serve()
    {
        ob_start();
        parent::serve();
        if(!empty($this->returnData['Exception']))
        {
            return true;
        }

        $logdir = '/home/www/logs/';
        if(!is_writable($logdir))
        {
            $logdir = '/tmp/logs/';
        }
        if(!is_dir($logdir))
        {
            mkdir($logdir, 0755);
        }
        $logFile = 'event.rpc.fail.'.date('YmdH').'.log';
        $logFile = $logdir.$logFile;
        $content = date('Y-m-d H:i:s').
                    "\n".'****-****'.
                    "\n".var_export($_GET, true).
                    "\n".'****=****'.
                    var_export($_POST, true).
                    "\n".'****+****'."\n";
        $re = file_put_contents($logFile, $content, FILE_APPEND);
        ob_clean();
        if(!$re)
        {
            $this->assembleException(new \Lib\RpcServerException('Failed to send message, try again later!'));
            $this->returnData['return'] = false;
        }
        $this->response();
    } 
}

RpcServerFailbackup::instance()->setDryRun()->serve();