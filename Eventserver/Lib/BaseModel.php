<?php

namespace Lib;

class ModelException extends \Exception
{

}

/**
 * Base model
 *
 * @todo cache method not suitable for large data
 */
class BaseModel {
    const CACHE_PREFIX = 'MEC_MODEL_CACHES:';
    protected static $relatedModel;
    protected $cacheTtl = 8640000;

    /**
     * business logical errors
     *
     * @var array
     */
    protected $errors = array ();

    /**
     * codes start with 71 are Model errors
     *
     * @param int $code
     * @param sting $message
     */
    protected function setError($code, $message) {
        $this->errors [] = array (
                'code' => $code,
                'message' => $message
        );
    }

    public function errors() {
        return $this->errors;
    }

    public function errorsToString($separator=',')
    {
        $str = array();
        foreach ($this->errors() as $error)
        {
            $str[] = $error['message'];
        }
        return implode($separator,$str);
    }

    /**
     * set the cache ttl for the magic cache method which is to be invoked the next time.
     *
     * @param int $ttl defualt 0 never expire. in seconds.
     */
    public function setCacheTtl($ttl=0)
    {
        $this->cacheTtl = (int) $ttl;
    }

    public function __call($name, $args)
    {
        if(substr($name, -9, 9) == 'WithCache')
        {//magic cache methods
            $nameNoCache = substr($name, 0, -9);
            if(method_exists($this, $nameNoCache))
            {
                if($cache = $this->getCacheFacility())
                {
                    $cacheKey = $this->getCachePrefix().$nameNoCache.':'.md5(var_export($args, true));
                    try{
                        $data = $cache->get($cacheKey, $exists);
                        if($exists){
                            return $data;
                        }
                    }catch(\Exception $ex){
                        error_log($ex);
                    }

                }

                $value = call_user_func_array(array($this, $nameNoCache), $args);
                if($cache)
                {   try{
                        $this->CacheSave($cacheKey, $value);
                    }catch(\Exception $ex){
                        error_log($ex);
                    }
                }
                return $value;
            }
        }

        $trace = debug_backtrace(null, 1);
        trigger_error('Call to undefined method '.get_called_class()."::{$name} in {$trace[0]['file']} on line {$trace[0]['line']}", E_USER_ERROR);
    }

    /**
     * get model level cache key prefixed with APP_NAME,common_prefix, MODEL_NAME
     * @todo 多项目集群缓存是,prefix区分项目
     */
    public function getCachePrefix()
    {
        $cachePrefix = self::CACHE_PREFIX;
        $cachePrefix .= get_class($this).':';
        $cachePrefix = str_replace('\\', '_', $cachePrefix);
        return $cachePrefix;
    }

    /**
     * clear caches on Model level
     */
    public function clearModelCache()
    {
        $keyPrefix = $this->getCachePrefix();
        $redis = $this->getCacheFacility();
        if($redis)
        {
            $cleanCmds = "for k,v in pairs(redis.call('keys','*')) do redis.call('del',v) end return true";
            foreach($redis->_hosts() as $host){
                $redis->_instance($host)->eval($cleanCmds);
            }
        }
    }

    protected function getCacheFacility()
    {
        if(!class_exists('\Lib\Redis') || !method_exists('\Redis', 'eval'))
        {
            return false;
        }
        try{
            $redisCfgs = \Lib\Util\Sys::getAppCfg('Redis');
        }
        catch(\Exception $ex)
        {
            error_log($ex);
            return false;
        }

            if(!property_exists($redisCfgs, 'cache'))
            {
                return false;
            }
        try{
            return \Lib\Redis::instance('cache');
        }
        catch(\Exception $ex)
        {
            error_log($ex);
            return false;
        }
    }

    protected function CacheSave($key, $value)
    {
        $redis = $this->getCacheFacility();
        if(!$redis)
        {
            return false;
        }
        return  $redis->set($key, $value);
    }

    /**
     * @return array
     */
    public static function getRelatedModel()
    {
        return static::$relatedModel;
    }
}
