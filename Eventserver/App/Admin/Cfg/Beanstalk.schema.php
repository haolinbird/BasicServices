<?php
namespace App\Admin\Cfg;
class Beanstalk {
    // format: group1 => [server1, server2], group2 => [server1, server2]
    public static $servers = "#{mec.queue.servers.group}";
}
