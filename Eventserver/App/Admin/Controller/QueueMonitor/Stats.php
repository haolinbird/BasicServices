<?php
/**
 * Class Stats 
 * @author Haojie Huang<haojieh@jumei.com>
 */

namespace App\Admin\Controller\QueueMonitor;

use App\Admin\Controller\Auth\Base;
use \App\Admin\Model;

class Stats extends Base
{
    public function execute()
    {
        /*$hosts = \Lib\Util\Sys::getAppCfg('Beanstalk');
        $rc = new \ReflectionClass($hosts);
        $hosts = $rc->getStaticProperties();*/
        $beanstalkCfg = \Lib\Util\Sys::getAppCfg('Beanstalk');
        $hosts = $beanstalkCfg::$servers;
        $hostList = array();
        foreach($hosts as $gHosts){
            foreach($gHosts as $cHost){
                $hostList[] = $cHost;
            }
        }
        $bsm = new \Lib\BeanstalkMonitor($hostList);
        $host = $this->requestParams->host == '' ? null : $this->requestParams->host;
        $messageClass = $this->requestParams->messageClass == '' ? null : $this->requestParams->messageClass;
        $hostGroup = array();
        if(!is_null($host)){
            foreach($host as $k => $v){
                if(strpos($v, 'group-') === 0){
                    $v = explode('-', $v);
                    $hostGroup[] = $v[1];
                    unset($host[$k]);
                 }
            }
        }
        $tubesStats = $bsm->getTubesStats($host);
        $fields = array();
        foreach(\Lib\BeansTalkMonitor::$statsToDisplay as $v){
            $fields[$v] = \Lib\BeansTalkMonitor::$statsMapping[$v];
        }
        $tpl        = $this->getTemplate();
        $messageClasses = \App\Server\Model\MessageClasses::instance()->getList();
        $sortedMessageClasses = array();
        foreach($messageClasses as $v){
            $sortedMessageClasses[$v['class_key']] = $v;
        }
        uksort($sortedMessageClasses, array('\Lib\Util\String', 'uksortCB'));

        $subscribers =  Model\Event\Subscriber::instance()->getNames();
        $sortedSubscribers = array();
        foreach($subscribers as $subscriber){
            $sortedSubscribers[$subscriber['subscriber_key']] = $subscriber;
        }
        uksort($sortedSubscribers, array('\Lib\Util\String', 'uksortCB'));
        foreach($tubesStats as $k=>$ts){
            uksort($ts, array('\Lib\Util\String', 'uksortCB'));
            $tubesStats[$k] = $ts;
        }
        if($this->requestParams->getGet('subscriber_key')){
            $subscriptions = Model\Event\Subscription::instance()->getSubscriptionList(array('subscriber_key' => $this->requestParams->getGet('subscriber_key')), 1, 9999);
            $subscriptionIds = array();

            foreach($subscriptions['data'] as $sub){
                $subscriptionIds[] = $sub['subscription_id'];
            }
        }else {
            $subscriptionIds = null;
        }

        $tpl->assign(
            array(
                'hosts'     => $hosts,
                'bsm'       => $bsm,
                'bsPool'    => $bsm->getInstancePools(),
                'tubesStats' => $tubesStats,
                'fields'    => $fields,
                'host'      => $host,
                'hostGroup' => $hostGroup,
                'messageClass' => $messageClass,
                'messageClasses' => $sortedMessageClasses,
                'subscribers'    => $sortedSubscribers,
                'subscriberKey' => $this->requestParams->getGet('subscriber_key'),
                'subscriptionIds' => $subscriptionIds
            )
        );
        $tpl->display();
    }
}

