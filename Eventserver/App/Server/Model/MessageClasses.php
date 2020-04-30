<?php
namespace App\Server\Model;
class MessageClasses extends \Lib\BaseDbModel{
    const TABLE = 'message_classes';
    const PRIMARY_KEY = 'class_id';
    public function validateInputMessageData($data)
    {
        $valid = true;
        if(empty($data['class_name']) || empty($data['class_key']))
        {
            $this->setError('710102', 'Class name or class key cannot be empty!');
            return false;
        }
        if(!preg_match('#^[0-9a-zA-Z_]+$#', $data['class_key']))
        {
            $this->setError('710103', 'Invalid message class key, only 0-9,a-Z and "_" are allowed !');
            return false;
        }
        $sql = 'SELECT '.self::PRIMARY_KEY.' FROM '.self::TABLE.'
        WHERE (class_name=? OR class_key=?)';
        $inputData = array($data['class_name'],$data['class_key']);
        if(!empty($data['class_id']))
        {
            $sql .= ' AND class_id <>?';
            $inputData[] = $data['class_id'];
        }
        $stm = $this->db()->write()->prepare($sql);
        $stm->execute($inputData);
        $re = $stm->fetchColumn();
        if($re !== false)
        {
            $this->setError('710101', 'Class name or class key already exists!');
            $valid = false;
        }
        return $valid;
    }

    public function getListByPage($cond='', $page=1, $limit=NUMBERS_PER_PAGE, $order='class_id desc')
    {
    	$res = array();
    	if(!empty($cond))
    	{
    	    if(is_string($cond))
    	    {
    	        $cond = ' where '.trim(trim($cond), 'and');
    	    }
    	    else if(is_array($cond))
    	    {
    	        $cond = ' where '.$this->db()->read()->buildWhere($cond);
    	    }
    	}
    	else {
    	    $cond = '';
    	}
    	$sql = "select class_id,class_name,class_key,comment,create_time from ".self::TABLE;
    	$sql .= $cond;
    	$sql .= " order by ".$order;
    	$sql .= " limit ".($page-1)*$limit.",".$limit;
    	$data = $this->db()->read()->query($sql)->fetchALL(\PDO::FETCH_ASSOC);
    	$sql = "select count(*) as size from ".self::TABLE;
    	$sql .= $cond;
    	$re = $this->db()->read()->query($sql)->fetch(\PDO::FETCH_ASSOC);
    	$size = (!empty($re['size'])) ? $re['size'] : 0;
    	$res = $this->getPageInfo($data, $size, $page, $limit);
    	return $res;
    }

    public function getList($cond='')
    {
    	$res = array();
    	$sql = "select class_id,class_name,class_key,comment,create_time from ".self::TABLE;
    	if(!empty($cond))
    	{
    		$sql .= " where ".trim(trim($cond), 'and');
    	}
    	$sql .= " order by class_id desc";
    	$res = $this->db()->read()->query($sql)->fetchALL(\PDO::FETCH_ASSOC);
    	return $res;
    }

    public function getInfo($cond)
    {
    	$res = array();
    	if(!empty($cond)) $cond = ' where '.$cond;
    	$sql = "select class_id,class_name,class_key,comment,create_time from ".self::TABLE;
    	$sql .= $cond;
    	$res = $this->db()->read()->query($sql)->fetch(\PDO::FETCH_ASSOC);
    	return $res;
    }

    public function add($className,$classKey,$comment='')
    {
        $data = array('class_name'=>$className,
                      'class_key'=>$classKey,
                      'comment'=>$comment,
                      'create_time'=>time());
        if(!$this->validateInputMessageData($data))
        {
            return false;
        }
        $re = $this->db()->write()->insert(self::TABLE, $data);       
        return $re;
    }
    public function update($classId,$className,$classKey,$comment='')
    {
        $data = array('class_id'=>$classId,
                      'class_name'=>$className,
                      'class_key'=>$classKey,
                      'comment'=>$comment);
        if(!$this->validateInputMessageData($data))
        {
            return false;
        }
        return $id = $this->db()->write()->update(self::TABLE, $data,array('class_id' => $classId));
    }
    public function delete($classId)
    {
        return $this->deleteByPrimaryKey($classId);
    }
}
