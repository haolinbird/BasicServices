<?php
namespace Lib;
class BaseDbModel extends BaseModel{
    const TABLE = null;
    const PRIMARY_KEY = null;
    const DATABASE = 'default';
    /**
     * the derived class that currently called the method of its parent class.
     * set this propery only in DELETE,UPDATE,INSERT.
     * refers to {@link Lib\Db}
     *
     * @var string
     */
    private static $currentCalledClass;

    protected static $instances = array();

    /**
     * @var \Lib\Db;
     */
    protected $db;

    /**
     * @return static
     */
    public static function instance()
    {
        $class = get_called_class();
        if(!isset(self::$instances[$class]))self::$instances[$class] = new $class();
        return self::$instances[$class];
    }

    /**
     *
     * @param string $className
     */
    protected static function setCurrentCalledClass($className)
    {
        self::$currentCalledClass = $className;
    }

    public static function getCurrentCalledClass()
    {
        return self::$currentCalledClass;
    }

    /**
     * Db connection configuration name.
     * @return \Lib\Db
     */
    public function db()
    {
        if(!$this->db)
        {
            $this->db = new \Lib\Db();
        }
        return $this->db;
    }

    /**
     * check if exists a record with the value of the key/field. The default key is the primary key that defined in each derived class.
     * @param mixed $value
     * @param string $key
     */
    public function exists($value,$key=null)
    {
        if(!is_string($key))
        {
            $key = $this->db()->quoteObj($this::PRIMARY_KEY);
        }
        $sql = 'SELECT '.$key.' FROM '.$this->db()->write($this::DATABASE)->quoteObj($this::TABLE).' WHERE '.$key.'='.$this->db()->write($this::DATABASE)->quote($value);
        $stm = $this->db()->write($this::DATABASE)->query($sql);
        $re = $stm->fetchColumn();
        return false !== $re;
    }

    public function fetchColum($column,$cond)
    {
        $db = $this->db()->write($this::DATABASE);
        if(is_array($cond))$cond = $db->buildWhere($cond);
        $sql = 'SELECT '.$db->quoteObj($column).' FROM '.$this::TABLE.' WHERE '.$cond;
        $stm = $db->query($sql);
        return $stm->fetchColumn();
    }

    public function deleteByPrimaryKey($value)
    {
        self::setCurrentCalledClass(get_called_class());
        $db = $this->db()->write($this::DATABASE);
        $sql = 'DELETE FROM '.$this::TABLE.' WHERE '.$this::PRIMARY_KEY.'=?';
        $stm = $db->prepare($sql);
        $re = $stm->execute(array($value));
        return $re;
    }
    public function delete($cond)
    {
        self::setCurrentCalledClass(get_called_class());
        $db = $this->db()->write($this::DATABASE);
        if(is_array($cond))$cond = $db->buildWhere($cond);
        $sql = 'DELETE FROM '.$this::TABLE.' WHERE '.$cond;
        return $db->exec($sql);
    }

    public function getByPrimaryKey($valueOfPrimayKey,$column='*')
    {
        $db = $this->db()->read($this::DATABASE);
        $sql = 'SELECT'.$column.' FROM '.$this::TABLE.' WHERE '.$this::PRIMARY_KEY.'=?';
        $stm = $db->prepare($sql);
        $stm->execute(array($valueOfPrimayKey));
        return $stm->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * get a row from the TABLE
     * @param array $cond
     */
    public function getOne(array $cond=array(),$logical='AND')
    {
        $db = $this->db()->read($this::DATABASE);
        $sql = 'SELECT * FROM '.$this::TABLE;
        if(!empty($cond)) $sql .= ' WHERE '.$db->buildWhere($cond,$logical);
        $stm = $db->query($sql);
        return $stm->fetch(\PDO::FETCH_ASSOC);
    }

    public function updateByPrimaryKey($valueOfPrimaryKey,$data)
    {
        self::setCurrentCalledClass(get_called_class());
        return $this->db()->write($this::DATABASE)->update($this::TABLE,$data,array($this::PRIMARY_KEY=>$valueOfPrimaryKey));
    }

    public function getAll(array $cond=array(),$logical='AND')
    {
        $db = $this->db()->read($this::DATABASE);
        $sql = 'SELECT * FROM '.$this::TABLE;
        if(!empty($cond)) $sql .= ' WHERE '.$db->buildWhere($cond,$logical);
        $stm = $db->query($sql);
        return $stm->fetchAll(\PDO::FETCH_ASSOC);
    }
    public function getPageInfo($data=array(), $size=0, $page_now=1, $limit=NUMBERS_PER_PAGE)
    {
        $res = array(
                'rows' => $data,
                'rowCount' => $size,
                'rowsPerPage' => $limit,
                'pageIndex' => $page_now-1,
                'pageNumber' => $page_now,
                'pageCount' => 1
        );
        if(!empty($size))
        {
            $res['pageCount'] = ceil($size/$limit);
        }
        return $res;
    }
}
