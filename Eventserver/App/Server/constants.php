<?php
/**
 * Defined basic common constants that will not be influenced by environments. It should be included by any other script in the first line.
 */

/**
 * start time of the current script
 * 
 * @var float
 */
define ( 'SCRIPT_START_TIME', microtime ( true ) );

/**
 * Directory seperator of the system.
 * 
 * @var string
 */
define ( 'DS', DIRECTORY_SEPARATOR );

define('APP_NAME', 'Server');

if (! defined ( 'SYS_ROOT' ))
    /**
     * Root dir of the event system
     * 
     * @var string
     */
    define ( 'SYS_ROOT', realpath(__DIR__ .'/../../'). DS );

if (! defined('APP_ROOT')) {
    define('APP_ROOT', SYS_ROOT.'App'.DS.APP_NAME.DS);
}
