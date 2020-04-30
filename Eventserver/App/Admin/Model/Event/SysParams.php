<?php
namespace App\Admin\Model\Event;

class SysParams extends Base
{
    const TABLE       = 'sys_params';
    const PRIMARY_KEY = 'param_id';
    protected static $instance;

    const FIELD = 'default_alarm_receiver';

    public function replace($data)
    {
        $this->delete(array('param_name' => self::FIELD));
        $this->db()->write(self::DATABASE)->insert(self::TABLE, $data);
    }
}