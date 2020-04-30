<?php
namespace App\Admin\Model\Event;

class Subscription extends Base {

    const TABLE       = 'subscriptions';
    const TABLE_SUB_PARAMS       = 'subscription_params';
    const PRIMARY_KEY = 'subscription_id';
    protected static $relatedModel = array('\App\Server\Model\Subscription');
    protected static $instance;

    public function getSubscriptionCount($cond = array())
    {
        $db = $this->db()->read(static::DATABASE);
        $where = $db->buildWhere($cond);

        $sql = 'SELECT count(1) count
            FROM '.static::TABLE_SUBSCRIPTIONS.' s
            JOIN '.static::TABLE_SUBSCRIBERS.' sr ON sr.subscriber_id = s.subscriber_id
            JOIN '.static::TABLE_MSG_CLASSES.' mc ON mc.class_id= s.message_class_id ';

        if(!empty($where)) {
            $sql .= ' where ' . $where;
        }

        $count = $db->query($sql)->fetch(\PDO::FETCH_ASSOC);
        return $count['count'];
    }

    /**
     * 获取错误日志
     *
     * @param array   $cond     查询条件.
     * @param integer $page     分页.
     * @param integer $pageSize 每页数据量.
     *
     * @return array
     */
    public function getSubscriptionList($cond = array(), $page = 1, $pageSize = 10)
    {

        $db = $this->db()->read(static::DATABASE);
        $where = $db->buildWhere($cond);

        $sql = 'SELECT count(1) count
            FROM '.static::TABLE_SUBSCRIPTIONS.' s
            JOIN '.static::TABLE_SUBSCRIBERS.' sr ON sr.subscriber_id = s.subscriber_id
            JOIN '.static::TABLE_MSG_CLASSES.' mc ON mc.class_id= s.message_class_id ';

        if(!empty($where)) {
            $sql .= ' where ' . $where;
        }

        $count = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $count = $count[0]['count'];

        if ( $page * $pageSize >= $count) {
            $page = ceil($count / $pageSize);
        }

        $start = max($page - 1, 0) * $pageSize;
        $limit = " limit $start, $pageSize";

        $sql = 'SELECT s.*,sr.subscriber_name,sr.subscriber_key, mc.class_name,mc.class_key
            FROM '.static::TABLE_SUBSCRIPTIONS.' s
            JOIN '.static::TABLE_SUBSCRIBERS.' sr ON sr.subscriber_id = s.subscriber_id
            JOIN '.static::TABLE_MSG_CLASSES.' mc ON mc.class_id= s.message_class_id ';

        if(!empty($where)) {
            $sql .= ' where ' . $where;
        }
        $sql .= " order by subscription_id desc";
        $sql .= $limit;
        $data = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $data = array('data' => $data, 'count' => $count);

        return $data;
    }

    /**
     * 获取指定ID的错误日志.
     *
     * @param integer $logId log id.
     * @return array
     */
    public function getSubscriptionDetail($subscriptionId)
    {
        $db = $this->db()->read(static::DATABASE);

        $sql = 'SELECT s.*,sr.subscriber_name, sr.subscriber_key, mc.class_name,mc.class_key 
            FROM '.static::TABLE_SUBSCRIPTIONS.' s
            JOIN '.static::TABLE_SUBSCRIBERS.' sr ON sr.subscriber_id = s.subscriber_id
            JOIN '.static::TABLE_MSG_CLASSES.' mc ON mc.class_id = s.message_class_id
            WHERE subscription_id = '.(int)$subscriptionId;

        $data = $db->query($sql)->fetch(\PDO::FETCH_ASSOC);
        return $data;
    }

    public function update($data, $cond)
    {
        $db = $this->db()->write(static::DATABASE);
        return $db->update(self::TABLE, $data, $cond);
    }
    public function insert($data)
    {
        return $this->db()->write(static::DATABASE)->insert(static::TABLE, $data);
    }
    public function getAddedSubscriberMessages($cond)
    {
        $db = $this->db()->read(static::DATABASE);
        $sql = "select class_key from ".static::TABLE_MSG_CLASSES." mc
            join ".static::TABLE_SUBSCRIPTIONS." s on s.message_class_id = mc.class_id
            JOIN ".static::TABLE_SUBSCRIBERS." sr ON sr.subscriber_id= s.subscriber_id
            where sr.subscriber_key = ".$db->quote($cond['subscriber_key']);
        $data = $db->query($sql)->fetchAll(\PDO::FETCH_NUM);
        return $data;
    }

    /**
     * @param int $subscriptionId
     * @return array
     * @throws \Lib\DbException
     */
    public function getSubscriptionParams($subscriptionId){
        $db = $this->db()->read(static::DATABASE);
        $sql = ' SELECT * FROM '.static::TABLE_SUB_PARAMS.
               ' WHERE subscription_id='.(int) $subscriptionId;
        $res = $db->query($sql);
        $paired = array();
        while($row = $res->fetch(\PDO::FETCH_ASSOC)){
            $paired[$row['param_name']] = $row['param_value'];
        }
        return $paired;
    }

    /**
     * @param $subscriptionId
     * @param array $params
     * @return bool|string
     * @throws \Exception
     */
    public function saveSubscriptionParams($subscriptionId, array $params){
        $data = array();
        foreach($params as $k => $v){
            // hack , bool型false存到数据库会被转成'',这里先转成整型0
            if($k == 'AlerterEnabled' || $k == 'ReceiveBenchMsgs'){
                $v = (int)$v;
            }
            $data[] = array('subscription_id' => $subscriptionId, 'param_name' => $k, 'param_value' => $v);
        }
        $db = $this->db()->write(static::DATABASE);
        $db->beginTransaction();
        try{
            $db->query('DELETE FROM '.static::TABLE_SUB_PARAMS.' WHERE subscription_id='.(int)$subscriptionId);
            $db->insert(static::TABLE_SUB_PARAMS, $data);
        } catch(\Exception $ex){
            $db->rollBack();
            throw $ex;
        }
        $db->commit();
        return true;
    }

    // 同步订阅信息到压测环境.
    // medis分发服务依赖表: subscriptions,message_classes,broadcast_failure_log,subscribers,subscription_params.
    /*public function syncSubscriptionInfoToBench($subscriptionId)
    {
        $dbConfigs = \Lib\Util\Sys::getAppCfg('Db');

        $defaultDbCfgName = static::DATABASE;
        $benchDbCfgName = static::DATABASE_BENCH;

        // 如果没有bench环境的数据库，则不做任何操作.
        if (! property_exists($dbConfigs, $benchDbCfgName)) {
            return true;
        }

        $defaultDbCfg = $dbConfigs::$$defaultDbCfgName;
        $benchDbCfg = $dbConfigs::$$benchDbCfgName;

        // bench环境与prod使用相同的配置，则不做任何操作.
        if ($defaultDbCfg['write']['dsn'] == $benchDbCfg['write']['dsn']) {
            return true;
        }

        $db = $this->db()->read(static::DATABASE);
        $benchdb = $this->db()->write(static::DATABASE_BENCH);

        // ===============================开始同步subscriptions(订阅关系表)设置到bench数据库===============================.
        $sql = "SELECT * FROM `subscriptions` WHERE `subscription_id` = $subscriptionId";
        $mainSubscriptions = $db->query($sql)->fetch(\PDO::FETCH_ASSOC);
        if (! $mainSubscriptions) {
            return false;
        }

        $sql = "DELETE FROM `subscriptions` WHERE `subscription_id` = $subscriptionId";
        $benchdb->exec($sql);

        if (! $benchdb->insert('subscriptions', $mainSubscriptions)) {
            return false;
        }

        // ===============================开始同步message_classes(消息分类表)到bench数据库===============================.
        $sql = "SELECT * FROM `message_classes` WHERE `class_id` = {$mainSubscriptions['message_class_id']}";
        $mainMessageClasses = $db->query($sql)->fetch(\PDO::FETCH_ASSOC);
        if (! $mainMessageClasses) {
            return false;
        }

        $sql = "DELETE FROM `message_classes` WHERE `class_id` = {$mainSubscriptions['message_class_id']}";
        $benchdb->exec($sql);

        if (! $benchdb->insert('message_classes', $mainMessageClasses)) {
            return false;
        }

        // ===============================开始同步subscribers(订阅者基础信息表)到bench数据库===============================.
        $sql = "SELECT * FROM `subscribers` WHERE `subscriber_id` = {$mainSubscriptions['subscriber_id']}";
        $mainSubscriber = $db->query($sql)->fetch(\PDO::FETCH_ASSOC);
        if (! $mainSubscriber) {
            return false;
        }

        $sql = "DELETE FROM `subscribers` WHERE `subscriber_id` = {$mainSubscriptions['subscriber_id']}";
        $benchdb->exec($sql);

        if (! $benchdb->insert('subscribers', $mainSubscriber)) {
            return false;
        }

        // ===============================开始同步subscription_params(订阅参数表)到bench数据库===============================.
        $sql = "SELECT * FROM `subscription_params` WHERE `subscription_id` = $subscriptionId";
        $mainSubParams = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        if (! $mainSubParams) {
            return false;
        }

        $sql = "DELETE FROM `subscription_params` WHERE `subscription_id` = $subscriptionId";
        $benchdb->exec($sql);

        foreach ($mainSubParams as $param) {
            if (! $benchdb->insert('subscription_params', $param)) {
                return false;
            }
        }

        return true;
    }*/

    public function clearSubscriptionParams($subscriptionId){
        return $this->db()->write(static::DATABASE)->query('DELETE FROM '.static::TABLE_SUB_PARAMS.' WHERE subscription_id='.(int)$subscriptionId);
    }
}
