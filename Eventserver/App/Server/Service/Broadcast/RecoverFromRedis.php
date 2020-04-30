<?php
namespace App\Server\Service\Broadcast;

use Lib\Util\Broadcast as util;
use App\Server\Cfg\Service as CS;

/**
 * try recover messages that had failed been sent to message queue from redis and push to it the queue again. 
 * 
 * @author Su Chaos<suchaoabc@163.com>
 * @todo currently not well intergrated with the upper daemon
 */
class RecoverFromRedis extends \Lib\BaseService
{
    /**
     * interval for trying to get LOCK for recovering process
     * @var int  in seconds
     */
    const RETRIEVE_LOCK_INTERVAL = 15;

    /**
     * interval of re-checking the messages in redis list
     * @var int in seconds
     */
    const RECHECK_FAILURE_QUEUE_INTERVAL = 10;

    /**
     * interval of acessing redis.For control CPU consumption.
     * @var int in millisencods
     */
    const CYCLING_REDIS_INTERVAL = 10;

    protected $keyPrefix;

    protected $keyOfLock;

    /**
     * @var \Lib\Redis;
     */
    protected $redis;
    
    public function execute()
    {
        $this->redis = \Lib\Redis::instance();
        $this->keyPrefix = CS::MESSAGE_IN_LOG_FAILURE_KEY;
        //get mutex key for recovering process to avoid duplicated messages;
        $this->keyOfLock = $this->keyPrefix.'_recover_lock';
        switch(PHP_SAPI)
        {
            case 'cli':
                $this->processInCli();
                continue;
            default:
                $this->processInNonCli();
        }
    }
    
    protected function confirmRedisLink()
    {
        $return = $this->redis->ping() !== false;
        if(!$return)
        {
             throw new \Lib\RedisException('Redis connection is broken', 32000);
        }
        return $return;
    }
    protected function getLock()
    {
        $this->confirmRedisLink();
        $result = $this->redis->setnx($this->keyOfLock, microtime(true));
        return $result;
    }
    
    protected function releaseLock()
    {
        return $this->redis->delete($this->keyOfLock);
    }
    /**
     * for long running
     */
    protected function processInCli()
    {
        //sleeps may plus Cfg\Service::BROADCAST_INTERVAL
        if(!$this->processInNonCli())
        {
            //10 seconds
            usleep(self::RETRIEVE_LOCK_INTERVAL * 1000000);
        }
        else 
        {
            //10 seconds
            usleep(self::RECHECK_FAILURE_QUEUE_INTERVAL * 1000000);
        }
    }
    
    /**
     * for one-time run
     */
    protected function processInNonCli()
    {
        if(!$this->getLock())
        {
            return false;
        }
        $keys = $this->redis->keys($this->keyPrefix.':*');
        $sreviceSend = new Send();
        foreach ($keys as $key)
        {
            while(false !== ($message = $this->redis->lPop($key)))
            {
                $msgObj = util::unserialize($message);
                if(empty($msgObj))
                {
                    \Lib\Log::instance('serverNotices')->log('Failed to recover message:'.$message);
                }
                else 
                {
                    try{
                        $sreviceSend->execute($msgObj->msgKey, $msgObj->body, null, null, $msgObj->sender);
                    }
                    catch(\Exception $ex)
                    {
                        $this->releaseLock();
                        throw $ex;
                    }
                }
                usleep(self::CYCLING_REDIS_INTERVAL * 1000);
            }
        }
        $this->releaseLock();
        return true;
    }
}
