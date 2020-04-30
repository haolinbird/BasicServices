<?php
/**
 * This file is generated automatically by ConfigurationSystem.
 * Do not change it manually in production, unless you know what you're doing and can take responsibilities for the consequences of changes you make.
 */

namespace App\Server\Cfg;
class Db{
    const DEBUG = TRUE;
    /**
     * available options are 1,2<br />
     * 1 log the SQL and time consumed;<br />
     * 2 logs including the traceback.<br />
     * <b>IMPORTANT</b><br />
     * please take care of option "confirm_link",when set as TRUE, each query will try to do an extra query to confirm that the link is still usable,this is mostly used in daemons.
     * @var INT
     */
    const DEBUG_LEVEL = 2;

    public static $default = array(
        'read' => array('dsn'      => 'mysql:host=172.27.0.14;port=3306;dbname=jumei_event',
            'user'     => 'amber_shop_read',
            'password' => 'YG43r$G4PjK7oB=8',
            'confirm_link' => false,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8MB4\'',
                    \PDO::ATTR_TIMEOUT=>1
            )
    ),
            'write' => array('dsn'      => 'mysql:host=172.27.0.14;port=3306;dbname=jumei_event',
                        'user'     => 'amber_shop_write',
                    'password' => 'P2$+ZuTjv428Pr4n',
                    'confirm_link' => false,//required to set to TRUE in daemons.
                    'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8MB4\'',
                            \PDO::ATTR_TIMEOUT=>1
                    )
    )
    );
}
