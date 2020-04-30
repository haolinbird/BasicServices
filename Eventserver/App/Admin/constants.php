<?php
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

if (! defined ( 'SYS_ROOT' ))
/**
* Root dir of the event system
*
* @var string
*/
    define ( 'SYS_ROOT', __DIR__ .'/../../'. DS );

if(!defined('TEMPLATE_ENGINE_ROOT')):
/**
 * root directory of template engines
 * @var string
 */
define('TEMPLATE_ENGINE_ROOT', SYS_ROOT.DS.'Lib'.DS.'ViewTemplateEngines'.DS);
endif;

/**
 * HTTP_BASE_URL
 * @var string
 */
if(!defined('HTTP_BASE_URL') && isset($_SERVER['HTTP_HOST']))
{
    if($_SERVER['SERVER_PORT'] == 443)
    {
        define('HTTP_BASE_URL', 'https://'.$_SERVER['HTTP_HOST'].'/');
    }
    else
    {
         define('HTTP_BASE_URL', 'http://'.$_SERVER['HTTP_HOST'].'/');
    }

}
