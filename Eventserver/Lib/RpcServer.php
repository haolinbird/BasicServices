<?php
namespace Lib;

use App\Server\Model;

/**
 * <h3>Data structure.</h3>*class and method are case-sensitive !*
 * <pre>
 * {"user":"koubei",
 * "sign":"flewlEEl",
 * "class":"Broadcast",
 * "method":"Subscribe",
 * "params":["lion","1.5",{"head":31,"tail":60}]
 * }
 * </pre>
 *
 * @author Su Chao<suchaoabc@163.com>
 *         @use \App\Server\Cfg\Service
 */
class RpcServer extends RpcBase {
    protected static $instance;
    protected static $supportedprotocols = array (
            'json',
            'msgpack',
            'php'
    );
    protected $requestData;
    protected $returnData = array (
            'extra_output_string' => '',
            'error_msg' => array ()
    );
    protected $dryRun;
    protected function __construct()
    {

    }

    public static function instance()
    {
        if(!self::$instance)
        {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * Only openservices are available
     *
     * @param string $serviceName
     */
    public function hasService($serviceName) {
        $names = explode ( '::', $serviceName );
        $cfg = \Lib\Util\Sys::getAppCfg('Service');
        foreach ($cfg::$openServices as $v ) {
            if ($serviceName == $v || $v == $names [0] . '::*') {
                return true;
            }
        }
        return false;
    }

    public function getClientProtocol()
    {
        $protocol = isset ( $_REQUEST ['protocol'] ) ? $_REQUEST ['protocol'] : null;
        if(empty($protocol))
        {
            return $this->protocol;
        }
        if(in_array($protocol, self::$supportedprotocols ))
        {
            return $protocol;
        }
        throw new RpcServerException('Not supported protocol!');
    }

    /**
     *
     * @return \Lib\RpcServer
     */
    public function setDryRun()
    {
        $this->dryRun = true;
        return $this;
    }

    public function serve() {
        ob_start ();
        $cfg = \Lib\Util\Sys::getAppCfg('RpcServer');
        $protocolEx = null;
        try{
            $this->protocol = $this->getClientProtocol();
            $this->returnData ['protocol'] = $this->protocol;
        }
        catch(\Exception $protocolEx)
        {
            $this->assembleException($protocolEx);
        }

        if(!isset($_REQUEST['data']))
        {
            $this->requestData = array();
        }
        else
        {
            if (defined('\App\Server\Cfg\RpcServer::DEBUG') && $cfg::DEBUG)
            {
                $this->returnData ['request_raw_data'] = $_REQUEST ['data'];
            }
            $this->requestData = $this->deserialize ( $_REQUEST ['data'] );
        }

        if($protocolEx)
        {
            $this->assembleException($protocolEx);
        }
        else if(!$this->requestData || ! isset ( $this->requestData ['user'] ) || ! isset ( $this->requestData ['sign'] ) || ! isset ( $this->requestData ['class'] ) || ! isset ( $this->requestData ['method'] ) || ! isset ( $this->requestData ['params'] ))
        {
            $this->assembleException( new RpcServerException ( 'Bad rpc client request data: "'.var_export($this->requestData, true).'"', 52000 ) );
        }
        else if($this->dryRun || (defined('\App\Server\Cfg\RpcServer::DRY_RUN') && $cfg::DRY_RUN))
        {
            $this->returnData['return'] = true;
        }
        else
        {
            $this->logTransaction ( $this->requestData );
            try{
                $subscriber = $this->getClientInfo ( $this->requestData ['user'] );
            }
            catch(\Exception $ex)
            {
                $this->assembleException(new RpcServerException('Server error, '.get_class($ex).': '.$ex->getMessage(), 51000));
                $this->response();
                return false;
            }

            if($subscriber)
            {
                $clientSign = $this->generateSign ( array (
                        'user' => $this->requestData ['user'],
                        'secret_key' => $subscriber ['secret_key'],
                        'class' => $this->requestData ['class'],
                        'method' => $this->requestData ['method']
                ) );
            }

            if(!$subscriber || $clientSign != $this->requestData ['sign'])
            {
                $this->assembleException ( new RpcServerException ( 'Bad sign, please check your configurations !', 52005 ) );
            }
            else
            {
                if(! $this->hasService ( $this->requestData ['class'] . '::' . $this->requestData ['method'] ))
                {
                    $this->assembleException ( new RpcServerException ( 'Requested service is not available!', 52001 ) );
                }
                else
                {
                    try
                    {
                        $this->requestData['secret_key'] = $subscriber['secret_key'];
                        $processor = new \Lib\Processor ( $this->requestData ['class'], $this->requestData ['method'] );
                        $this->returnData ['return'] = call_user_func_array ( array (
                                $processor,
                                'execute'
                        ), $this->requestData ['params'] );
                    } catch ( \Exception $e ) {
                        $logicEx = new RpcLogicalException ( $e );
                        $this->assembleException ( $logicEx );
                    }
                }
            }
        }
        $this->response();
        return true;
    }

    protected function response()
    {
        $this->returnData ['extra_output_string'] = ob_get_clean ();
        $return = $this->serialize ( $this->returnData );
        echo $return;
    }
    protected function assembleException(\Exception $e) {
        $this->returnData ['Exception'] = $e->__toString();
    }
    public function errorHandler($errno, $errstr, $errfile, $errline) {
        $this->returnData ['error_msg'] [] = array (
                'error_no' => $errno,
                'string' => $errstr,
                'file' => $errfile,
                'line' => $errline
        );
        \Lib\Log::instance ( 'phpErrors' )->log ( 'PHP ' . $errno . ': ' . $errstr . ' in ' . $errfile . ' ' . $errline );
    }
    protected function getClientInfo($userKey) {
        $modelSubscriber = new Model\Subscriber ();
        return $subscriber = $modelSubscriber->getNormalSubscriberWithCache($userKey);
    }

    public function getRequestData()
    {
        return $this->requestData;
    }

    public function getReturnData()
    {
        return $this->returnData;
    }
    public function __get($name)
    {
        switch($name)
        {
            case 'requestData':
                return $this->getRequestData();
            default :
                trigger_error('Try to get undefined property of \''.$name.'\'class \''.__CLASS__.'\'.');
        }
    }

    /**
     *
     * @param string|array $message
     */
    protected function logTransaction($message) {
        $cfg = \Lib\Util\Sys::getAppCfg('RpcServer');
        $logStr = 'RPC request time:' . date ( 'Y-m-d H:i:s' ) . "\n";
        if (! is_string ( $message ))
            $message = var_export ( $message, true ) . "\n---------------\n";
        $logStr .= $message;
        if ($cfg::DEBUG) {
            \Lib\Log::instance ( 'rpcServer' )->log ( $logStr );
        }
    }
}