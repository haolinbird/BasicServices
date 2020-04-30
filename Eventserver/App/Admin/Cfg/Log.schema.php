<?php
namespace App\Admin\Cfg;

/**
 *
 * @author suchao
 *
 */
class Log{
    /**
     * root of all logs of type "file".
     * @var please make sure this file is writeable to php-fpm worker.
     */
    const FILE_LOG_ROOT = '/home/logs/jm-event-center-admin/';
   

    /**
     * for database connections
     * @var array
     */
    public static $db = array('logger'=>'file',//logs are sent to PHP which is defined in php.ini as error_log
                              'rotate'=>False
                             );
    public static  $admin = array(
            'logger' => 'jsonfile',
            'fields' => array('user', 'controller', 'action', 'params'),
            'rotate' => false
    );
    
}
