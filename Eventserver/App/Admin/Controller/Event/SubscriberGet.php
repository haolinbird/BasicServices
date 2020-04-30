<?php
/**
 * Class Subscriberget 
 * @author Haojieh<haojieh@jumei.com>
 */
namespace App\Admin\Controller\Event;
use App\Admin\Controller\Auth\Base;
use App\Admin\Model;

class SubscriberGet extends Base
{
    public function execute()
    {
        $cond = array(
            'subscriber_key' => $this->requestParams->getPost('subscriber_key'),
        );

        // $subscribers = Model\Event\Subscriber::instance()->getOne($cond);
        // $allowedClasses = explode('|', $subscribers['allowed_message_class_to_send']);
        $addedClasss = Model\Event\Subscription::instance()->getAddedSubscriberMessages($cond);
        header("Content-Type:text/javascript;charset:utf-8;");
        echo json_encode($addedClasss);
    }
}

