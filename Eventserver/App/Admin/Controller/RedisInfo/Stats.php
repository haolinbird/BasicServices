<?php
namespace App\Admin\Controller\RedisInfo;

use App\Admin\Controller\Auth\Base;
use \App\Admin\Model;

class Stats extends Base
{
    public function execute()
    {
        $redis = \Lib\Redis::instance();
        $info = $redis->info();

        $tpl        = $this->getTemplate();
        $tpl->assign(
            array(
                'info'       => $info
            )
        );
        $tpl->display();
    }
}

