<?php
/**
 * This file is generated automatically by ConfigurationSystem.
 * Do not change it manually in production, unless you know what you're doing and can take responsibilities for the consequences of changes you make.
 */

namespace App\Server\Cfg;
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
    public static $default = array (
  'host' => '172.27.200.4',
  'db_index' => 3,
  'timeout' => 1,
  'port' => 6379,
  'options' => 
  array (
    1 => 'msgpack',
    3 => 1,
  ),
);

    // 压测环境.
    public static $default_bench = array (
  'host' => '172.29.11.14',
  'db_index' => 13,
  'timeout' => 1,
  'port' => 28001,
  'options' => 
  array (
    1 => 'msgpack',
    3 => 1,
  ),
);

    public static $cache = array (
  'host' => '172.27.200.4',
  'db_index' => 2,
  'timeout' => 1,
  'port' => '6379',
  'options' => 
  array (
    1 => 'msgpack',
    3 => 1,
  ),
);
}
