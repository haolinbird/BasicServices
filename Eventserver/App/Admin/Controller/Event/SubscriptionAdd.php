<?php
/**
 * Class Subscriptionadd
 * @author Haojieh<haojieh@jumei.com>
 */
namespace App\Admin\Controller\Event;
use App\Admin\Controller\Auth\Base;
use App\Admin\Model;

class SubscriptionAdd extends SubscriptionBase
{
    public function execute()
    {
        \MedApi\Client::config((array)\Lib\Util\Sys::getAppCfg("MedApi"));
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $res = array('code' => 'error', 'message' => array());
            $formalized = $this->validateInpputData();
            if(!empty($formalized['errors'])){
                $res['message'] = $formalized['errors'];
                \Lib\Util\Response::json($res);
                return;
            }
            $data = $formalized['data'];
            $subParams = $formalized['subParams'];

            $messageClassIds = $this->requestParams->getPost('message_class_ids');
            foreach ($messageClassIds as $m) {
                if(!Model\Event\Message::instance()->exists($m, 'class_id')) {
                    $res['message'][] = '非法的消息类型id';
                    break;
                }
                $data['message_class_id'] = $m;
                $subs = Model\Event\Subscription::instance()->getSubscriptionList(array('s.subscriber_id'=>$data['subscriber_id']), 1, 99999);
                $subExists = false;
                foreach ( $subs['data'] as $sub)
                {
                    if($data['message_class_id'] == $sub['message_class_id'] && $sub['reception_channel'] == $data['reception_channel'])
                    {
                        $subExists = true;
                    }
                }
                if($subExists){
                    $res = array('code'=>'success', 'message'=>'添加成功!');
                } else{
                    $subscriptionId = Model\Event\Subscription::instance() ->insert($data);
                    if($subscriptionId){
                        try{
                            // 注意api调用顺序.
                            \MedApi\Client::call("SetSubscriptionParams",
                                array('SubscriptionId' => (int)$subscriptionId,
                                    'Params' => $subParams
                                )
                            );
                            Model\Event\Subscription::instance()->saveSubscriptionParams($subscriptionId, $subParams);
                            // 同步订阅信息到压测环境.
                            // Model\Event\Subscription::instance()->syncSubscriptionInfoToBench($subscriptionId);
                            $res = array('code' => 'success', 'message' => '添加成功!');
                        }catch(\Exception $ex){
                            $res = array('code'=>'error', 'message'=>$ex->getMessage());
                            break;
                        }
                    } else {
                        $res = array('code' => 'error', 'message' => '保存失败!');
                        break;
                    }
                }
            }

            if($res['code'] == 'success'){
                try {
                    \MedApi\Client::call("ClearDataCache");
                }catch(\Exception $ex){
                    $res = array('code' => 'error', 'message' => $ex->getMessage());
                }
            }
            \Lib\Util\Response::json($res);
            return;
        }

        \MedApi\Client::call('GetDefaultSubscriptionSettings');
        $defaultParams = \MedApi\Client::getLastCallResultFromCenterNode();
        $subscribers = Model\Event\Subscriber::instance()->getListByCond(array('status' => 0));
        $messageClasses    = Model\Event\Message::instance()->getMsgList(array(), 1, 2000);
        $messageClasses  = $messageClasses['data'];
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

        $tpl = $this->getTemplate();
        $tpl->assign(array(
            'subscribers'    => $sortedSubscribers,
            'messageClasses'       => $sortedMessageClasses,
            'defaultTimeOut' => \App\Server\Cfg\Service::MAX_MESSAGE_DELIVERY_TIME,
            'defaultParams'  => $defaultParams
        ));

        $tpl->display();
    }
}

