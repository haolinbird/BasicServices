<?php

namespace Lib;

class RpcBase {
    const PROTOCOL_JSON = 'json';
    const PROTOCOL_MSGPACK = 'msgpack';
    const PROTOCOL_PHP = 'php';
    protected $protocol = 'json';
    protected function deserialize($data) {
        switch ($this->protocol) {
            case self::PROTOCOL_JSON :
                return json_decode ( $data, true );
                continue;
            case self::PROTOCOL_MSGPACK :
                return msgpack_unpack ( $data );
                continue;
            case self::PROTOCOL_PHP :
                return unserialize ( $data );
                continue;
            default :
                return false;
        }
    }
    protected function serialize($data) {
        switch ($this->protocol) {
            case self::PROTOCOL_JSON :
                return json_encode ( $data );
                continue;
            case self::PROTOCOL_MSGPACK :
                return msgpack_pack ( $data );
                continue;
            case self::PROTOCOL_PHP :
                return serialize ( $data );
                continue;
            default :
                return false;
        }
    }
    /**
     * <h4>Data</h4>
     * <pre>
     * user => .
     * ..
     * secret_key => ...
     * class => ...
     * method => ...
     * </pre>
     *
     * @param array $data
     * @return string
     */
    public function generateSign($data) {
        return md5 ( $data ['user'] . $data ['secret_key'] . $data ['class'] . $data ['method'] );
    }
}
/**
 * Rpc execption classes are bundled whtin the RpcBase,because exceptions are
 * delivered between the client and server sides.<br />
 * Exception code as below:<br/>
 * <b>Error code start with "52" are redis exceptions.</b>
 * <pre>
 * <code>
 * 52000 => bad client RPC request data
 * 51000 => Server error.
 * </code>
 * </pre>
 *
 * @author Su Chao<suchaoabc@163.com>
 */
class RpcServerException extends \Exception {
}

/**
 * transaction logical exceptions.<br />
 * Exception code as below:<br/>
 * <b>Error code start with "53" are redis exceptions.</b><br/>
 *
 * @author Su Chao<suchaoabc@163.com>
 */
class RpcLogicalException extends \Exception {
    protected $rpcFile, $rpcLine, $rpcTrace, $rpcTraceString, $rpcExceptionClass;
    public function __construct($e) {
        parent::__construct ( $e->getMessage (), $e->getCode () );
        $this->rpcExceptionClass = get_class ( $e );
        $this->rpcFile = $e->getFile ();
        $this->rpcLine = $e->getLine ();
        $this->rpcTrace = $e->getTrace ();
        $this->rpcTraceString = $e->getTraceAsString ();
    }
    public function rpcTrace() {
        return $this->rpcTrace;
    }
    public function rpcTraceString() {
        return $this->rpcTraceString;
    }
    public function rpcExceptionClass() {
        return $this->rpcExceptionClass;
    }
    public function __toString()
    {
        return 'exception \''.$this->rpcExceptionClass .'\' with message \''.$this->getMessage().'\' in file '.$this->rpcFile.':'.$this->rpcLine."\nStack trace:\n". trim($this->rpcTraceString, "\t ");
    }
}