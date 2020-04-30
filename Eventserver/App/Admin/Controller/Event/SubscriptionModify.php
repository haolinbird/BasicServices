<?php
/**
 * Class SubscriptionModify 
 * @author Haojieh<haojieh@jumei.com>
 */
namespace App\Admin\Controller\Event;
use App\Admin\Controller\Auth\Base;
use App\Admin\Model;

class SubscriptionModify extends Base
{
    public function execute()
    {
        $tpl        = $this->getTemplate();
        $subscriptionId= $this->requestParams->getGet('id');

        $subList = Model\Event\Subscriber::instance()->getNames();
        $data = Model\Event\Subscription::instance()->getSubscriptionDetail($subscriptionId);
        if(empty($data)){
            $tpl->assign('errorMessage', '不存在的订阅.');
            $tpl->display("Errors/error");
            return;
        }

        \MedApi\Client::config((array)\Lib\Util\Sys::getAppCfg("MedApi"));
        \MedApi\Client::call('GetSubscriptionParams', array('SubscriptionId' => (int)$subscriptionId));

        $subParams = Model\Event\Subscription::instance()->getSubscriptionParams($subscriptionId);
        $subParamsCached = \MedApi\Client::getLastCallResultFromCenterNode();
        if(!empty($subParams)){
            $subParamsCached = $subParams;
        }
        \MedApi\Client::call('GetDefaultSubscriptionSettings');
        $defaultParams = \MedApi\Client::getLastCallResultFromCenterNode();
        $tpl->assign(
            array(
                'subList'        => $subList,
                'subParams' => $subParamsCached,
                'rs'             => $data,
                'defaultParams'  => $defaultParams,
                )
            );
        $tpl->display();
    }
} 
