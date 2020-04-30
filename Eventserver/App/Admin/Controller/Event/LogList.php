<?php
namespace App\Admin\Controller\Event;

use App\Admin\Controller\Auth\Base;
use \App\Admin\Model;

class LogList extends Base {

    public function execute() {
        $maxRetryTimes = \App\Server\Cfg\Service::MAX_MESSAGE_SEND_RETRY_TIMES;
        $pageNo     = $this->requestParams->toInt('page_no');
        $viewCfg    = \Lib\Util\Sys::getAppCfg('View');
        $alive      = $this->requestParams->getGet('alive');
        $finalStatus = $this->requestParams->getGet('final_status');
        $cond = array();
        $subscriberKey = $this->requestParams->getGet('subscriber_key');
        $classKey      = $this->requestParams->getGet('class_key');
        $retryTimes    = $this->requestParams->getGet('retry_times');
        if(!empty($subscriberKey)){
            $cond['subscriber_key'] = $subscriberKey;
        }
        if(!empty($classKey)){
            $cond['class_key'] = $classKey;
        }

        if(!is_null($retryTimes) && $retryTimes != ''){
            $cond['retry_times'] = $retryTimes;
        }
        $cond['first_push_time_start'] = strtotime($this->requestParams->getGet('first_push_time_start'));
        $cond['first_push_time_end'] = strtotime($this->requestParams->getGet('first_push_time_end'));
        if(!is_null($alive) && $alive != '*' && $alive !== '' && in_array($alive, array(1,0)))
        {
            $cond['alive'] = ($alive = (int) $alive);
        }
        if(!is_null($finalStatus) && $finalStatus != '*' && $finalStatus !=='' && in_array($finalStatus, array(1,0)))
        {
            $cond['final_status'] = ($finalStatus = (int) $finalStatus);
        }
        $rowsPerPage = $this->requestParams->rows_per_page ? (int) $this->requestParams->rows_per_page :  $viewCfg::DEFAULT_ROWS_PER_PAGE;
        $result     = Model\Event\Log::instance()->getLogList($cond, $pageNo, $rowsPerPage);
        $paging     = \Lib\Util\View::generatePagingParams($result['count'], $rowsPerPage, $pageNo);
        $tpl        = $this->getTemplate();
        /*$hosts = \Lib\Util\Sys::getAppCfg('Beanstalk');
        $rc = new \ReflectionClass($hosts);
        $hosts = $rc->getStaticProperties();*/
        $beanstalkCfg = \Lib\Util\Sys::getAppCfg('Beanstalk');
        $hosts = $beanstalkCfg::$servers;
        $params = array(
            'maxRetryTimes' => $maxRetryTimes,
            'list'           => $result['data'],
            'query'          => $cond,
            'paging'         => $paging,
            'count'          => $result['count'],
            'messageLists' => isset($_SESSION['message']) ? $_SESSION['message'] : null,
            'hosts'         =>  $hosts
        );
        unset($_SESSION['message']);
        $tpl->assign($params, NULL);
        $tpl->display();
    }
}

