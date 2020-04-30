<?php

namespace App\Admin\Cfg;

class MNLogger
{
    public $exception = array(
            'on' => true,
            'app' => 'mec',
            'logdir' => '/home/logs/monitor/'

    );


    public $trace = array(
        'on' => true,
        'app' => 'mec',
        'logdir' => '/home/logs/monitor/'
    );
    public $data = array(
        'on' => true,
        'app' => 'mec',
        'logdir' => '/home/logs/monitor/'
    );
}

