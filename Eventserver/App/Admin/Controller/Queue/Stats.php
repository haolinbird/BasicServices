<?php
/**
 * 查询特定队列的清理进度.
 *
 * @author xianwangs <xianwangs@jumei.com>
 */

namespace App\Admin\Controller\Queue;

use App\Admin\Controller\Auth\Base;
use \App\Admin\Model;

/**
 * Clean process.
 */
class Stats extends Base
{
    public function execute()
    {
        $queueList = json_decode($this->requestParams->getPost('queue'), true);

        if (! $queueList) {
            echo json_encode(array());
            return;
        }

        $redis = \Lib\Redis::instance();

        $result = array();
        foreach ($queueList as $info) {
            list($host, $port, $queue) = explode(':', $info);
            $key = Clear::QUEUE_KEY_PREFIX . "$host:$port:$queue";
            $exists = false;
            $result[$info] = intval($redis->get($key, $exists));
        }

        echo json_encode($result);
    }
}
