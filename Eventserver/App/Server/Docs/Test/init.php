<?php
require '../../constants.php';
require SYS_ROOT . 'Lib/Autoloader.php';
Lib\Autoloader::setNamespacePrefixPath ( SYS_ROOT );
spl_autoload_register ( array (
'Lib\Autoloader',
'loadByNamespace'
) );