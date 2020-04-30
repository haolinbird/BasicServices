<?php
namespace App\Admin\Cfg;
/**
 * Redis configrations.<br />
 * *<b>Important</b>*
 * <ul>
 *     <li>Once OPT_SERIALIZER is set, it should never be changed, otherwise those previously stored data will be broken.</li>
 *     <li>to avoid process blocking, it has to set an acceptable connection timeout threshhold, since original redis connection timeout is 0 which means the it will keep trying connecting to the server even the server is gone.</li>
 *     <li>but you can use {@link \Lib\Redis::disableSerialization()} to disable serialization before store a data that should not be serialized, and will subsequently use in functions such as {@link \Lib\Redis:sort},{@link \Lib\Redis:getRange} etc.</li>
 * </ul>
 */
class Redis{
    /**
     * default timeout for all connections
     * @var int
     */
    const CONNECTION_TIMEOUT = 2;
    public static $cache = array('host'=>'192.168.2.86',
                                   'port'=>6379,
                                   'timeout'=>1,
                                   'db_index'=>4,//integer number. null means default db
                                   'password'=>null,//leave it NULL or remove this item if no authentication is required.
                                   'options'=>array(\Redis::OPT_SERIALIZER => 'msgpack'
                                                   )
                                  );
}
