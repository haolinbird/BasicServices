<?php
/**
 * Class Subscriptionlist 
 * @author Haojieh<haojieh@jumei.com>
 */
namespace App\Admin\Controller\Event;
use App\Admin\Controller\Auth\Base;
use App\Admin\Model;

class SubscriptionList extends Base 
{
    public function execute() {
        $pageNo     = $this->requestParams->toInt('page_no');
        $_GET['page_no'] = $pageNo;
        $viewCfg    = \Lib\Util\Sys::getAppCfg('View');
        $cond = array(
            'subscriber_key' => $this->requestParams->getGet('subscriber_key'),
            'class_key'      => $this->requestParams->getGet('class_key'),
        );
        $subscribers =  Model\Event\Subscriber::instance()->getNames();
        $messageClasses = Model\Event\Message::instance()->getMsgList(array(), 1, 999999);
        $messageClasses = $messageClasses['data'];
        $filterCond = array_filter($cond, 'trim');
        $result     = Model\Event\Subscription::instance()->getSubscriptionList($filterCond, $pageNo, $viewCfg::DEFAULT_ROWS_PER_PAGE);
        $params     = array('list' => $result['data']);
        $rowsPerPage = $this->requestParams->toInt('rows_per_page');
        if($rowsPerPage < 1)
        {
            $rowsPerPage = $viewCfg::DEFAULT_ROWS_PER_PAGE;
        }
        $paging     = \Lib\Util\View::generatePagingParams($result['count'], $rowsPerPage, $pageNo);
        $tpl        = $this->getTemplate();

        $sortedSubscribers = array();
        foreach($subscribers as $subscriber){
            $sortedSubscribers[$subscriber['subscriber_key']] = $subscriber;
        }
        uksort($sortedSubscribers, array('\Lib\Util\String', 'uksortCB'));

        $sortedMessageClasses = array();
        foreach($messageClasses as $messageClass){
            $sortedMessageClasses[$messageClass['class_key']] = $messageClass;
        }
        uksort($sortedMessageClasses, array('\Lib\Util\String', 'uksortCB'));

        $params = array(
            'list'           => $result['data'],
            'query'          => $_GET,
            'paging'         => $paging,
            'count'          => $result['count'],
            'subscriber_key' => $this->requestParams->getGet('subscriber_key'),
            'class_key'      => $this->requestParams->getGet('class_key'),
            'subscribers'    => $sortedSubscribers,
            'message_classes' => $sortedMessageClasses,
        );

        $tpl->assign($params, NULL);
        $tpl->display();
    }
}
