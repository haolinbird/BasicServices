<?php
namespace App\Server\Cfg;
class Beanstalk {
    public static $default = "#{mec.queue.servers.default}";

    // 压测环境.
    public static $default_bench = "#{mec.bench.queue.servers.default}";
}
