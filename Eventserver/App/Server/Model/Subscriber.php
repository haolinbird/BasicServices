<?php

namespace App\Server\Model;

class Subscriber extends \Lib\BaseDbModel {
    const TABLE = 'subscribers';
    const PRIMARY_KEY = 'subscriber_id';
    const SUBSCRIBER_STATUS_NORMAL = 0;
    const PRIVILEGE_CREATE_MESSAGE_CLASS = 'create_message_class';
    const PRIVILEGE_SELF_MAKE_SUBSCRIPTION = 'self_make_subscription';
    const PRIVILEGE_SELF_ENABLE_SEND_MESSAGE = 'self_enable_send_message';
    protected static $validStatus = array(1,0);
    /**
     * if data includes the subscriber id, it will be treated as an UPDATE validation, otherwise an INSERT validation
     * @param array $data
     */
    public function validateInputSubscriberData($data)
    {
        $valid = true;
        if(empty($data['subscriber_name']) || empty($data['subscriber_key']) || empty($data['secret_key']))
        {
            $this->setError('710002', 'subscriber name subscriber key and secret key cannot be empty!');
            return false;
        }
        if(!preg_match('#^[0-9a-zA-Z_]+$#', $data['subscriber_key']))
        {
            $this->setError('710003', 'Invalid subscriber key, only 0-9,a-Z and "_" are allowed !');
            return false;
        }
        if(!isset($data['status']) || !in_array($data['status'], self::$validStatus))
        {
            $this->setError('710004', 'Invalid subscriber status('.$data['status'].') !');
            return false;
        }
        $sql = 'SELECT '.self::PRIMARY_KEY.' FROM '.self::TABLE.'
                WHERE (subscriber_name=? OR subscriber_key=?)';
        $inputData = array (
                $data ['subscriber_name'],
                $data ['subscriber_key']
        );
        if (! empty ( $data ['subscriber_id'] )) {
            $sql .= ' AND subscriber_id <>?';
            $inputData [] = $data ['subscriber_id'];
        }
        $stm = $this->db()->write()->prepare($sql);
        $stm->execute($inputData);
        $re = $stm->fetchColumn();
        if($re !== false)
        {
            $this->setError('710001', 'subscriber name or subscriber key already exists!');
            $valid = false;
        }
        return $valid;
    }
    public function register($subscriberName,$subscriberKey,$secretKey,$comment='',$status=0,$allowedMessageClassToSend='')
    {
        $data = array('subscriber_name' => $subscriberName,
                'subscriber_key' => $subscriberKey,
                'secret_key' => $secretKey,
                'comment' => $comment,
                'status' => $status,
                'register_time'=>time(),
                'allowed_message_class_to_send' =>$allowedMessageClassToSend
        );
        if(!$this->validateInputSubscriberData($data))return false;
        return $id = $this->db()->write()->insert(self::TABLE, $data);
    }

    public function unregister($subscriberKey)
    {
        $data = array('status'=>1);
        return $this->db()->write()->update(self::TABLE, $data, array('subscriber_key'=>$subscriberKey));
    }


    public function getListByPage($cond='', $page=1, $limit=NUMBERS_PER_PAGE, $order='subscriber_id desc')
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
        $sql = "select * from ".self::TABLE;
        $sql .= $cond;
        $sql .= " order by ".$order;
        $sql .= " limit ".($page-1)*$limit.",".$limit;
        $data = $this->db()->read()->query($sql)->fetchALL(\PDO::FETCH_ASSOC);
        $sql = "select count(*) as size from ".self::TABLE;
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

    public function getInfo($cond)
    {
        $res = array();
        if(!empty($cond)) $cond = ' where '.$cond;
        $sql = "select * from ".self::TABLE;
        $sql .= $cond;
        $res = $this->db()->read()->query($sql)->fetch(\PDO::FETCH_ASSOC);
        return $res;
    }

    public function update($subscriber_id,$subscriberName,$subscriberKey,$secretKey,$comment='',$status=-1,$allowed_message_class_to_send='')
    {
        $data = array('subscriber_id' => $subscriber_id,
                'subscriber_name' => $subscriberName,
                'subscriber_key' => $subscriberKey,
                'secret_key' => $secretKey,
                'comment' => $comment
        );
        if(-1 != $status) $data['status'] = $status;
        if(!empty($allowed_message_class_to_send)) $data['allowed_message_class_to_send'] = $allowed_message_class_to_send;
        if(!$this->validateInputSubscriberData($data))return false;
        return $id = $this->db()->write()->update(self::TABLE, $data,array('subscriber_id' => $subscriber_id));
    }

    public function delete($id)
    {
        $db = $this->db()->write();
        $sql = 'DELETE FROM '.self::TABLE.' WHERE '.$this::PRIMARY_KEY.'=?';
        $stm = $db->prepare($sql);
        $re = $stm->execute(array($id));
        return $re;
    }

    public function canSendMessage($subscriberKey, $messageKey)
    {
        $subscriber = $this->getOne(array('subscriber_key'=>$subscriberKey));
        if(!$subscriber)return false;
        if($subscriber['allowed_message_class_to_send'] == '*'){
            return true;
        }
        $allowedMessageClasses = explode('|', $subscriber['allowed_message_class_to_send']);
        return in_array($messageKey, $allowedMessageClasses);
    }

    public function canSelfMakeSubscription($subscriberKey)
    {
        return in_array(self::PRIVILEGE_SELF_MAKE_SUBSCRIPTION, $this->getPrivileges($subscriberKey));
    }

    public function canCreateMessageClass($subscriberKey)
    {
        return in_array(self::PRIVILEGE_CREATE_MESSAGE_CLASS, $this->getPrivileges($subscriberKey));
    }

    public function canSelfEnableSendMessage($subscriberKey)
    {
        return in_array(self::PRIVILEGE_SELF_ENABLE_SEND_MESSAGE, $this->getPrivileges($subscriberKey));
    }


    /**
     * @param string $subscriberKey
     */
    public function getNormalSubscriber($subscriberKey)
    {
        return $this->getOne(array('subscriber_key'=>$subscriberKey,'status'=>$this::SUBSCRIBER_STATUS_NORMAL));
    }

    public function isValidSubscriber($subscriberKey, $secretKey){
        $this->getOne(array('subscriber_key'=>$subscriberKey,'secret_key'=>$this::SUBSCRIBER_STATUS_NORMAL));
    }

    public function getPrivileges($subscriberKey)
    {
        $subscriber = $this->getNormalSubscriber($subscriberKey);
        if(!$subscriber)
        {
            return array();
        }
        return explode(',', $subscriber['privileges']);
    }

    public function enableSendMessage($senderKey, $messageKey)
    {
        $sender = $this->getNormalSubscriber($senderKey);
        if(!$sender)
        {
            $this->setError('710005', 'User not found!');
            return false;
        }
        $messageClass = MessageClasses::instance()->getInfo(' class_key= '.$this->db()->read()->quote($messageKey));
        if(!$messageClass)
        {
            $this->setError('710006', 'Message class not found!');
            return false;
        }
        $allowedClasses = explode('|', $sender['allowed_message_class_to_send']);
        if(!in_array($messageKey, $allowedClasses))
        {
            $allowedClasses[] = $messageKey;
            $allowedClasses = implode('|', $allowedClasses);
            $sql = 'UPDATE '.self::TABLE.' SET allowed_message_class_to_send=? WHERE subscriber_key=?';
            $stm = $this->db()->write()->prepare($sql);
            return $stm->execute(array($allowedClasses, $senderKey));
        }
        return true;
    }
}
