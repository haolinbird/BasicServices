<?php

namespace Lib\Util;

/**
 * Provides common methods for getting event system information and inspectings.
 * 
 * @author Su Chao<suchaoabc@163.com>
 */
class Sys {
    /**
     * Get the time consumed in the script.
     * 
     * @uses \SCRIPT_START_TIME
     */
    public static function uptime() {
        return sprintf ( '%.6f', microtime ( true ) - SCRIPT_START_TIME );
    }
    
    public static function getAppCfg($name, $appName=null)
    {
        static $cfgInstances=array();
        if(empty($appName))
        {
            $appName = APP_NAME;
        }
        $cfgClassStr = '\App\\'.$appName.'\\'.'Cfg\\'.$name;
        if(!isset($cfgInstances[$cfgClassStr]))
        {
            if(!class_exists($cfgClassStr))
            {
                throw new \Exception('Config class "'.$cfgClassStr.'" not found!');
            }
            $cfgInstances[$cfgClassStr] = new $cfgClassStr;
        }
        return $cfgInstances[$cfgClassStr];
    }

    public static function returnAppCfg($name, $appName=null)
    {
        if(empty($appName))
        {
            $appName = APP_NAME;
        }
        $cfgFile = SYS_ROOT.'/App/'.$appName.'/'.'Cfg/'.$name.'.php';
        if(!is_file($cfgFile))
        {
            throw new \Exception('Config file "'.$cfgFile.'" not found!');
        }
        return require($cfgFile);
    }
}