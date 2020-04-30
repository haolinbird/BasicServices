<?php
namespace App\Admin\Cfg;
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
    const DEBUG_LEVEL = 1;
    public static $default = array('read' => array('dsn'      => 'mysql:host=192.168.2.86;port=9001;dbname=jm_event',
                                                 'user'     => 'dev',
                                                 'password' => 'jumeidevforall',
                                                 'confirm_link' => false,//required to set to TRUE in daemons.
                                                 'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8MB4\'',
                                                                     \PDO::ATTR_TIMEOUT=>1
                                                                     )
                                                 ),
                                 'write' => array('dsn'      => 'mysql:host=192.168.2.79;port=9001;dbname=jm_event',
                                                  'user'     => 'dev',
                                                  'password' => 'jumeidevforall',
                                                  'confirm_link' => false,//required to set to TRUE in daemons.
                                                  'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8MB4\'',
                                                                     \PDO::ATTR_TIMEOUT=>1
                                                                     )
                                                  )
                                  );
}
