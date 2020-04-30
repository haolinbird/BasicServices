<?php
namespace App\Server\Model;
use App\Server\Cfg;
use Lib\Util\Broadcast as UB;
class Statistic extends \Lib\BaseModel{
    public function updateMessageList()
    {
        $redis = \Lib\Redis::instance();
        $statisticKey = Cfg\Service::EVENT_CENTER_SATISTIC_KEY;
        $listKeys = $redis->keys(Cfg\Service::MESSAGE_IN_LOG_SUCCESS_KEY.'*');
        $statistic =  $redis->hGetAll($statisticKey);
        if(!is_array($statistic))
        {
            $statistic = array('success_in' =>0, 'last_update'=>0);
        }
        if(!isset($statistic['success_in']))
        {
            $statistic['success_in'] = 0;
        }

        if(!isset($statistic['last_update']))
        {
            $statistic['last_update'] = 0;
        }

        $lastUpdateTime = $statistic['last_update'];

        foreach($listKeys as $key)
        {
            $keyLen = $redis->lLen($key);
            $listLastMessage = UB::unserialize($redis->lIndex($key, 0));
            $listFirstMessage = UB::unserialize($redis->lIndex($key, ($keyLen-1)));
            if($listFirstMessage->time > $lastUpdateTime)
            {//此列表还未统计过
                $statistic['success_in'] += $keyLen;
                $statistic['last_update'] = $listLastMessage->time;
            }
            else if($listFirstMessage->time < $lastUpdateTime && $listLastMessage->time < $lastUpdateTime)
            {//已经统计过，不再做统计.

            }
            else if($listFirstMessage->time <= $lastUpdateTime && $listLastMessage->time > $lastUpdateTime)
            {//已经统计过，但需重新遍历此列表还未统计过的消息
                $offsetStart = 0;
                $offsetEnd = $keyLen-1;
                //二分法查找最近一个时间戳小于或等于上次更新的元素
                while(true)
                {
                    if($offsetEnd - $offsetStart <=1)
                    {
                         if(UB::unserialize($redis->lIndex($key, $offsetStart))->time > $lastUpdateTime)
                         {
                             $index = $offsetStart;
                             break;
                         }
                         else
                         {
                             $index = $offsetEnd;
                             break;
                         }
                    }
                    $index = $offsetStart + intval(($offsetEnd - $offsetStart)/2);
                    if(UB::unserialize($redis->lIndex($key, $index))->time > $lastUpdateTime &&  UB::unserialize($redis->lIndex($key, $index+1))->time > $lastUpdateTime)
                    {
                        $offsetStart = $index;
                    }
                    else if(UB::unserialize($redis->lIndex($key, $index))->time < $lastUpdateTime && UB::unserialize($redis->lIndex($key, $index-1))->time < $lastUpdateTime)
                    {
                        $offsetEnd = $index;
                    }
                    else
                    {
                        break;
                    }
               }
                $length = $redis->lLen($key);
                //获取当前list的最新数据
                $msg = UB::unserialize($redis->lIndex($key, 0));
                $statistic['success_in'] += $index;
                $statistic['last_update'] = $msg->time;
            }
        }
        $redis->hMSet($statisticKey, $statistic);
    }

    public function getAllStatistics()
    {
        $redis = \Lib\Redis::instance();
        $statisticKey = Cfg\Service::EVENT_CENTER_SATISTIC_KEY;
        return $redis->hGetAll($statisticKey);
    }

    public function getAllSuccessListKeys()
    {
        $keys = \Lib\Redis::instance()->keys(Cfg\Service::MESSAGE_IN_LOG_SUCCESS_KEY.'*');
        rsort($keys);
        return $keys;
    }

    public function deleteList($key)
    {
        return \Lib\Redis::instance()->del($key);
    }
}