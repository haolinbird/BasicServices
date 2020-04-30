<?php
/**
 * init scripts for handler
 */

require __DIR__.'/../constants.php';

! defined('JM_APP_NAME') && define('JM_APP_NAME', 'mec');

require SYS_ROOT.'/Vendor/Bootstrap/Autoloader.php';
Bootstrap\Autoloader::instance()->addRoot(SYS_ROOT)->addRoot(SYS_ROOT.'Vendor/')->addRoot(APP_ROOT)->init();

require SYS_ROOT . 'Lib/Autoloader.php';
Lib\Autoloader::setNamespacePrefixPath ( SYS_ROOT );
spl_autoload_register ( array (
    'Lib\Autoloader',
    'loadByNamespace'
));

// autoload handlers
spl_autoload_register(function($className){
    if(strpos($className, 'Handler') !== 0){
        return;
    }
    $classFile = APP_ROOT.str_replace('\\', DS, $className).'.php';
    if(file_exists($classFile)){
        require($classFile);
    }
});