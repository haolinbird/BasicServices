<?php

namespace Lib;

/**
 *
 * @author Su Chao<suchaoabc@163.com>
 */
class Db extends \Pdo {
    protected static $writeConnections = array ();
    protected static $readConnections = array ();
    protected $queryBeginTime;
    protected $queryEndTime;
    protected $lastSql;
    protected $connectionCfg = array();
    public function __construct($dsn = null, $username = null, $passwd = null, $options = array()) {
        if (! is_null ( $dsn )) {
            parent::__construct ( $dsn, $username, $passwd, $options );
        }
    }
    /**
     *
     * @param string $name
     * @throws DbException
     * @return \Lib\Db
     */
    public function write($name = 'default') {
        if (empty ( self::$writeConnections[$name] ) && ! $this->addWriteConnection ($name)) {
            throw new DbException ( 'No available write connections. Please use addWriteConnection to initialize  first', 42001 );
        }
        return self::$writeConnections[$name];
    }
    /**
     *
     * @param string $name
     * @throws DbException
     * @return \Lib\Db
     * @todo connection name select
     */
    public function read($name = 'default') {
        if (empty ( self::$readConnections[$name] ) && ! $this->addReadConnection ($name)) {
            throw new DbException ( 'No available read connections. Please use addReadConnection to initialize  first', 42001 );
        }
        return  self::$readConnections[$name];
    }
    /**
     * initialize read connections
     *
     * @param string $name
     * @return \Lib\Db
     */
    public function addReadConnection($name = 'default') {
        $dbConfigs = \Lib\Util\Sys::getAppCfg('Db');
        if(isset($dbConfigs::$$name ) && ($dbConfig = $dbConfigs::$$name) && isset($dbConfig['read']))
        {
            $cfg = $dbConfig['read'];
            $connection = new self ( $cfg ['dsn'], $cfg ['user'], $cfg ['password'], $cfg ['options'] );
            $connection->connectionCfg['dsn'] = $cfg ['dsn'];
            $connection->connectionCfg['user'] = $cfg ['user'];
            $connection->connectionCfg['password'] = $cfg ['password'];
            $connection->connectionCfg['options'] = $cfg ['options'];
            self::$readConnections [$name] = $connection;
            return self::$readConnections [$name];
        } else {
            throw new DbException ( 'Read configuration of "' . $name . '" is not found.', 42003 );
        }
    }

    /**
     * initialize write connections
     *
     * @param string $name
     */
    public function addWriteConnection($name = 'default') {
        $dbConfigs = \Lib\Util\Sys::getAppCfg('Db');
        if(isset($dbConfigs::$$name ) && ($dbConfig = $dbConfigs::$$name) && isset($dbConfig['read']))
        {
            $cfg = $dbConfig['write'];
            $connection = new self ( $cfg ['dsn'], $cfg ['user'], $cfg ['password'], $cfg ['options'] );
            $connection->connectionCfg['dsn'] = $cfg ['dsn'];
            $connection->connectionCfg['user'] = $cfg ['user'];
            $connection->connectionCfg['password'] = $cfg ['password'];
            $connection->connectionCfg['options'] = $cfg ['options'];
            self::$writeConnections [$name] = $connection;
            return self::$writeConnections [$name];
        } else {
            throw new DbException ( 'Write configuration of "' . $name . '" is not found.', 42003 );
        }
    }
    /**
     *
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param array $options
     * @return \Lib\Db
     */
    public function connect($dsn, $user = null, $password = null, $options = array()) {
        if(is_array($dsn))
        {
            parent::__construct($this->connectionCfg['dsn'],$this->connectionCfg['user'],$this->connectionCfg['password'],$this->connectionCfg['options']);
        }
        else
        {
            parent::__construct ( $dsn, $user, $password, $options );
        }
        return $this;
    }
    public function insert($table, $data) {
        $data = $this->quote ( $data );
        $table = $this->quoteObj ( $table );
        if (is_array ( current ( $data ) )) {
            $columns = array_keys ( current ( $data ) );
            $columns = '(' . implode ( ',', $this->quoteObj ( $columns ) ) . ')';
            $values = array ();
            foreach ( $data as $v ) {
                $values [] = '(' . implode ( ',', $v ) . ')';
            }
            $values = implode ( ',', $values );
        } else {
            $columns = array_keys ( $data );
            $columns = '(' . implode ( ',', $this->quoteObj ( $columns ) ) . ')';
            $values = '(' . implode ( ',', $data ) . ')';
        }
        $sql = 'INSERT INTO ' . $table . $columns . ' VALUES' . $values;
        $re = $this->exec ( $sql );
        if (false === $re) {
            return false;
        } else {
            return $this->lastInsertId ();
        }
    }
    public function quote($data, $paramType = \PDO::PARAM_STR) {
        if (is_array ( $data ) || is_object ( $data )) {
            $return = array ();
            foreach ( $data as $k => $v ) {
                $return [$k] = $this->quote ( $v );
            }
            return $return;
        } else {
            $data = parent::quote ( $data );
            if (false === $data)
                $data = "''";
            return $data;
        }
    }

    /**
     * quote object names.
     * e.g. as mysql, a table name "user" will be quoted to "`user`".
     *
     * @param string|array $objName
     * @todo only mysql is currently supported.
     */
    public function quoteObj($objName) {
        if (is_array ( $objName )) {
            $return = array ();
            foreach ( $objName as $k => $v ) {
                $return [$k] = '`' . trim ( $v, '`' ) . '`';
            }
            return $return;
        } else {
            return '`' . trim ( $objName, '`' ) . '`';
        }
    }
    /**
     *
     * @param string $table
     * @param array $data
     * @param array|string $cond
     * @param string $logical
     *            AND/OR
     * @return int false on failure or the number of affected rows on success
     */
    public function update($table, $data, $cond, $logical = 'AND') {
        $condWhere = ' WHERE TRUE AND ';
        if (is_string ( $cond )) {
            $condWhere .= $cond;
        } else if (is_array ( $cond )) {
            $conds = array ();
            foreach ( $cond as $k => $v ) {
                if (is_int ( $k )) {
                    $conds [] = $v;
                } else {
                    $conds [] = $this->quoteObj ( $k ) . '=' . $v;
                }
            }
            $condWhere .= implode ( $logical, $conds );
        }
        $sql = 'UPDATE ' . $this->quoteObj ( $table );
        $values = array ();
        foreach ( $data as $k => $v ) {
            $values [] = $this->quoteObj ( $k ) . '=' . $this->quote ( $v );
        }
        $values = implode ( ',', $values );
        $sql .= ' SET ' . $values . $condWhere;
        $re = $this->exec ( $sql );
        return $re;
    }
    public function throwException($message = null, $code = null, $previous = null) {
        $errorInfo = $this->errorInfo ();
        throw new DbException ( $message . ' ' . $errorInfo [2], $code, $previous );
    }
    /**
     *
     * @return \PDOStatement
     * @see PDO::query()
     */
    public function query($sql) {
        $this->lastSql = $sql;
        $this->confirmConnection();
        $this->queryBeginTime = microtime ( true );
        $re = parent::query ( $sql );
        $this->queryEndTime = microtime ( true );
        $this->logQuery ( $sql );
        if (false === $re) {
            $this->throwException ( 'Query failure.SQL:' . $sql . '. ', 42004 );
        }
        $this->clearModelCache($re);
        return $re;
    }
    
    /**
     * (non-PHPdoc)
     *
     * @see PDO::exec()
     */
    public function exec($sql) {
        $this->lastSql = $sql;
        $this->confirmConnection();
        $this->queryBeginTime = microtime ( true );
        $re = parent::exec ( $sql );
        $this->queryEndTime = microtime ( true );
        $this->logQuery ( $sql );
        if (false === $re) {
            $this->throwException ( 'Query failure.SQL:' . $sql . '. ', 42004 );
        }
        $this->clearModelCache($re);
        return $re;
    }
    /**
     * test if is stilled connected with the server.
     */
    public function isConnected()
    {
        try {
            $re = parent::query('SELECT 1');
            if($re)$re = $re->fetchColumn();
            return (boolean) $re;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }
    public function confirmConnection()
    {
        if(!$this->isConnected())
        {
           return $this->connect($this->connectionCfg);
        }
        else
        {
            return true;
        }
    }
    public function buildWhere(array $cond, $logical = 'AND') {
        $where = array ();
        foreach ( $cond as $k => $v ) {
            if(is_int($k)){
                $where[] = $v;
                continue;
            }

            $kArr = explode ( '.', $k );
            if (count ( $kArr ) > 1) {
                $k = $this->quoteObj ( $kArr [0] ) . '.' . $this->quoteObj ( $kArr [1] );
            } else {
                $k = $this->quoteObj ( $k );
            }
            $where [] = $k . '=' . $this->quote ( $v );
        }
        return implode ( ' ' . $logical . ' ', $where );
    }
    public function logQuery($sql) {
        $cfg = \Lib\Util\Sys::getAppCfg('Db');
        if ($cfg::DEBUG) {
            $logString = 'Begin:' . date ( 'Y-m-d H:i:s', $this->queryBeginTime ) . "\n";
            $logString .= 'SQL: ' . $sql . "\n";
            switch ($cfg::DEBUG_LEVEL) {
                case 2 :
                    $tempE = new \Exception ();
                    $logString .= "Trace:\n" . $tempE->getTraceAsString () . "\n";
                    continue;
                case 1 :
                default :
                    continue;
            }
            $logString .= 'End:' . date ( 'Y-m-d H:i:s', $this->queryEndTime ) . '  Total:' . sprintf ( '%.3f', ($this->queryEndTime - $this->queryBeginTime) * 1000 ) . 'ms';
            \Lib\Log::instance ( 'db' )->log ( $logString );
        }
    }
    
    /**
     * clear Model level cache when data changed.
     * 
     * @param boolean|int|PDOStatement
     * @todo do not enable this feature in heavy write applcations. performance issue.
     * @return NULL
     */
    public function clearModelCache($result)
    { 
        if(!$result || (($result instanceof \PDOStatement) && $result->rowCount() < 1))
        {
            return null;
        }

        $action = strtolower(substr($this->lastSql, 0, 6));
        if(!in_array($action, array('update', 'insert', 'delete')))
        {
            return null;
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach($trace as $rec)
        {
            if(isset($rec['class']))
            {
                $modelClass = null;
                if(preg_match('/^App\\\[a-z0-9_]+\\\Model\\\[a-z0-9_\\\]+$/i', $rec['class']))
                {
                    $modelClass = $rec['class'];
                }
                else if($rec['class'] == 'Lib\BaseDbModel')
                {
                    $modelClass = \Lib\BaseDbModel::getCurrentCalledClass();
                }
                if($modelClass)
                {
                    $modelClass::instance()->clearModelCache();
                    $relatedModel = $modelClass::getRelatedModel();
                    if(is_array($relatedModel))
                    {
                        foreach($relatedModel as $rm)
                        {
                            $rm::instance()->clearModelCache();
                        }
                    }
                }
            }
        }
        return true;
    }
}

class DbStatement extends \PDOStatement {
}
/**
 * Exceptions about database.DbException is bundled with {@link \Lib\Db} in the
 * same script file.<br />
 * Exception code as below:<br/>
 * <b>Error code start with "42" are redis exceptions.</b>
 * <pre>
 * <code>
 * 42000 => connection failure
 * 42001 => no connections available
 * 42003 => specified connecton configuration not found
 * 42004 => Query failure.
 * </code>
 * </pre>
 */
class DbException extends \Exception {
}
