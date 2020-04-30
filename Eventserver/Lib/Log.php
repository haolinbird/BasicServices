<?php

namespace Lib;

class Log {
    /**
     *
     * @var log instances
     */
    protected static $loggers = array ();

    /**
     * available loggers of the current version
     *
     * @var array
     */
    protected static $availableLoggers = array (
            'php',
            'file',
            'mongo',
            'jsonfile'
    );

    /**
     * Log record will be prefixed with time.
     * @var boolean
     */
    protected $prefixedWithTime=true;

    protected $useRawMessage = false;

    /**
     * config of the current logger
     *
     * @var array
     */
    protected $loggerCfg;

    /**
     * configuration name of the logger , which defined in \Core\Config\Log
     * @var string
     */
    protected $cfgName;

    /**
     * maps of the E_USER* error types to their comment strings in php error logs.
     * @var array
     */
    protected static $phpUserErrorTypeToStringMap = array(
              E_USER_NOTICE => 'Notice',
              E_USER_WARNING => 'Warning',
              E_USER_DEPRECATED => 'Deprecated',
              E_USER_ERROR => 'Fatal error'
    );

    protected function __construct($cfgName) {
        $logConfig = Util\Sys::getAppCfg('Log');
        if (! property_exists ($logConfig, $cfgName ))
            return false;
        $cfg = $logConfig::$$cfgName;
        if (! in_array ( $cfg ['logger'], self::$availableLoggers )) {
            return false;
        }
        $this->loggerCfg = $cfg;
        $this->cfgName = $cfgName;
    }
    /**
     *
     * @param string $cfg
     * @return \Lib\Log
     */
    public static function instance($cfgName = 'default') {
        if (! isset ( self::$loggers [$cfgName] )) {
            self::$loggers [$cfgName] = new self ( $cfgName );
        }
        return self::$loggers [$cfgName];
    }
    public function log($msg, $options=array()) {
        if (! $this->loggerCfg)
            return false;
        switch ($this->loggerCfg ['logger']) {
            case 'php' :
                return $this->phpLogger($msg, $options) ? $this : false;
                continue;
            case 'sys' :
                // @todo implementation
                continue;
            case 'file' :
                return $this->fileLogger ( $msg ) ? $this : false;
                continue;
            case 'mongo' :
                return call_user_func_array(array($this, 'mongoLogger'), func_get_args()) ? $this : false;
                continue;
            case 'jsonfile' :
                $this->useRawMessage = true;
                $this->prefixedWithTime = false;
                return $this->jsonfileLogger($msg) ? $this : false;
                continue;
        }
    }

    protected function fileLogger($msg)
    {
        $logConfig = Util\Sys::getAppCfg('Log');
        $logDir = $logConfig::FILE_LOG_ROOT.DIRECTORY_SEPARATOR.$this->cfgName . DIRECTORY_SEPARATOR;
        $timefile = $this->cfgName.'.log';
        if (! is_dir ( $logDir ) && ! @mkdir ( $logDir, 0777, true ) && !is_writable ( $logDir )) {
            return false;
        }
        if(!$this->useRawMessage)
        {
            $msg = $this->formatMessage($msg);
        }
        return file_put_contents ( $logDir . $timefile, $msg, FILE_APPEND );
    }

    /**
     * @param array $msg
     */
    protected function mongoLogger($msg)
    {
        if(!isset($this->loggerCfg['dbConfigName']))
        {
            throw new LogException('Invalid log config of mongo handler, $dbConfigName missing!');
        }
        if(isset($msg['time']))
        {
            $msg['time'] = new \MongoDate(strtotime($msg['time']));
        }
        $db = Mongo::instance($this->loggerCfg['dbConfigName'])->selectDB()->selectCollection($this->loggerCfg['dbCollection']);
        if(is_array($msg))
        {
            if(is_array(current($msg)))
            {
                $re = true;
                foreach ($msg as $record)
                {
                    if(!$db->save($record))
                    {
                        $re = false;
                    }
                }
                return $re;
            }
            else
            {
                return $db->save($msg);
            }
        }
        else
        {
            throw new LogException('$msg for mongo handler should be array!');
        }
    }

    /**
     * send log message to the php system log that defined as error_log in php.ini. messages are formatted as the standard php errors , default error type is E_NOTICE.
     *
     * @param string $msg message body
     * @param array $options availabe options are  'type': error_type which introduce within E_USER_* default is E_USER_NOTICE;'trace_depth': trace depth, default is 1, which means just only record the trace line where you called the "log" method.
     */
    protected function phpLogger($msg, $options)
    {
        if(!is_array($options))
        {
            $options = array();
        }
        $type = isset($options['type']) && in_array($options['type'], array_keys($this::$phpUserErrorTypeToStringMap))? $options['type'] : E_USER_NOTICE;
        $traceDepth = isset($options['trace_depth']) ? (int) $options['trace_depth'] : 1;
        $msg = 'PHP '.$this::$phpUserErrorTypeToStringMap[$type].': '.$msg.' ' ;
        $traceStr = '';
        $traceDepth += 1 ;//count the call of self::Log in trace.
        $traceStr = ' Stack Trace: ';
        if(version_compare(PHP_VERSION, '5.4.0', '>='))
        {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $traceDepth);
        }
        else
        {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }
        unset($trace[0]);//remove the trace of self::Log
        if (sizeof($trace) > 0)
        {
            foreach ($trace as $k => $v)
            {
                extract($v);
                $traceStr .= $k.'. in '.$file.':'.$line . '\n';
            }
           return error_log($msg.$traceStr);
        }
        return false;
    }

    /**
     * Log message object as json to file.
     *
     * @param array $data
     */
    protected function jsonfileLogger($data)
    {
        if(isset($this->loggerCfg['fields'])){
            $data = array_combine($this->loggerCfg['fields'], $data);
            if(!$data)return false;
        }
        $data['log_create_time'] = time();
        $data['log_create_time_hr'] = date('Y-m-d H:i:s', $data['log_create_time']);
        $msg = json_encode($data, 256)."\n";
        return $this->fileLogger($msg);
    }

    protected function formatMessage($msg)
    {
        $msg = str_replace(array("\n","\r\n"), '\n',$msg);
        if($this->prefixedWithTime)
        {
            $msg = date('Ymd H:i:s ').$msg;
        }
        $msg .="\n";
        return $msg;
    }
}

class LogException extends \Exception{

}