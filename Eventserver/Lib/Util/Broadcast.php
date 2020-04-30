<?php
namespace Lib\Util;

use \App\Server\Cfg\Service as CS;
use App\Server\Model\BroadcastFailureLog as MBF;

use App\Server\Helper\FailureTimes as FailureAlerter;

/**
 * Common functions and processes of BROADCAST
 * 
 * @author Su Chao<suchaoabc@163.com>
 */
class Broadcast{
    /**
     * available serializers
     * @var array
     */
    protected static $availableSerializers = array('json','msgpack');
    
    /**
     * name of serializer
     * @var string
     */
    protected static $serializer=null;
    
    /**
     * get the "compress" flag that should be append ahead of the serialized message data
     * @return string
     */
    public static function getDefinedCompressFlag()
    {
        return pack("H3cc",0xCD,66,74);
    }
    
    /**
     * set serializer for the message data
     * @param string $serializer
     */
    protected static function setSerializer($serializer)
    {
        if(empty($serializer))
        {
            $serializer = CS::EVENT_MESSAGE_SERIALIZER;
        }
        if(!in_array($serializer, self::$availableSerializers))
        {
            $serializer = null;
        }
        self::$serializer = $serializer;
    }

    public static function serialize($data,$serializer=null)
    {
        self::setSerializer($serializer);
        switch (self::$serializer)
        {
            case 'json':
                $data = json_encode($data);
                continue;
            case 'msgpack' :
                $data = msgpack_pack($data);
                continue;
            default:
                continue;
        }
        return self::compress($data);
    }

    public static function unserialize($data,$serializer=null)
    {
        self::setSerializer($serializer);
        $data = self::uncompress($data);
        switch (self::$serializer)
        {
            case 'json':
                $data = json_decode($data);
                continue;
            case 'msgpack' :
                $data = msgpack_unpack($data);
                continue;
            default:
                continue;
        }
        return $data;
    }
    
    public static function uncompress($data)
    {
        $compressFlag = self::getDefinedCompressFlag();
        $testFlag = substr($data, 0, strlen($compressFlag));
        if($testFlag == $compressFlag)
        {
            $data = substr_replace ($data, '', 0 , strlen($testFlag));
            return gzinflate($data);
        }
        return $data;
    }
    
    public static function compress($data)
    {
        if(!defined('CS::COMPRESS_MESSAGE') || !CS::COMPRESS_MESSAGE)
        {
            return $data;
        }
        return self::getDefinedCompressFlag().gzdeflate($data, 9);
    }
    
    /**
     * push message to the subsciber.
     * @param array $jobId job id of the job from the message queue
     * @param array $message
     * @param array $subscription
     * @param int $failureLogId if it's a valid failure log ID then it should be re-dispatch from the failure queue.
     * @param int $retryTimes the times of re-sending the same message to the subscriber
     * @return boolean
     */
    public static function dispatch($jobId,$message,$subscription,$failureLogId=null, $retryTimes=0)
    {
        $messageBody = json_encode($message->body);
        $uniqueJobId = $message->time.'-'.$jobId.'-'.$subscription['subscription_id'];
        $urlRaw = parse_url($subscription['reception_channel']);
        $queryStr = array('jobid'=>$uniqueJobId, 'retry_times'=>$retryTimes);
        $queryStr = http_build_query($queryStr);
        if(!empty($urlRaw['query']))
        {
             $queryStr = $urlRaw['query'].'&'.$queryStr;
        }
        $url = $urlRaw['scheme'].'://';
        if(!empty($urlRaw['user'])){
            $url .= $urlRaw['user'];
        }
        if(!empty($urlRaw['password'])){
            $url .= ':'.$urlRaw['password'].'@';
        }
        $url .= $urlRaw['host'];
        if(!empty($urlRaw['port'])){
            $url .= ':'.$urlRaw['port'];
        }
        if(!empty($urlRaw['path'])){
            $url .= $urlRaw['path'];
        }
        if(!empty($queryStr)){
            $url .= '?'.$queryStr;
        }
        if(!empty($urlRaw['fragment'])){
            $url .= '#'.$urlRaw['fragment'];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('message'=>$messageBody, 'retry_times'=>$retryTimes,'jobid'=>$uniqueJobId)));
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, ($subscription['timeout'] > 0 && $subscription['timeout'] >= CS::MAX_MESSAGE_DELIVERY_TIME) ? (int) $subscription['timeout'] : CS::MAX_MESSAGE_DELIVERY_TIME);
        //loging...
        $startTime = microtime(true);
        $logStr = '('.posix_getpid().')start to send message '.$message->msgKey.".\n";
        $logStr .= 'job id:'.$jobId."\n";
        $logStr .= 'committed time:'.date('Y-m-d H:i:s',$message->time)."\n";
        $logStr .= 'by:'.$message->sender."\n";
        $logStr .= 'subscriber id:'.$subscription['subscriber_id']."\n";
        $logStr .= "body:\n".$messageBody."\n";
        $logStr .= "body end\n";
        $logStr .= "Sending to {$subscription['reception_channel']}.....\n";
        /////
        $return = $returnRaw = trim(curl_exec($ch));
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $hasError = false;
        if(false !== $return)
        {
            $return = json_decode(trim($return), true);
        }
        if($httpCode != 200 || empty($return) || $return['status'] != 1 )
        {
            $logStr .= 'Send failure. HTTP code:'.$httpCode."\n";
            $hasError = true;
            $errorMsg = 'Code:'.$httpCode.' content: '.$returnRaw;
            $mBFL = MBF::instance();
            $logId = $mBFL->logFailure($message, $subscription,$errorMsg,$failureLogId, 0, $uniqueJobId);
            //push into the failure log tube,if the failure appears for the firsttime.
            if(!$mBFL->lastFailureIsRepeated())
            {//@todo failures when put to "sent failure queue"
                $bt = \Lib\Beanstalk::instance();
                $failureLogTube = CS::TUBE_EVENT_CENTER_MESSAGES_SENT_FAILURE;
                $bt->useTube($failureLogTube);
                $failureLog = new \stdClass();
                $failureLog->log_id = $logId;
                $failureLog->job_id = $jobId;
                $failureLog->message = $message;
                $failureLog->subscription = $subscription;
                $fallSuccess = $bt->put(\Lib\Util\Broadcast::serialize($failureLog), 1025, CS::RETRY_INTERVAL_CE);
                if($fallSuccess)
                {
                    $mBFL->setFinalStatus($logId, 0, 1);
                }
            }
        }
        else
        {
            if($failureLogId)
            {//failed message sent successfully this time
                $mBFL = MBF::instance();
                $logId = $mBFL->logFailure($message, $subscription,'',$failureLogId,1, $uniqueJobId);
            }
            $logStr .= 'Message send successfully.'."\n";
        }
        $logStr .= 'Time consumed: '.sprintf('%.4f',(microtime(true)-$startTime)*1000)."ms\n\n";
        self::outPutLog($logStr);
        if($hasError)
        {// 短信报警
            FailureAlerter::instance()->logIncreaseMessageFailureTimes($message->msgKey, $subscription['reception_channel']);
            $lastFailureTimeInfo = FailureAlerter::instance()->getLogRecentMessageFailureInfo($message->msgKey, $subscription['reception_channel']);
            if($lastFailureTimeInfo)
            {
                FailureAlerter::instance()->alertBySms($message->msgKey, $subscription['reception_channel'], 'Worker最后返回:'.$returnRaw);
            }

            if($retryTimes >= CS::MAX_MESSAGE_SEND_RETRY_TIMES-3)
            {
                $timeLeft = sprintf('%.1f', static::countTimeleftForFixing($retryTimes)/60);
                FailureAlerter::instance()->alertBySms($message->msgKey, $subscription['reception_channel'], '消息('.$message->msgKey.')推送( '.$subscription['reception_channel'].' ) 次数('.($retryTimes+1).')即将超限('.(CS::MAX_MESSAGE_SEND_RETRY_TIMES+1).')，您还有接近'.$timeLeft.'分钟来修复。'."\n日志ID：{$failureLogId}\n".'Worker最后返回('.$httpCode.'):'.$returnRaw, true, true);
            }
        }
        return array('success' => !$hasError, 'last_client_return' => array('code' => $httpCode, 'content' => $returnRaw));
    }

    /**
     * output log
     * @param string $message
     */
    public static function outPutLog($message)
    {
        if(CS::ENABLE_OUTPUT_LOG)
        {
            \Lib\Log::instance('messageSentLogOutput')->log($message);
        }
    }

    public static function countTimeleftForFixing($retryTimes)
    {
        $total = 0;
        for($count=$retryTimes+1; $count <= CS::MAX_MESSAGE_SEND_RETRY_TIMES+1; $count++)
        {
            $total += pow($count, 2)*CS::RETRY_INTERVAL_CE;
        }
        return $total;
    }
}
