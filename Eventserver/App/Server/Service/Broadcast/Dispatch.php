<?php
namespace App\Server\Service\Broadcast;

use Lib\Util\Broadcast as util;
use App\Server\Cfg\Service as CS;
use App\Server\Model\Subscription as MS;

/**
 * Delievers queued broadcast messages to subscribers.
 * @author Su Chao<suchaoabc@163.com>
 */
class Dispatch extends \Lib\BaseService{
    public function execute()
    {
        $tubeName = CS::TUBE_EVENT_CENTER_MESSAGES;
        $bt = \Lib\Beanstalk::instance();
        $bt->watch($tubeName);
        $job = $bt->reserve(1);
        if(!$job)
        {
            return false;
        }
        $message =(object) util::unserialize($job->getData());
        if(empty($message->msgKey) || !property_exists($message, 'body') || empty($message->sender))
        {
            //bury broken messages
            $bt->bury($job);
            throw new \Lib\TransactionException('Illformed message data'.var_export($message,true).' .',61100);
        }
        $mSubscription = new MS();
        $subscriptions = $mSubscription->getSubscriptionsByMessageKeyWithCache($message->msgKey, true);
        try
        {
            foreach($subscriptions as $subscription)
            {
               $result = util::dispatch($job->getId(),$message,$subscription);
            }
        }
        catch(\Exception $e)
        {
            $bt->release($job);
            throw $e;
        }
        $bt->delete($job);
        return true;
    }
}
