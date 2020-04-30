<?php

namespace Lib;

/**
 * Exceptions about redis.RedisException is bundled with {@link \Lib\Redis} in
 * the same script file.<br />
 * Exception code as below:<br/>
 * <b>Error code start with "32" are redis exceptions.</b>
 * <pre>
 * <code>
 * 32000 => connection failure
 * 32100 => failed to select database
 * 32300 => connection authentication failed
 * 32400 => data save failure
 * </code>
 * </pre>
 *
 * @author Su Chao<suchaoabc@163.com>
 */
class RedisException extends \Exception {
}

/**
 * Redis client.
 * Refer to {@link https://github.com/nicolasff/phpredis}.<br />
 * Derived from class Redis that provided by pecl extension redis.<br />
 * **use a serializer rather than NONE will disable incr/decr and related
 * funcationalities***
 * <h4>Example</h4>
 * <code>
 * //Use a default link. all links are defined in Cfg\Redis
 * \Lib\Redis::instance()->hSet('Event:Subscription',array('b'=>'123'));
 * //or
 * $redis = new \Lib\Redis;
 * $redis->hSet('Event:Subscription',array('b'=>'123'));
 * //Use a specified link
 * \Lib\Redis::instance('koubei')->hSet('Event:Subscription',array('b'=>'123'));
 * //Link to any other server
 * $redis = new \Lib\Redis(null);
 * $redis->connect('127.0.0.3',4123,1)->hSet('Event:Subscription',array('b'=>'123'));
 * </code>
 *
 * @author Su Chao<suchaoabc@163.com>
 * @uses Cfg\Redis
 * @todo track msgpack issue {@link
 *       https://github.com/msgpack/msgpack-php/issues/8}
 */
class Redis extends \RedisArray {
    const SERIALIZER_MSGPACK = 'msgpack';
    protected $serializer;
    protected $enableSerialization = true;
    /**
     * each configuration should has only one instance.
     *
     * @var array
     */
    protected static $instances = array ();
    /**
     * Redis instance
     *
     * @var \Lib\Redis
     */
    protected static $singleInstance;
    /**
     * connection endpoint of the instance
     * @var string
     */
    protected $endpoint;
    /**
     * Get a redis client instance per endpoint.
     *
     * @param string $endpoint
     *            if is a valid endpoint it will connect to the server
     *            automatically, othewise you need to call method "connect"
     *            manually.<br />
     *            e.g.
     *            <pre>
     *            \Lib\Redis::instance('default')->get('key1');//get instance
     *            with a valid endpoint
     *            \Lib\Redis::instance(null)->connect('127.0.0.1')->get('key2');//null
     *            endpoint , then need to connect manually.
     *            </pre>
     * @return \Lib\Redis
     */
    public static function instance($endpoint = 'default') {
        if (is_null ( $endpoint )) {
            if (self::$singleInstance instanceof self) {
                return self::$singleInstance;
            } else {
                self::$singleInstance = new self ( null );
                return self::$singleInstance;
            }
        } else if (! isset ( self::$instances [$endpoint] )) {
            self::$instances [$endpoint] = new self ( $endpoint );
        }
        return self::$instances [$endpoint];
    }
    /**
     *
     * @param string $endpoint
     *            Which configuration to use.
     */
    public function __construct($endpoint = 'default') {
        $cfgs = \Lib\Util\Sys::getAppCfg('Redis');
        if (! is_null ( $endpoint ) && ! empty ( $cfgs::$$endpoint )) {
            $srv = $cfgs::$$endpoint;
            if (empty ( $srv ['host'] ))
                $srv ['host'] = '127.0.0.1';
            if (empty ( $srv ['port'] ))
                $srv ['port'] = 6379;
            if (empty ( $srv ['timeout'] ))
                $srv ['timeout'] = $cfgs::CONNECTION_TIMEOUT;
            $this->connect ( $srv ['host'], $srv ['port'], $srv ['timeout'] );
            if (! empty ( $srv ['password'] )) {
                $authed = $this->auth ( $srv ['password'] );
                if (! $authed) {
                    throw new RedisException ( 'Failed to auth connection with password. Please check your configurations.', 32300 );
                }
            }
            if (isset ( $srv ['options'] ) && is_array ( $srv ['options'] )) {
                foreach ( $srv ['options'] as $k => $v ) {
                    $this->setOption ( $k, $v );
                }
            }
            if (! empty ( $srv ['db_index'] )) {
                $result = $this->select ( $srv ['db_index'] );
                if (! $result) {
                    throw new RedisException ( 'Failed to select database(' . $srv ['db_index'] . '). Please check your configurations.', 32100 );
                }
            }
        }

        $this->endpoint = $endpoint;
    }
    public function connect($host, $port = null, $timeout = null) {
        $hosts = explode(',', $host );
        $ports = explode(',', $port );
        $lastPortIndex = 0;
        foreach($hosts as $k => $v){
            if(isset($ports[$k])){
                $lastPortIndex = $k;
            }
            $hosts[$k] = $v.':'.$ports[$lastPortIndex];
        }
        parent::__construct($hosts, array("connect_timeout" => $timeout, 'lazy_connect'=>false));
        return $this;
    }

    /**
     * re-establish connection for the current instance.
     */
    public function reEstablishConnection()
    {
        if(is_null($this->endpoint))
        {
            self::$singleInstance = self::instance(null);
            return self::$singleInstance;
        }

        return self::$instances[$this->endpoint] = new self($this->endpoint);
    }

    /**
     * enable serialization.
     */
    public function enableSerialization() {
        return $this->enableSerialization = true;
    }
    /**
     * return all the connection instances
     */
    public static function getInstances()
    {
        return self::$instances;
    }
    public function getEndpoint()
    {
        return $this->endpoint;
    }
    /**
     * force to disable serialization, even if a serializer is available.<br />
     * this is usefull when you want to use certain redis functions and
     * operations e.g.
     * incr,incryByFloat,decr
     *
     * @return boolean
     */
    public function disableSerialization() {
        return $this->enableSerialization = false;
    }
    /**
     * set as PHP data structure
     *
     * @param string $key
     * @param string $hashKey
     * @param mixed $value
     */
    public function hSet($key, $hashKey, $value) {
        $value = $this->serialize ( $value );
        return parent::hSet ( $key, $hashKey, $value );
    }

    /**
     * get as PHP data structure
     *
     * @param string $key
     * @param string $hashKey
     * @return mixed
     */
    public function hGet($key, $hashKey) {
        $value = parent::hGet ( $key, $hashKey );
        if ($value !== false) {
            return $this->deserialize ( $value );
        }
        return false;
    }
    public function hGetAll($key) {
        $hashKeys = parent::hKeys ( $key );
        $values = array ();
        foreach ( $hashKeys as $hashKey ) {
            $values [$hashKey] = $this->hGet ( $key, $hashKey );
        }
        return $values;
    }

    /**
     * Returns the whole hash, as an array of PHP data structure indexed by
     * strings
     *
     * @param string $key
     */
    public function hVals($key) {
        $values = parent::hVals ( $key );
        foreach ( $values as $k => $value ) {
            $values [$k] = $this->deserialize ( $value );
        }
        return $values;
    }

    /**
     * set as PHP data structure only if this field isn't already in the hash.
     *
     * @param string $key
     * @param string $hashKey
     * @param mixed $value
     * @return boolean TRUE if the field was set, FALSE if it was already
     *         present.
     */
    public function hSetNx($key, $hashKey, $value) {
        $value = $this->serialize ( $value );
        return parent::hSetNx ( $key, $hashKey, $value );
    }

    /**
     * Fills in a whole hash as PHP structure.
     * <h3>Examples</h3>
     * <code>
     * $redis->delete('user:1');
     * $redis->hMset('user:1', array('name' => 'Joe',
     * 'salary' => 2000));
     * $redis->hIncrBy('user:1', 'salary', 100); // Joe earns 100 more now.
     * </code>
     *
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function hMset($key, $value) {
        foreach ( $value as $k => $v ) {
            $value [$k] = $this->serialize ( $v );
        }
        return parent::hMset ( $key, $value );
    }

    /**
     * Retrieve the values associated to the specified fields in the hash as PHP
     * data.
     *
     * @param string $key
     * @param array $memberkeys
     */
    public function hMget($key, $memberkeys) {
        $values = parent::hMget ( $key, $memberkeys );
        foreach ( $values as $k => $v ) {
            if (false === $v) {
                $values [$k] = $v;
                continue;
            }
            $values [$k] = $this->deserialize ( $v );
        }
        return $values;
    }
    /**
     *
     * @param
     *            mixed
     * @return string
     */
    public function serialize($value) {
        if (! $this->enableSerialization)
            return $value;
        switch ($this->serializer) {
            case 'msgpack' :
                return msgpack_pack ( $value );
            case 1 :
                return serialize ( $value );
            default :
                return $value;
        }
    }
    /**
     *
     * @param string|array $value
     * @return mixed
     */
    public function deserialize($value) {
        if (! $this->enableSerialization)
            return $value;
        if (is_array ( $value )) {
            foreach ( $value as $k => $v ) {
                $value [$k] = $this->deserialize ( $v );
            }
            return $value;
        }
        switch ($this->serializer) { // since string and numbers are not serialized thus cant be
          // unserialized and false is returned, then it should return the
          // original string
            case 'msgpack' :
                $return = @msgpack_unpack ( $value );
                return $return;
            case 1 :
                $return = @unserialize ( $value );
                return $return;
            default :
                return $value;
        }
    }
    public function setOption($name, $value) {
        if ($name == \Redis::OPT_SERIALIZER) { // disable this option for parent class
            $this->serializer = $value;
            return true;
        }
        return parent::setOption ( $name, $value );
    }
    public function getOption($name) {
        if ($name == \Redis::OPT_SERIALIZER) { // disable this option for parent class
            return $this->serializer;
        }
        return parent::getOption ( $name );
    }
    public function set($key, $value) {
        $value = $this->serialize ( $value );
        return parent::set ( $key, $value );
    }
    public function get($key, &$exists) {
        $value = parent::get ( $key );
        if (false === $value) {
            $exists = false;
            return false;
        }
        $exists = true;
        return $this->deserialize ( $value );
    }
    /**
     * Set the string value in argument as value of the key, with a time to
     * live.
     *
     * @param string $key
     * @param int $ttl
     *            in seconds
     * @param mixed $value
     */
    public function setex($key, $ttl, $value) {
        $value = $this->serialize ( $value );
        return parent::setex ( $key, $ttl, $value );
    }

    /**
     * Set the string value in argument as value of the key, with a time to
     * live.
     *
     * @param string $key
     * @param int $ttl
     *            in milliseconds seconds
     * @param mixed $value
     */
    public function psetex($key, $ttl, $value) {
        $value = $this->serialize ( $value );
        return parent::psetex ( $key, $ttl, $value );
    }
    /**
     * Set the string value in argument as value of the key if the key doesn't
     * already exist in the database.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setnx($key, $value) {
        $value = $this->serialize ( $value );
        return parent::setnx ( $key, $value );
    }

    /**
     * Get the values of all the specified keys.
     * If one or more keys dont exist, the array will contain FALSE at the
     * position of the key.
     *
     * @param array $keys
     */
    public function mGet($keys) {
        $values = array ();
        foreach ( $keys as $key ) {
            $values [] = $this->get ( $key );
        }
        return $values;
    }

    /**
     * Adds the string value to the head (left) of the list.
     * Creates the list if the key didn't exist. If the key exists and is not a
     * list, FALSE is returned.
     *
     * @param string $key
     * @param mixed $value
     * @return long he new length of the list in case of success, FALSE in case
     *         of Failure.
     */
    public function lPush($key, $value) {
        $return = parent::lPush ( $key, $this->serialize ( $value ) );
        if($return === false)
        {
            throw new RedisException("Failed to save data. Maybe connection problem ocurred", 32400);
            
        }
        return $return;
    }

    /**
     * Adds the string value to the tail (right) of the list.
     * Creates the list if the key didn't exist. If the key exists and is not a
     * list, FALSE is returned.
     *
     * @param string $key
     * @param mixed $value
     * @return long he new length of the list in case of success, FALSE in case
     *         of Failure.
     */
    public function rPush($key, $value) {
        $return = parent::rPush ( $key, $this->serialize ( $value ) );
        if($return === false)
        {
            throw new RedisException("Failed to save data. Maybe connection problem ocurred", 32400);
            
        }
        return $return;        
    }
    /**
     * Adds the string value to the head (left) of the list if the list exists.
     *
     * @param string $key
     * @param mixed $value
     * @return long he new length of the list in case of success, FALSE in case
     *         of Failure.
     */
    public function lPushx($key, $value) {
        $return = parent::lPushx ( $key, $this->serialize ( $value ) );
        if(!$return === false)
        {
            throw new RedisException("Failed to save data. Maybe connection problem ocurred", 32400);
            
        }
        return $return;        
    }
    /**
     * Adds the string value to the tail (right) of the list if the ist exists.
     * FALSE in case of Failure.
     *
     * @param string $key
     * @param mixed $value
     * @return long he new length of the list in case of success, FALSE in case
     *         of Failure.
     */
    public function rPushx($key, $value) {
        $return = parent::rPushx ( $key, $this->serialize ( $value ) );
        if($return === false)
        {
            throw new RedisException("Failed to save data. Maybe connection problem ocurred", 32400);
            
        }
        return $return;
    }
    /**
     * Return and remove the first element of the list.
     *
     * @param string $key
     * @return mixed value if command executed successfully , FALSE in case of
     *         failure (empty list)
     */
    public function lPop($key) {
        $value = parent::lPop ( $key );
        if (false !== $value) {
            return $this->deserialize ( $value );
        }
        return false;
    }
    /**
     * Return and remove the last element of the list.
     *
     * @param string $key
     * @return mixed value if command executed successfully , FALSE in case of
     *         failure (empty list)
     */
    public function rPop($key) {
        $value = parent::rPop ( $key );
        if (false !== $value) {
            return $this->deserialize ( $value );
        }
        return false;
    }

    /**
     * Is a blocking lPop(rPop) primitive.
     * If at least one of the lists contains at least one element, the element
     * will be popped from the head of the list and returned to the caller. Il
     * all the list identified by the keys passed in arguments are empty, blPop
     * will block during the specified timeout until an element is pushed to one
     * of those lists. This element will be popped.<br />
     * returns false when no element can be returned, this is different from
     * PARENT class will throws a RedisException.
     * <h4>Example</h4>
     * <code>
     * //Non blocking feature
     * $redis->lPush('key1', 'A');
     * $redis->delete('key2');
     *
     * $redis->blPop('key1', 'key2', 10); // array('key1', 'A')
     * // OR
     * $redis->blPop(array('key1', 'key2'), 10); // array('key1', 'A')
     *
     * $redis->brPop('key1', 'key2', 10); // array('key1', 'A')
     * // OR
     * $redis->brPop(array('key1', 'key2'), 10); // array('key1', 'A')
     *
     * // Blocking feature
     *
     * // process 1
     * $redis->delete('key1');
     * $redis->blPop('key1', 10);
     * // blocking for 10 seconds
     *
     * // process 2
     * $redis->lPush('key1', 'A');
     *
     * / process 1
     * // array('key1', 'A') is returned
     * </code>
     *
     * @param string $key1[,$key2,[$key3...]]
     * @param int $timeout
     */
    public function blPop($key1, $timeout) {
        try {
            $value = call_user_func_array ( array (
                    'parent',
                    'blPop'
            ), func_get_args () );
            $value [1] = $this->deserialize ( $value [1] );
        } catch ( \RedisException $e ) {
            return false;
        }
    }

    /**
     *
     * @see blPop
     * @param string $key1[,$key2,[$key3...]]
     * @param int $timeout
     */
    public function brPop($key1, $timeout) {
        try {
            $value = call_user_func_array ( array (
                    'parent',
                    'brPop'
            ), func_get_args () );
            $value [1] = $this->deserialize ( $value [1] );
        } catch ( \RedisException $e ) {
            return false;
        }
    }

    /**
     * Return the specified element of the list stored at the specified key.<br
     * />
     * 0 the first element, 1 the second .
     * .. -1 the last element, -2 the penultimate ... Return FALSE in case of a
     * bad index or a key that doesn't point to a list.
     * <h4>Example</h4>
     * <code>
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C'); // key1 => [ 'A', 'B', 'C' ]
     * $redis->lGet('key1', 0); // 'A'
     * $redis->lGet('key1', -1); // 'C'
     * $redis->lGet('key1', 10); //FALSE`
     * </code>
     *
     * @param string $key
     * @param int $index
     */
    public function lIndex($key, $index) {
        $value = parent::lIndex ( $key, $index );
        if (false !== $value) {
            return $this->deserialize ( $value );
        }
        return false;
    }
    /**
     * alias of {@link lIndex}
     *
     * @param string $key
     * @param int $index
     */
    public function lGet($key, $index) {
        return $this->lIndex ( $key, $index );
    }

    /**
     * Set the list at index with the new value.
     *
     * @param string $key
     * @param int $index
     * @param mixed $value
     */
    public function lSet($key, $index, $value) {
        return parent::lSet ( $key, $index, $this->serialize ( $value ) );
    }

    /**
     * Returns the specified elements of the list stored at the specified key in
     * the range [start, end].
     * start and stop are interpretated as indices: 0 the first element, 1 the
     * second ... -1 the last element, -2 the penultimate ...
     * <h4>Example</h4>
     * <code>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');
     * $redis->lRange('key1', 0, -1); // array('A', 'B', 'C')
     * </code>
     *
     * @param string $key
     * @param int $start
     * @param int $end
     */
    public function lRange($key, $start, $end) {
        $values = parent::lRange ( $key, $start, $end );
        if (false === $values)
            return false;
        return $this->deserialize ( $values );
    }

    /**
     * alias of {@link lRange}
     *
     * @param string $key
     * @param int $start
     * @param int $end
     */
    public function lGetRange($key, $start, $end) {
        return $this->lRange ( $key, $start, $end );
    }

    /**
     * Removes the first count occurences of the value element from the list.
     * If count is zero, all the matching elements are removed. If count is
     * negative, elements are removed from tail to head.
     *
     * @param string $key
     * @param mixed $value
     * @param int $count
     * @return long the number of elements to remove; FALSE if the value
     *         identified by key is not a list.
     */
    public function lRem($key, $value, $count) {
        $value = $this->serialize ( $value );
        return parent::lRem ( $key, $value, $count );
    }
    /**
     * alias of {@link lRem}
     *
     * @param string $key
     * @param mixed $value
     * @param int $count
     */
    public function lRemove($key, $value, $count) {
        return $this->lRem ( $key, $value, $count );
    }

    /**
     * Insert value in the list before or after the pivot value.
     * the parameter options specify the position of the insert (before or
     * after). If the list didn't exists, or the pivot didn't exists, the value
     * is not inserted.
     *
     * @param string $key
     * @param int $position
     *            \Redis::BEFORE | \Redis::AFTER
     * @param mixed $pivot
     * @param mixed $value
     * @return The number of the elements in the list, -1 if the pivot didn't
     *         exists.
     */
    public function lInsert($key, $position, $pivot, $value) {
        $pivot = $this->serialize ( $pivot );
        $value = $this->serialize ( $value );
        return parent::lInsert ( $key, $position, $pivot, $value );
    }

    /**
     * Adds a value to the set value stored at key.
     * If this value is already in the set, FALSE is returned.
     *
     * @param string $key
     * @param mixed $value
     *            value1[,value2[,value3]...]
     * @return int the number of elements added to the set
     */
    public function sAdd($key, $value) {
        $args = func_get_args ();
        for($i = 1, $count = count ( $args ); $i < $count; $i ++) {
            $args [$i] = $this->serialize ( $args [$i] );
        }
        return call_user_func_array ( array (
                'parent',
                'sAdd'
        ), $args );
    }
    /**
     * Removes the specified member from the set value stored at key.
     *
     * @param string $key
     * @param mixed $value
     *            value1[,value2[,value3]...]
     * @return int The number of elements removed from the set.
     */
    public function sRem($key, $values) {
        $args = func_get_args ();
        for($i = 1, $count = count ( $args ); $i < $count; $i ++) {
            $args [$i] = $this->serialize ( $args [$i] );
        }
        return call_user_func_array ( array (
                'parent',
                'sRem'
        ), $args );
    }
    /**
     * alias of {@link sRem}
     *
     * @param string $key
     * @param mixed $value
     *            value1[,value2[,value3]...]
     * @return int The number of elements removed from the set.
     */
    public function sRemove($key, $values) {
        return $this->sRem ( $key, $values );
    }

    /**
     * Moves the specified member from the set at srcKey to the set at dstKey.
     *
     * @param string $srcKey
     * @param string $dstKey
     * @param mixed $value
     * @return boolean If the operation is successful, return TRUE. If the
     *         srcKey and/or dstKey didn't exist, and/or the member didn't exist
     *         in srcKey, FALSE is returned.
     */
    public function sMove($srcKey, $dstKey, $value) {
        $value = $this->serialize ( $value );
        return parent::sMove ( $srcKey, $dstKey, $value );
    }

    /**
     * Checks if value is a member of the set stored at the key key.
     *
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function sIsMember($key, $value) {
        $value = $this->serialize ( $value );
        return parent::sIsMember ( $key, $value );
    }

    /**
     * Removes and returns a random element from the set value at Key.
     *
     * @param string $key
     */
    public function sPop($key) {
        $value = parent::sPop ( $key );
        if (false === $value)
            return false;
        return $this->deserialize ( $value );
    }

    /**
     * Returns a random element from the set value at Key, without removing it.
     *
     * @param string $key
     */
    public function sRandMember($key) {
        $value = parent::sRandMember ( $key );
        if (false === $value)
            return false;
        return $this->deserialize ( $value );
    }
    /**
     * Returns the members of a set resulting from the intersection of all the
     * sets held at the specified keys.
     * If just a single key is specified, then this command produces the members
     * of this set. If one of the keys is missing, FALSE is returned.
     *
     * @param string $key
     *            key1[,key2[,key3]...]
     * @return Array contain the result of the intersection between those keys.
     *         If the intersection beteen the different sets is empty, the
     *         return value will be empty array.
     */
    public function sInter($key) {
        $values = call_user_func ( array (
                'parent',
                'sInter'
        ), func_get_args () );
        if (false === $values)
            return false;
        return $this->deserialize ( $values );
    }

    /**
     * Performs the union between N sets and returns it.
     *
     * @param string $key
     *            key1[,key2[,key3]...]
     */
    public function sUnion($key) {
        $value = parent::sUnion ( $key );
        if (false === $value)
            return false;
        return $this->deserialize ( $value );
    }

    /**
     * Performs the difference between N sets and returns it.
     *
     * @param string $key
     *            key1[,key2[,key3]...]
     */
    public function sDiff($key) {
        $value = $values = call_user_func ( array (
                'parent',
                'sDiff'
        ), func_get_args () );
        if (false === $value)
            return false;
        return $this->deserialize ( $value );
    }

    /**
     * Returns the contents of a set.
     *
     * @param string $key
     */
    public function sMembers($key) {
        $value = parent::sMembers ( $key );
        if (false === $value)
            return false;
        return $this->deserialize ( $value );
    }
    /**
     * alias of sMember
     *
     * @param string $key
     */
    public function sGetMembers($key) {
        return $this->sMembers ( $key );
    }

    /**
     * Sets a value and returns the previous entry at that key.
     *
     * @param string $key
     * @param mixed $value
     */
    public function getSet($key, $value) {
        $value = $this->serialize ( $value );
        $return = parent::getSet ( $key, $value );
        if (false === $return)
            return false;
        return $this->deserialize ( $return );
    }

    /**
     * Sets multiple key-value pairs in one atomic command.
     * MSETNX only returns TRUE if all the keys were set (see SETNX).
     *
     * @param unknown $pairs
     */
    public function mset(array $pairs) {
        foreach ( $pairs as $k => $v ) {
            $pairs [$k] = $this->serialize ( $v );
        }
        return parent::mset ( $pairs );
    }
    public function msetnx(array $pairs) {
        foreach ( $pairs as $k => $v ) {
            $pairs [$k] = $this->serialize ( $v );
        }
        return parent::msetnx ( $pairs );
    }

    /**
     * Adds the specified member with a given score to the sorted set stored at
     * key.
     *
     * @param string $key
     * @param float $score
     * @param mixed $value
     * @return int 1 if the element is added. 0 otherwise.
     */
    public function zAdd($key, $score, $value) {
        return parent::zAdd ( $key, $score, $this->serialize ( $value ) );
    }

    /**
     * Returns a range of elements from the ordered set stored at the specified
     * key, with values in the range [start, end].
     * start and stop are interpreted as zero-based indices: 0 the first
     * element, 1 the second ... -1 the last element, -2 the penultimate ...
     * <h4>Example</h4>
     * <code>
     * $redis->zAdd('key1', 0, 'val0');
     * $redis->zAdd('key1', 2, 'val2');
     * $redis->zAdd('key1', 10, 'val10');
     * $redis->zRange('key1', 0, -1); // array('val0', 'val2', 'val10')
     * // with scores
     * $redis->zRange('key1', 0, -1, true); /*
     * array(array('value'=>'val0','score' => 0), array('value'=>'val2','score'
     * => 2), array('value'=>'val10','score'=> 10))
     * </code>
     *
     * @param string $key
     * @param int $start
     * @param int $end
     * @param boolean $withscore[optional]
     *            default false
     */
    public function zRange($key, $start, $end, $withscore = false) {
        $values = parent::zRange ( $key, $start, $end, $withscore );
        if (! $withscore)
            return $this->deserialize ( $values );
        $valuesReal = array ();
        foreach ( $values as $value => $score ) {
            $valuesReal [] = array (
                    'value' => $this->deserialize ( $value ),
                    'score' => $score
            );
        }
        return $valuesReal;
    }

    /**
     * Deletes a specified member from the ordered set.
     *
     * @param string $key
     * @param mixed $member
     */
    public function zDelete($key, $member) {
        $member = $this->serialize ( $member );
        return parent::zDelete ( $key, $member );
    }

    /**
     * alias of zDelete
     *
     * @param string $key
     * @param mixed $member
     */
    public function zRem($key, $member) {
        return $this->zDelete ( $key, $member );
    }

    /**
     * Returns the elements of the sorted set stored at the specified key in the
     * range [start, end] in reverse order.
     * start and stop are interpretated as zero-based indices: 0 the first
     * element, 1 the second ... -1 the last element, -2 the penultimate ...
     *
     * @param string $key
     * @param int $start
     * @param int $end
     * @param boolean $withscore[optional]
     *            default false
     */
    public function zRevRange($key, $start, $end, $withscore = false) {
        $values = parent::zRevRange ( $key, $start, $end, $withscore );
        if (! $withscore)
            return $this->deserialize ( $values );
        $valuesReal = array ();
        foreach ( $values as $value => $score ) {
            $valuesReal [] = array (
                    'value' => $this->deserialize ( $value ),
                    'score' => $score
            );
        }
        return $valuesReal;
    }

    /**
     * Returns the elements of the sorted set stored at the specified key which
     * have scores in the range [start,end].
     * Adding a parenthesis before start or end excludes it from the range. +inf
     * and -inf are also valid limits. zRevRangeByScore returns the same items
     * in reverse order, when the start and end parameters are swapped.
     *
     * @param string $key
     * @param int $start
     * @param int $end
     * @param array $options
     *            Two options are available: withscores => TRUE, and limit =>
     *            array($offset, $count)
     */
    public function zRangeByScore($key, $start, $end, $options = array('withscores'=>false)) {
        $values = parent::zRangeByScore ( $key, $start, $end, $withscore );
        if (! $options ['withscores'])
            return $this->deserialize ( $values );
        $valuesReal = array ();
        foreach ( $values as $value => $score ) {
            $valuesReal [] = array (
                    'value' => $this->deserialize ( $value ),
                    'score' => $score
            );
        }
        return $valuesReal;
    }

    /**
     * Returns the elements of the sorted set stored at the specified key which
     * have scores in the range [start,end].
     * Adding a parenthesis before start or end excludes it from the range. +inf
     * and -inf are also valid limits. zRevRangeByScore returns the same items
     * in reverse order, when the start and end parameters are swapped.
     *
     * @param string $key
     * @param int $start
     * @param int $end
     * @param array $options
     *            Two options are available: withscores => TRUE, and limit =>
     *            array($offset, $count)
     */
    public function zRevRangeByScore($key, $start, $end, $options = array('withscores'=>false)) {
        $values = parent::zRevRangeByScore ( $key, $start, $end, $withscore );
        if (! $options ['withscores'])
            return $this->deserialize ( $values );
        $valuesReal = array ();
        foreach ( $values as $value => $score ) {
            $valuesReal [] = array (
                    'value' => $this->deserialize ( $value ),
                    'score' => $score
            );
        }
        return $valuesReal;
    }

    /**
     * Returns the score of a given member in the specified sorted set.
     *
     * @param string $key
     * @param mixed $member
     */
    public function zScore($key, $member) {
        return parent::zScore ( $key, $this->serialize ( $member ) );
    }

    /**
     * Returns the rank of a given member in the specified sorted set, starting
     * at 0 for the item with the smallest score.
     * zRevRank starts at 0 for the item with the largest score.
     *
     * @param string $key
     * @param mixed $member
     */
    public function zRank($key, $member) {
        return parent::zRank ( $key, $this->serialize ( $member ) );
    }
    /**
     *
     * @param string $key
     * @param mixed $member
     */
    public function zRevRank($key, $member) {
        return parent::zRevRank ( $key, $this->serialize ( $member ) );
    }

    /**
     * Increments the score of a member from a sorted set by a given amount.
     *
     * @param string $key
     * @param float $value
     * @param mixed $member
     * @param
     *            int the new value
     */
    public function zIncrBy($key, $value, $member) {
        return parent::zIncrBy ( $key, $value, $this->serialize ( $member ) );
    }
}
