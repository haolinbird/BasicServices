<?php
namespace Handler;

use Lib\Util\Broadcast as util;
use Lib\Log as Logger;
use App\Server\Cfg\Service as CS;
use App\Server\Model\MessageClasses as MMC;
use App\Server\Model\Subscriber as MS;

class Broadcast implements \Provider\Broadcast\BroadcastIf{
    /**
     * @param string ME user key.
     * @param string ME user secret key.
     * @param string $messageClassKey
     * @param multiple $message
     * @param int $priority
     *            [optional] lower means higher priority. default is 1.
     * @param int $timeToDelay
     *            [optional] seconds to delay sending the message, if less than
     *            1 second means sending it immediately. default is 0.
     * @param string $senderKey  name key of the sender. this param is currently used for recovering data only
     * @return bool
     * @throws \Lib\TransactionException
     */
    public function send($senderKey, $secretKey, $messageClassKey, $message, $priority = 1, $timeToDelay = 0)
    {
        // 压测环境.
        global $context, $owl_context;

        $this->logIncoming(array('sender' => $senderKey,
                                 'messageClass' => $messageClassKey,
                                 'message' => $message,
                                 'priority' => $priority,
                                 'delay' => $timeToDelay
            ));

        $modelSubscriber = MS::instance();
        $subscriber = $modelSubscriber->getNormalSubscriberWithCache($senderKey);
        if(!$subscriber)
        {
            throw new \Lib\TransactionException('Sender ('.$senderKey.') does not exists.', 61002 );
        }

        if($subscriber['secret_key'] != $secretKey){
            throw new \Lib\TransactionException('Sender ('.$senderKey.') credential error.', 61012);
        }

        if(!$modelSubscriber->canSendMessageWithCache($senderKey, $messageClassKey))
        {
            throw new \Lib\TransactionException('Sender ('.$senderKey.') is not allowed to send this class('.$messageClassKey.') of message.', 61003);
        }

        $modelMsgCls = new MMC();
        if (!$modelMsgCls->fetchColumWithCache('class_id', array('class_key' => $messageClassKey)))
        {
            throw new \Lib\TransactionException('Bad message class key.('.$messageClassKey .')', 61001 );
        }

        // 生成一个rpc节点, 串联: 上游节点 -> 消息中心 -> 下游接收者.
        \MNLogger\TraceLogger::instance()->RPC_CS('unknown', 'mec.beanstalk', 'put', null);

        $tubeName = CS::TUBE_EVENT_CENTER_MESSAGES;

        $job = new \stdClass ();
        $job->msgKey = $messageClassKey;
        $job->body = $message;
        $job->time = microtime ( true );
        $job->sender = $senderKey;
        $job->priority = $priority;
        $job->delay = $timeToDelay;
        
        if (isset($context) && ! empty($context)) {
            $job->context = http_build_query($context);
        }

        if (isset($owl_context) && ! empty($owl_context)) {
            $job->owl_context = http_build_query($owl_context);
        }

        // 区分压测环境和正式环境的beanstalk/redis配置.
        if (isset($context['X-Jumei-Loadbench']) && $context['X-Jumei-Loadbench'] == 'bench') {
            $endpoint = 'default_bench';
        } else {
            $endpoint = 'default';
        }

        $job = (array)$job;

        //two very important vars, all
        $e = $btReturn = $failureLogged = false;

        $serializedJob = util::serialize($job);

        try
        {
            $bt = \Lib\Beanstalk::instance($endpoint);
            $bt->useTube($tubeName );
            $btReturn = $bt->put($serializedJob, $priority, $timeToDelay );
        }
        catch(\Exception $e)
        {
            $btReturn = false;
            Logger::instance('beanstalkErrors')->log($e->__toString());
            Logger::instance('serverNotices')->log('Failed to sent message to queue.', E_USER_WARNING);

            \MNLogger\EXLogger::instance()->log($e);
        }

        if ($btReturn) {
            \MNLogger\TraceLogger::instance()->RPC_CR(\MNLogger\Base::T_SUCCESS, 0);
        } else {
            if ($e instanceof \Exception) {
                \MNLogger\TraceLogger::instance()->RPC_CR(\MNLogger\Base::T_EXCEPTION, strlen($e->getTraceAsString()), $e->getTraceAsString());
            } else {
                \MNLogger\TraceLogger::instance()->RPC_CR(\MNLogger\Base::T_EXCEPTION, strlen('Failed to sent message to queue.'), 'Failed to sent message to queue.');
            }
        }

        //log messages to redis for solid storage
        if((defined('\App\Server\Cfg\Service::ENABLE_IN_SUCCESS_LOG') && CS::ENABLE_IN_SUCCESS_LOG) || (defined('\App\Server\Cfg\Service::ENABLE_IN_FAILURE_LOG') && CS::ENABLE_IN_FAILURE_LOG))
        {
            $redis = null;
            try
            {
                $redis = \Lib\Redis::instance($endpoint);
            }
            catch(\Exception $e)
            {
                Logger::instance('redisErrors')->log($e->__toString());
            }

            if($redis)
            {
                $keySuffix = ':'.date('Y-m-d-').intval(date('H')/5);
                //record info about messages successfully sent to queue
                if($btReturn !== false && defined('\App\Server\Cfg\Service::ENABLE_IN_SUCCESS_LOG') && CS::ENABLE_IN_SUCCESS_LOG && defined('\App\Server\Cfg\Service::MESSAGE_IN_LOG_SUCCESS_KEY'))
                {
                    try
                    {
                        $redis->lPush(CS::MESSAGE_IN_LOG_SUCCESS_KEY . $keySuffix, $serializedJob);
                    }
                    catch(\Exception $e)
                    {
                        Logger::instance('redisErrors')->log($e->__toString());
                        Logger::instance('serverNotices')->log('Failed to sent message to redis success list.');
                        Logger::instance('riskyMessages')->log('success '.$serializedJob);
                    }
                }

                //record info about messages failed sent to queue
                if($btReturn === false && defined('\App\Server\Cfg\Service::ENABLE_IN_FAILURE_LOG') && CS::ENABLE_IN_FAILURE_LOG && defined('\App\Server\Cfg\Service::MESSAGE_IN_LOG_FAILURE_KEY'))
                {
                    try
                    {
                        $failureLogged = $redis->lPush(CS::MESSAGE_IN_LOG_FAILURE_KEY . $keySuffix, $serializedJob);
                    }
                    catch(\Exception $e)
                    {
                        $failureLogged = false;
                        Logger::instance('redisErrors')->log($e->__toString());
                        Logger::instance('serverNotices')->log('Failed to sent message to redis failure list.');
                        Logger::instance('riskyMessages')->log('failure '.$serializedJob);
                    }
                }
            }
            else if(!$btReturn)
            {
                Logger::instance('riskyMessages')->log('failure '.$serializedJob);
            }
        }
        //return true if message sent to message queue of redis
        return $btReturn !== false || $failureLogged !== false;
    }


    /**
     * log incoming messages
     * @param array $message
     */
    protected function logIncoming($message) {
        $cfg = \Lib\Util\Sys::getAppCfg('Service');
        if ($cfg::ENABLE_INCOMING_MESSAGE_LOG) {
            \Lib\Log::instance ( 'inComingMsg' )->log ($message);
        }
    }
}