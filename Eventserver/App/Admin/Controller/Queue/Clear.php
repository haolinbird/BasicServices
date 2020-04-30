<?php
/**
 * 清除特定tube中的所有数据.
 *
 * @author xianwangs <xianwangs@jumei.com>
 */

namespace App\Admin\Controller\Queue;

use App\Admin\Controller\Auth\Base;
use \App\Admin\Model;

/**
 * Clean queue.
 */
class Clear extends Base
{
    const QUEUE_KEY_PREFIX = 'queue_clear_process:';

    public function execute()
    {
        set_time_limit(1800);
        ignore_user_abort(true);

        $queue = $this->requestParams->getPost('queue');
        $host = $this->requestParams->getPost('host');
        $port = $this->requestParams->getPost('port');

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        $username = \Lib\User::current()->name;
        \Lib\Log::instance('admin')->log("[$username] [清理队列] [$queue]\r\n");

        $key = self::QUEUE_KEY_PREFIX . "$host:$port:$queue";
        $redis = \Lib\Redis::instance();

        $beanstalk = new \Lib\Beanstalk(null);
        $beanstalk->connect($host, $port);

        $tubeInfo = $beanstalk->statsTube($queue);
        $redis->setex($key, 1800, $tubeInfo['current-jobs-ready']);

        try {
            while ($job = $beanstalk->peekDelayed($queue)) {
                $beanstalk->delete($job);
            }
        } catch (\Exception $e) {
        }

        try {
            while ($job = $beanstalk->peekBuried($queue)) {
                $beanstalk->delete($job);
            }
        } catch (\Exception $e) {
        }

        for (;;) {
            try {
                $job = $beanstalk->watch($queue)->ignore('default')->reserve(2);
                if (! $job) {
                    break;
                }

                $beanstalk->delete($job);
                // echo $beanstalk->statsTube($queue)['current-jobs-ready'], PHP_EOL;

                $tubeInfo = $beanstalk->statsTube($queue);
                $redis->setex($key, 1800, $tubeInfo['current-jobs-ready']);
            } catch (\Exception $e) {
                $redis->del($key);
                break;
            }
        }
    }
}
