<?php

namespace Lib;

/**
 * Autoloaders.<br />
 * <h4>Example</h4>
 * <pre>
 * <code>
 * require SYS_ROOT.'Lib/Autoloader.php';
 * Lib\Autoloader::setNamespacePrefixPath(SYS_ROOT);
 * spl_autoload_register(array('Lib\Autoloader','loadByNamespace'));
 * </code>
 * </pre>
 * 
 * @author Su Chao<suchaoabc@163.com>
 */
class Autoloader {
    /**
     * 命名空间前缀目录.loadByNamespace需使用
     * 
     * @var string
     */
    protected static $namespacePrefixPath = '';
    /**
     * 按命名空间自动加载相应的类
     * 
     * @param string $name
     *            命名空间及类名.如:Controller\Broadcast\Send
     */
    public static function loadByNamespace($name) {
        $classPath = str_replace ( '\\', DIRECTORY_SEPARATOR, $name );
        $classPathFile = self::$namespacePrefixPath . DIRECTORY_SEPARATOR . $classPath . '.php';
        if (is_file ( $classPathFile )) {
            require ($classPathFile);
            return true;
        }
        return FALSE;
    }
    public static function setNamespacePrefixPath($path) {
        self::$namespacePrefixPath = $path;
    }
}