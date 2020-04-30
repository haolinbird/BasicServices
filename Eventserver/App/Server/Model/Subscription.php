<?php
namespace App\Server\Model;
class Subscription extends \Lib\BaseDbModel{
    const TABLE = 'subscriptions';
    const PRIMARY_KEY = 'subscription_id';
    const SUBSCRIPTION_NORMAL = 0, SUBSCRIPTION_CANCEL = 1;
    public static $subscriptionStatus = array(
        self::SUBSCRIPTION_NORMAL => '正常',
        self::SUBSCRIPTION_CANCEL => '已经取消',
    );

    /**
     * make an subscription.
     * @param string $subscriberKey
     * @param string $messageClassKey
     * @param string $receptionChannel the address to receive the broadcast
     * @param int $timeout when to timeout, by default is 0, which means use \App\Server\Cfg\Service::MAX_MESSAGE_DELIVERY_TIME as timeout value
     * @todo prevent fraud subscription using others' ID
     */
    public function make($subscriberKey, $messageClassKey,$receptionChannel, $timeout = 5)
    {
        $modelSubscriber = new Subscriber();
        $subscriberId = $modelSubscriber->fetchColum('subscriber_id', array('subscriber_key'=>$subscriberKey));
        if(!$subscriberId)
        {
            $this->setError(710201, 'Subscriber ('.$subscriberKey.') does not exist !');
            return false;
        }
        $modelMsgCls = new MessageClasses();
        $messageClassId = $modelMsgCls->fetchColum('class_id', array('class_key'=>$messageClassKey));
        if(!$messageClassId)
        {
            $this->setError(710202, 'Message class ('.$messageClassKey.') does not exist !');
            return false;
        }

        if($this->subscriptionExists($subscriberId, $messageClassId))
        {
            $this->setError(710203, 'Subscriber('.$subscriberId.') has already subscribed message('.$messageClassId.') !');
            return false;
        }
        if(!$this->isValidChannel($receptionChannel))
        {
            $this->setError(710204, 'Reception channel ('.$receptionChannel.') is not valid!');
            return false;
        }
        $re = $this->db()->write()->insert(self::TABLE, array('subscriber_id'=>$subscriberId,
                                                              'message_class_id'=>$messageClassId,
                                                              'reception_channel'=>$receptionChannel,
                                                              'subscribe_time' => time(),
                                                              'timeout' => (int)$timeout,
                                                              )
                                          );
        return $re;
    }

    public function getSubListByPage($cond='', $page=1, $limit=10, $order='a.subscription_id desc')
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
    	$sql .= " inner join subscribers b on a.subscriber_id=b.subscriber_id";
    	$sql .= " inner join message_classes c on a.message_class_id=c.class_id";
    	$sql .= $cond;
    	$sql .= " order by ".$order;
    	$sql .= " limit ".($page-1)*$limit.",".$limit;
    	$data = $this->db()->read()->query($sql)->fetchALL(\PDO::FETCH_ASSOC);
    	$sql = "select count(1) as size from ".self::TABLE." a";
    	$sql .= " inner join subscribers b on a.subscriber_id=b.subscriber_id";
    	$sql .= " inner join message_classes c on a.message_class_id=c.class_id";
    	$sql .= $cond;
    	$re = $this->db()->read()->query($sql)->fetch(\PDO::FETCH_ASSOC);
    	$size = (!empty($re['size'])) ? $re['size'] : 0;
    	$res = $this->getPageInfo($data, $size, $page, $limit);
    	return $res;
    }

    public function getList($cond='')
    {
    	$res = array();
    	if(!empty($cond)) $cond = ' where '.trim(trim($cond), 'and');
    	$sql = "select * from ".self::TABLE;
    	$sql .= $cond;
    	$res = $this->db()->read()->query($sql)->fetchALL(\PDO::FETCH_ASSOC);
    	return $res;
    }

    public function isValidChannel($channel)
    {
        $parsedUrl = parse_url($channel);
        $validChannelScheme = array('http','https');
        if(!isset($parsedUrl['scheme']) || !in_array($parsedUrl['scheme'], $validChannelScheme))
        {
            return false;
        }
        if(empty($parsedUrl['host']))return false;
        return $parsedUrl;
    }

    public function subscriptionExists($subscriberId, $messageClassId)
    {
        $sql = 'SELECT 1 FROM '.self::TABLE.' WHERE subscriber_id=? AND message_class_id=?';
        $stm = $this->db()->write()->prepare($sql);
        $re = $stm->execute(array($subscriberId, $messageClassId));
        return (bool)$stm->fetchColumn();
    }
    /**
     * cancel a subscription
     * @param string $subscriberKey
     * @param string $messageClassKey
     * @todo prevent fraud subscription using others' ID
     */
    public function cancel($subscriberKey, $messageClassKey)
    {
        $sql = 'DELETE FROM '.self::TABLE.'
                WHERE subscriber_id=(SELECT subscriber_id FROM '.Subscriber::TABLE.' WHERE subscriber_key=?)
                AND message_class_id=(SELECT class_id FROM '.MessageClasses::TABLE.'
                                          WHERE class_key=?)';
        $stm = $this->db()->write()->prepare($sql);
        return $re = $stm->execute(array($subscriberKey, $messageClassKey));
    }

    public function getSubscriptionsByMessageKey($messageKey, $normalStatus=false)
    {
        $db = $this->db()->read();
        $sql = 'SELECT t1.*,t2.class_key FROM '.self::TABLE.' t1
                INNER JOIN '.MessageClasses::TABLE.' t2
                ON(t1.message_class_id=t2.class_id AND t2.class_key='.$db->quote($messageKey).')';
        if($normalStatus)
        {
            $sql .= ' AND t1.status='.static::SUBSCRIPTION_NORMAL;
        }
        return $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function modifyById($id, $data = array()) {
        $id = (int)$id;
        if (is_numeric($id)) $this->updateByPrimaryKey($id, $data);
        return $this;
    }

    public function getByPrimaryKey($valueOfPrimayKey, $normalStatus=true)
    {
        $db = $this->db()->read();
        $sql = 'SELECT t1.*,t2.class_key FROM '.self::TABLE.' t1
                INNER JOIN '.MessageClasses::TABLE.' t2
                ON(t1.message_class_id=t2.class_id)
                WHERE t1.subscription_id=?';
        if($normalStatus)
        {
            $sql .= ' AND t1.status='.static::SUBSCRIPTION_NORMAL;
        }
        $stm = $db->prepare($sql);
        $stm->execute(array($valueOfPrimayKey));
        return $stm->fetch(\PDO::FETCH_ASSOC);
    }
}
