<?php
/**
 * Class Logdetail 
 * @author Haojieh<haojieh@jumei.com>
 */
namespace App\Admin\Controller\Event;

use App\Admin\Controller\Auth\Base;
use \App\Admin\Model;

class LogDetail extends Base {

    public function execute() {

        $logId = $this->requestParams->getGet('log_id');

        $data = Model\Event\Log::instance()->getLogDetail($logId);
        $tpl        = $this->getTemplate();
        $tpl->assign(array('info' => $data));
        $tpl->display();
    }
}

