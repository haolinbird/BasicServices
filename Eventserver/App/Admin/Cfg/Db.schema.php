<?php
namespace App\Admin\Cfg;
class Db{
    const DEBUG = FALSE;
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
        'read' => array(
            'dsn'      => "#{mec.database.default.read.dsn}",
            'user'     => "#{mec.database.default.read.user}",
            'password' => "#{mec.database.default.read.password}",
            'confirm_link' => false,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8MB4\'',
                \PDO::ATTR_TIMEOUT=>1
            )
        ),
        'write' => array(
            'dsn'      => "#{mec.database.default.write.dsn}",
            'user'     => "#{mec.database.default.write.user}",
            'password' => "#{mec.database.default.write.password}",
            'confirm_link' => false,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8MB4\'',
                \PDO::ATTR_TIMEOUT=>1
            )
        ),
    );

    /*public static $default_bench = array(
        'read' => array(
            'dsn'      => "#{mec.bench.database.default.read.dsn}",
            'user'     => "#{mec.bench.database.default.read.user}",
            'password' => "#{mec.bench.database.default.read.password}",
            'confirm_link' => false,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8MB4\'',
                \PDO::ATTR_TIMEOUT=>1
            )
        ),
        'write' => array(
            'dsn'      => "#{mec.bench.database.default.write.dsn}",
            'user'     => "#{mec.bench.database.default.write.user}",
            'password' => "#{mec.bench.database.default.write.password}",
            'confirm_link' => false,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8MB4\'',
                \PDO::ATTR_TIMEOUT=>1
            )
        ),
    );*/
}

