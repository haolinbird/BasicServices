<?php
namespace App\Admin\Cfg;
class Beanstalk {
    public static $default = array (
            array (
                    'host' => '127.0.0.1',
                    'port' => 11300,
                    'weight' => 1
            ),
            array (
                    'host' => '127.0.0.1',
                    'port' => 11301,
                    'weight' => 10
            )
    );
}
