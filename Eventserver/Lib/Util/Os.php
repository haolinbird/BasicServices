<?php
namespace Lib\Util;
class Os{
    /**
     * get lists of sub-directories and files of the base dir
     * @param string $baseDir
     * @param array $return  
     * @return array
     */
    public static function listFiles($baseDir, &$return=array('dir'=>array(), 'file'=>array()))
    {
        if(!is_readable($baseDir))
        {
            return $return;
        }
        $subs = scandir($baseDir);
        $baseDir = realpath($baseDir).DIRECTORY_SEPARATOR;
        foreach($subs as $f)
        {
            if($f == '.' || $f== '..')
            {
                continue;
            }
            $f = realpath($baseDir.$f);

            if(is_file($f))
            {
                $return['file'][] = $f;
            }

            if(is_dir($f) && !in_array($f, $return['dir']))
            {
                $return['dir'][] = $f;
                self::listFiles($f, $return);
            }
        }
        return $return;
    }
}