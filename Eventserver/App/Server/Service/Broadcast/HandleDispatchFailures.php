<?php
namespace App\Server\Service\Broadcast;

use Lib\Util\Broadcast as util;
use App\Server\Cfg\Service as CS;
use App\Server\Model\BroadcastFailureLog as MBF;
use App\Server\Model\Subscription as MS;

/**
 * Handle messages that failed being sent to subscribers.
 * @author Su Chao<suchaoabc@163.com>
 */
class HandleDispatchFailures extends \Lib\BaseService
{
    /**
     * coefficent of retry interval
     * @var int
     */
    const RETRY_INTERVAL_CE = 120;
    public function execute()
    {
        $tubeName = CS::TUBE_EVENT_CENTER_MESSAGES_SENT_FAILURE;
        $bt = \Lib\Beanstalk::instance();
        $bt->watch($tubeName);
        $job = $bt->reserve(1);
        if(!$job)
        {
            return false;
        }
        $failureLog = util::unserialize($job->getData());
        if(empty($failureLog->log_id) || empty($failureLog->job_id) || empty($failureLog->message) || empty($failureLog->subscription))
        {//bury broken messages
            $bt->bury($job);
            throw new \Lib\TransactionException('Illformed failure log message data: '.var_export($failureLog,true).' .',61101);
        }
        try
        {
           if(!isset($failureLog->retry_times))
           {
               $failureLog->retry_times = 1;
           }
            // 获取最新订阅登记信息
            // @todo 是否确实需要使用最新订阅登记信息
           $subscription = MS::instance()->getByPrimaryKeyWithCache($failureLog->subscription['subscription_id']);
           if($subscription)
           {
               $failureLog->subscription = $subscription;
           }
           $result = util::dispatch($failureLog->job_id, $failureLog->message, $failureLog->subscription, $failureLog->log_id, $failureLog->retry_times);
           $sendSuccess = $result['success'];
        }
        catch(\Exception $e)
        {
            $bt->release($job);
            throw $e;
        }
        $bt->delete($job);
        if($sendSuccess)
        {
            MBF::instance()->setFinalStatus($failureLog->log_id, 1, 0);
            return true;
        }
        else 
        {
            $logInfo = MBF::instance()->getByPrimaryKey($failureLog->log_id);
            //@todo currently hackin, $logInfo may be cache for unknown reason. so compare then and get the larger to fix it.
            $retryTimes = max($failureLog->retry_times, $logInfo['retry_times']);
            $fallSuccess = false;
            //put to the end of the queue for the next retry
            if($retryTimes < CS::MAX_MESSAGE_SEND_RETRY_TIMES)
            {
                $bt->useTube($tubeName);
                $coefficent = self::RETRY_INTERVAL_CE;
                if(defined('\App\Server\Cfg\Service::RETRY_INTERVAL_CE'))
                {
                    $coefficent= CS::RETRY_INTERVAL_CE;
                }
                $failureLog->retry_times = $retryTimes+1;
                //delay for retry
                $fallSuccess = $bt->put(util::serialize($failureLog),1025, pow($failureLog->retry_times, 2)*$coefficent);
            }
            MBF::instance()->setFinalStatus($failureLog->log_id, 0, (int) ($fallSuccess > 0));
            return false;
        }
    }
}
