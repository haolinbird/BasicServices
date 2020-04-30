<?php
namespace App\Admin\Model\Event;

class Subscriber extends Base {

    const TABLE       = 'subscribers';
    const PRIMARY_KEY = 'subscriber_id';
    protected static $relatedModel = array('\App\Server\Model\Subscriber');
    protected static $instance;

    /**
     * 获取订阅者列表
     *
     * @return array
     */
    public function getNames()
    {
        $db = $this->db()->write(self::DATABASE);
        $sql = 'SELECT subscriber_name, subscriber_id, subscriber_key
                FROM '.static::TABLE_SUBSCRIBERS.'
                ORDER BY subscriber_name';
        $data = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * 按条件获取订阅者信息.
     *
     * @param array $cond 条件.
     * @return array
     */
    public function getListByCond($cond = array())
    {
        $db = $this->db()->write(self::DATABASE);
        $sql = 'SELECT *
            FROM '.static::TABLE;
        if ($cond) {
            $where = $db->buildWhere($cond);
            $sql .= " where $where";
        }
        $data = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * 获取订阅者列表.
     *
     * @param array   $cond     查询条件.
     * @param integer $page     分页.
     * @param integer $pageSize 每页数据量.
     *
     * @return array 订阅者列表.
     */
    public function getList($cond = array(), $page = 1, $pageSize = 10) {
        $where = $this->db()->read(self::DATABASE)->buildWhere($cond);
        $sql = 'select count(1) count from '.self::TABLE;
        if(!empty($where)){
            $sql .= ' where '.$where;
        }
        $count = $this->db()->read(self::DATABASE)->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $count = $count[0]['count'];
        if ( $page * $pageSize > $count) {
            $page = floor($count / $pageSize) + 1;
        }
        $start = max($page - 1, 0) * $pageSize;
        $limit = " limit $start, $pageSize";
        $sql = 'select * from '. self::TABLE;
        if(!empty($where)) {
            $sql .= ' where ' . $where;
        }
        $sql .= $limit;
        $data = $this->db()->read(self::DATABASE)->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $result = array('data' => $data, 'count' => $count);
        return $result;
    }

    /**
     * 插入一条数据
     *
     * @param array $fields
     *
     * @return void
     */
    public function insert($fields) {
        $this->db()->write(self::DATABASE)->insert(self::TABLE, $fields);
    }
}
