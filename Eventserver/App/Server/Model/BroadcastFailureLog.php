<?php
namespace App\Server\Model;
class BroadcastFailureLog extends \Lib\BaseDbModel{
    const TABLE='broadcast_failure_log';
    const PRIMARY_KEY='log_id';
    protected $lastFailureIsRepeated = false;
    protected $validFinalStatus = array(0,1);
    /**
     *
     * @return static
    */
    public static function instance()
    {
        return parent::instance();
    }
    /**
     *
     * @param \stdClass $message
     * @param array $subscription
     * @param string $errorMessage
     * @param int $logId the failure log ID. if it's valid, then it's a repeated failure.
     * @param int $status if the message is successfully handled by the target. failure 0, success 1, default is 0
     * @param int $jobid unique jobid, composited with timestamp and MQ jobid
     * @return mixed return the log ID, if the failure appears for the first time. BOOL indicates if the update is successful when it's a repeated failure.
     */
    public function logFailure(\stdClass $message,array $subscription,$errorMessage='',$logId=null,$status=0, $jobid='')
    {
        $db = $this->db()->write();
        $status = (int) $status;
        if(!$logId || !$this->exists($logId))
        {
            $data = array('message_class_id'=>$subscription['message_class_id'],
                    'subscriber_id'=>$subscription['subscriber_id'],
                    'subscription_id' => $subscription['subscription_id'],
                    'message_body'=>json_encode($message->body),
                    'time'=>time(),
                    'last_failure_message'=>$errorMessage,
                    'final_status' => $status,
                    'job_id' => $jobid,
                    'first_failure_message'=>$errorMessage,
                    'message_time' => $message->time

            );
            $this->lastFailureIsRepeated = false;
            return $db->insert(self::TABLE, $data);
        }

        $sql = 'UPDATE '.self::TABLE.' SET
                retry_times=retry_times+1,
                last_retry_time='.time().',
                job_id='.$db->quote($jobid).',
                final_status ='.$status;

        if($status != 1)
        {
            $sql .= ',last_failure_message='.$db->quote($errorMessage);
        }
        $sql .= ' WHERE log_id='.$logId;
        $this->lastFailureIsRepeated = true;
        return $db->exec($sql);
    }

    /**
     * if the last failure that logged by {@link Model\BroadcastFailureLog::logFailure} is a repeated failure.
     * @return boolean
     */
    public function lastFailureIsRepeated()
    {
        return $this->lastFailureIsRepeated;
    }
    /**
     * set the final status of failure.
     * @param int $logId
     * @param int $status 1 the message was succesfully handled by the subscriber and failure is resolved. 0 still not sucessfully sent.
     * @param int $alive message is still in queue or not, 1 or 0.
     */
    public function setFinalStatus($logId,$status, $alive=0)
    {
        if(!in_array($status, $this->validFinalStatus))
        {
            $this->setError(710301, 'In valid final status('.$status.') of failure log.');
            return false;
        }
        return $this->updateByPrimaryKey($logId, array('final_status'=>$status, 'alive'=>intval($alive)));
    }

    public function getSubListByPage($cond='', $page=1, $limit=NUMBERS_PER_PAGE, $order='a.log_id desc')
    {
        $res = array();
        if(!empty($cond))
        {
            if(is_string($cond))
            {
                $cond = ' where '.trim(trim($cond), 'and');
            }
            else if(is_array($cond))
            {
                $cond = ' where '.$this->db()->read()->buildWhere($cond);
            }
        }
        else {
            $cond = '';
        }
        $sql = "select a.*,b.subscriber_name,b.subscriber_key,c.class_name,c.class_key from ".self::TABLE." a";
        $sql .= " left join subscribers b on a.subscriber_id=b.subscriber_id";
        $sql .= " left join message_classes c on a.message_class_id=c.class_id";
        $sql .= $cond;
        $sql .= " order by ".$order;
        $sql .= " limit ".($page-1)*$limit.",".$limit;
        $data = $this->db()->read()->query($sql)->fetchALL(\PDO::FETCH_ASSOC);
        $sql = "select count(1) as size from ".self::TABLE." a";
        $sql .= " left join subscribers b on a.subscriber_id=b.subscriber_id";
        $sql .= " left join message_classes c on a.message_class_id=c.class_id";
        $sql .= $cond;
        $re = $this->db()->read()->query($sql)->fetch(\PDO::FETCH_ASSOC);
        $size = (!empty($re['size'])) ? $re['size'] : 0;
        $res = $this->getPageInfo($data, $size, $page, $limit);
        return $res;
    }

    public function getInfo($cond)
    {
        $res = array();
        if(!empty($cond)) $cond = ' where '.$cond;
        $sql = "select a.*,d.reception_channel,b.subscriber_name,b.subscriber_key,c.class_name,c.class_key from ".self::TABLE." a";
        $sql .= " left join subscribers b on a.subscriber_id=b.subscriber_id";
        $sql .= " left join message_classes c on a.message_class_id=c.class_id";
        $sql .= " left join subscriptions d on b.subscriber_id=d.subscriber_id and c.class_id=d.message_class_id";
        $sql .= $cond;
        $res = $this->db()->read()->query($sql)->fetch(\PDO::FETCH_ASSOC);
        return $res;
    }
}
