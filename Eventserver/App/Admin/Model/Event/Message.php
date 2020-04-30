<?php
namespace App\Admin\Model\Event;

class Message extends Base {

    const TABLE = 'message_classes';
    const PRIMARY_KEY = 'class_id';
    protected static $relatedModel = array('\App\Server\Model\MessageClasses');
    protected static $instance;

    /**
     * 获取消息分类信息.
     *
     * @param array   $cond     查询条件.
     * @param integer $page     分页.
     * @param integer $pageSize 每页数据量.
     *
     * @return array 消息分类列表.
     */
    public function getMsgList($cond = array(), $page = 1, $pageSize = 10) {
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
