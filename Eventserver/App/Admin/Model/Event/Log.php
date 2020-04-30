<?php
namespace App\Admin\Model\Event;

class Log extends Base {


    protected static $instance;
    /**
     * 获取错误日志
     *
     * @param array   $cond     查询条件.
     * @param integer $page     分页.
     * @param integer $pageSize 每页数据量.
     *
     * @return array
     */
    public function getLogList(&$cond = array(), $page = 1, $pageSize = 10)
    {
        $condPrepared = $cond;
        //处理时间参数. 表的time字段 即第一次推送时间。这个条件是使用索引必须的.
        $hasTimeCond = false;
        foreach($condPrepared as $k => $q)
        {
            if(preg_match('# *`?time`?(start|end)#i', $q))
            {
                $hasTimeCond;
                break;
            }
        }
        $defaultPushTimeStart = strtotime(date('Y-m-d 00:00:00'));
        $defaultPushTimeEnd = strtotime(date('Y-m-d 23:59:59'));
        if(!empty($condPrepared['first_push_time_start']))
        {
            $defaultPushTimeStart  = (int) $condPrepared['first_push_time_start'];
        }
        if(!empty($condPrepared['first_push_time_end']))
        {
            $defaultPushTimeEnd = (int)$condPrepared['first_push_time_end'];
        }
        unset($condPrepared['first_push_time_end']);
        unset($condPrepared['first_push_time_start']);
        if(!$hasTimeCond)
        {
            array_unshift($condPrepared, '`time`>='.$defaultPushTimeStart, '`time` <='.$defaultPushTimeEnd);
        }
        $cond['first_push_time_start'] = $defaultPushTimeStart;
        $cond['first_push_time_end'] = $defaultPushTimeEnd;

        $db = $this->db()->read(self::DATABASE);
        $where = $db->buildWhere($condPrepared);
        if(!empty($where)) {
            $condWhere = ' where ' . $where;
        }
        else
        {
            $condWhere = '';
        }
        $count = $db->query('select count(1) count from '.self::TABLE_BROADCAST_LOG.
                            ' log  force index(time_comound)  join '.self::TABLE_SUBSCRIBERS.' sub on sub.subscriber_id = log.subscriber_id join '.self::TABLE_MSG_CLASSES.' msg_class on msg_class.class_id = log.message_class_id'.
                            $condWhere)
                    ->fetchAll(\PDO::FETCH_ASSOC);
        $count = $count[0]['count'];
        if ( $page * $pageSize >= $count) {
            $page = ceil($count / $pageSize);
        }
        $start = max($page - 1, 0) * $pageSize;
        $limit = " limit $start, $pageSize";

        $sql = 'select log.*,
                 sub.`subscriber_key`,
                 sub.subscriber_name,
                 msg_class.class_name,
                 msg_class.class_key,
                 msg_class.comment
                 from (
                    SELECT  log.log_id,
                    log.time,
                    log.retry_times,
                    log.last_retry_time,
                    log.final_status,
                    log.alive,
                    log.message_time,
                    log.subscriber_id,
                    log.message_class_id,
                    log.job_id
                    FROM   broadcast_failure_log log  force index(time_comound)
                    JOIN subscribers sub
                    ON sub.subscriber_id = log.subscriber_id
                    JOIN message_classes msg_class
                    ON msg_class.class_id = log.message_class_id
                    '.$condWhere.'
                    ORDER  BY `time` desc
                    '.$limit.'
                    ) log
                JOIN subscribers sub
                ON sub.subscriber_id = log.subscriber_id
                JOIN message_classes msg_class
                ON msg_class.class_id = log.message_class_id
                order by log.`time` desc';
        $data = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $result = array('data' => $data, 'count' => $count);
        return $result;
    }

    /**
     * 获取指定ID的错误日志.
     *
     * @param integer $logId log id.
     * @return array
     */
    public function getLogDetail($logId)
    {
        $db = $this->db()->write(self::DATABASE);
        $sql = 'select log.log_id, log.time, log.retry_times,
                log.message_body, log.job_id,log.subscriber_id,
                log.message_class_id,log.last_retry_time,
                log.final_status, log.alive,
                log.first_failure_message,
                log.last_failure_message,
                log.message_time,
                log.last_target,
                sub.`subscriber_key`, sub.subscriber_name,
                msg_class.class_name, msg_class.class_key,
                msg_class.comment,
                subs.subscription_id,
                subs.timeout,
                subs.subscribe_time,
                subs.status
                from '. self::TABLE_BROADCAST_LOG.' log
                join '.self::TABLE_SUBSCRIBERS.' sub on sub.subscriber_id = log.subscriber_id
                join '.self::TABLE_MSG_CLASSES.' msg_class on msg_class.class_id = log.message_class_id
                join '.self::TABLE_SUBSCRIPTIONS.' subs on subs.subscription_id = log.subscription_id
                where log_id = '.$logId;
        $data = $db->query($sql)->fetch(\PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * 再消息重新进入队列后重置重复次数.
     *
     * @param int $logId
     * @return number
     */
    public function resetRetryTimes($logId)
    {
        return $this->db()->write(self::DATABASE)->exec('UPDATE '.self::TABLE_BROADCAST_LOG.' SET retry_times=0, alive=1 WHERE log_id='.intval($logId));
    }

    public function canbeRestored($logData)
    {
        if($logData['alive'] != 0 || $logData['final_status'] != 0 || $logData['job_id'] <= 0)
        {
            return false;
        }
        return true;
    }
}
