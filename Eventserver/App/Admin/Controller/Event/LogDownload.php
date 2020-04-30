<?php
namespace App\Admin\Controller\Event;

use App\Admin\Controller\Auth\Base;

class LogDownload extends Base {

    public function execute() {
        $log = $this->requestParams->log;
        if(!isset($_SESSION['message'])) $_SESSION['message'] = array();
        if(!empty($log))
        {
            $log = base64_decode($log);
            if(!is_readable($log))
            {
                $_SESSION['message']['alert alert-error'][] = '日志文件无法访问, 请检查权限！';
            }
            else 
            {
                $f = fopen($log, 'r');
                ob_clean();
                header('Content-type: application/octet-stream;');
                header('Content-disposition: attachment; filename="'.basename($log).'"');
                while($str = fread($f, 4096))
                {
                    ob_start();
                    echo $str;
                    ob_flush();
                    flush();
                }
                die;
            }
        }
        $cfgs = \Lib\Util\Sys::getAppCfg('Log', 'Server');
        $logs = \Lib\Util\Os::listFiles($cfgs::FILE_LOG_ROOT);
        //potential not accessable error for this log
        $logNginx = \Lib\Util\Os::listFiles('/var/log/nginx');
        $logs['files'] = array_merge($logs['file'], $logNginx['file']);
        $tpl        = $this->getTemplate();
        $tpl->assign('logs', $logs);
        $tpl->assign('messageLists', $_SESSION['message']);
        $tpl->display();
        unset($_SESSION['message']);
    }
}

