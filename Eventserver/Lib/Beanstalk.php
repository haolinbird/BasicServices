<?php
namespace Lib;

/**
 * root of the pheanstalk lib.
 * @var string
 */
define('PHEANSTALK_ROOT', SYS_ROOT.'Lib'.DS.'ThirdParty'.DS.'Pheanstalk'.DS);
require_once PHEANSTALK_ROOT.DS.'ClassLoader.php';
\Pheanstalk_ClassLoader::register(PHEANSTALK_ROOT.'..');
/**
 * Beanstalk client.
 * Refer to {@link
 * https://github.com/kr/beanstalkd/blob/v1.3/doc/protocol.txt}.<br />
 * Derived from class Beanstalk that provided by pecl extension beanstalk. Refer
 * to {@link https://github.com/nil-zhang/php-beanstalk/}
 *
 * @author Su Chao<suchaoabc@163.com>
 * @uses Cfg\Beanstalk,\Beanstalk
*/
class Beanstalk extends \Pheanstalk_Pheanstalk
{
    /**
     * each configuration should has only one instance.
     *
     * @var array
     */
    protected static $instances = array ();

    protected  $instancePools = array();

    protected $instanceWeights = array();
    protected $host;
    protected $port;

    protected $id = null;

    /**
     * Get a beanstalk client instance per endpoint.
     *
     * @param string|array $endpoint
     * @return \Lib\Beanstalk
    */
    public static function instance($endpoint = 'default')
    {
        $index = '';
        if(is_array($endpoint)){
            $index = md5(serialize($endpoint));
        }
        else{
            $index = $endpoint;
        }
        if(!isset ( self::$instances [$index]))
        {
            self::$instances[$index] = new static($endpoint);
            self::$instances[$index]->id = $index;
        }
        return self::$instances[$index]->selectServer();
    }

    /**
     *
     * @param string|array $endpoint Which configuration to use.
     * @return null
     */
    public function __construct($endpoint = 'default', $host = null, $port = null)
    {
        if(!is_null($host) && !is_null($port)){
            $this->host = $host;
            $this->port = $port;
        }
        if(is_null($endpoint)) return;
        $hosts = array();
        if(is_string($endpoint)){
            $cfg = \Lib\Util\Sys::getAppCfg('Beanstalk');
            if(!empty($cfg::$$endpoint)) {
                $hosts = $cfg::$$endpoint;
            }
        } else if(is_array($endpoint)){
            $hosts = &$endpoint;
        }

        foreach($hosts as $srv)
        {
            if(empty($srv['port'])) $srv['port'] = 11300;
            if(empty($srv['weight'])) $srv['weight'] = 1;
            $this->addServer($srv['host'], $srv['port'], $srv['weight']);
        }

    }

    public function selectServer()
    {
        static $srvs = array();
        if(isset($srvs[$this->id]) && ! is_null($srvs[$this->id])){
            return $srvs[$this->id];
        }
        //random hash by weight
        $randArray = array();
        foreach($this->instanceWeights as $k=>$v)
        {
            $repeat = (int)$v;
            $randArray = array_pad($randArray, count($randArray)+$repeat, $k);
        }
        $randIndex = rand(0, count($randArray) -1);
//         var_dump($randArray[$randIndex]);die;
        $srvs[$this->id] = $this->instancePools[$randArray[$randIndex]];
        $srvs[$this->id]->connect($srvs[$this->id]->host, $srvs[$this->id]->port);
        return $srvs[$this->id];
    }

    /**
     * get the instance pool
     *
     * @return array
     */
    public static function getInstances()
    {
        return self::$instances;
    }

    public function getInstancePools()
    {
        return $this->instancePools;
    }

    /**
     * destroy a specified instance
     *
     * @param string $name instance configuration name
     * @return \Lib\Beanstalk
     */
    public static function destroyInstance($name)
    {
        self::$instances[$name] = null;
        unset(self::$instances[$name]);
        return $this;
    }

    /**
     * Add servers to the client link pool.
     * @param string $host ip address
     * @param int $port port number
     * @param float $weight a number to indicate the server weight
     * @todo multiple server/failover is to be supported.
     */
    public function addServer($host, $port = 11300, $weight = 1)
    {
        if($weight < 1)
        {
            throw new \Pheanstalk_Exception('weigth less than 1 !');
        }
        static $index = 0;
        $srv = new self(null, $host, $port);
        $this->instancePools[$index] = $srv;
        $this->instanceWeights[$index++] = $weight;
        $srv->instancePools = &$this->instancePools;
        $srv->instanceWeights = &$this->instanceWeights;
        return $this;
    }

    /**
     * connect to a single server.
     * @param string $host
     * @param number $port
     * @return \Lib\Beanstalk
     */
    public function connect($host, $port = 11300)
    {
        parent::__construct($host, $port, 0.06);
        return $this;
    }

    public function getConnection()
    {
        $conn = parent::getConnection();
        if(is_null($conn)){
            parent::__construct($this->host, $this->port);
            $conn = parent::getConnection();
        }
        return $conn;
    }
}
