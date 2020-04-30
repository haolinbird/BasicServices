<?php
namespace Lib;
/**
 * BeansTalkMonitor
 *
 * @author jingd <jingd3@jumei.com>
 */
class BeanstalkMonitor extends Beanstalk{
    const NAME = 'name';
    const CURRENT_JOBS_URGENT = 'current-jobs-urgent';
    const CURRENT_JOBS_READY = 'current-jobs-ready';
    const CURRENT_JOBS_RESERVED = 'current-jobs-reserved';
    const CURRENT_JOBS_DELAYED = 'current-jobs-delayed';
    const CURRENT_JOBS_BURIED = 'current-jobs-buried';
    const TOTAL_JOBS = 'total-jobs';
    const CURRENT_USING = 'current-using';
    const CURRENT_WATCHING = 'current-watching';
    const CURRENT_WAITING = 'current-waiting';
    const CMD_DELETE = 'cmd-delete';
    const CMD_PAUSE_TUBE = 'cmd-pause-tube';
    const PAUSE = 'pause';
    const PAUSE_LEFT_TIME = 'pause-time-left';
    
    public static $statsMapping = array(
        self::NAME => '队列名称',
        self::CURRENT_JOBS_URGENT => '紧急消息数',
        self::CURRENT_JOBS_READY => '等待处理数',
        self::CURRENT_JOBS_RESERVED => '准备处理数',
        self::CURRENT_JOBS_DELAYED => '延时处理数',
        self::CURRENT_JOBS_BURIED => '正在处理数',
        self::CMD_DELETE => '已处理消息总数',
        self::TOTAL_JOBS => '本次历史总数',
        self::CURRENT_USING => '消息发布连接数',
        self::CURRENT_WATCHING => '消息监听连接数',
        self::CURRENT_WAITING => '等待消息连接数',
        self::CMD_PAUSE_TUBE => '暂停命令数',
        self::PAUSE => '暂停总时间(sec)',
        self::PAUSE_LEFT_TIME => '暂停剩余时间(left)',
    );
    public static $statsToDisplay = array(
        self::NAME, self::CURRENT_JOBS_READY, self::CURRENT_JOBS_RESERVED, self::CURRENT_JOBS_BURIED,
        self::CURRENT_JOBS_DELAYED, self::TOTAL_JOBS, self::CURRENT_USING, 
        self::CMD_DELETE, self::CURRENT_WATCHING

    );
    public static $peekStats = array(
        'Ready', 'reserved', 'Delayed', 'Buried',
    );
    
    public static $jobAttris = array(
        'id', 'tube', 'state', 'pri', 'age', 'delay', 'ttr', 'time-left',
        'file', 'reserves', 'timeouts', 'releases', 'buries', 'kicks',
    );
    public function getTubesStats($host=null) {
        $stats = array();
        if(is_null($host)){
            return $stats;
        }
        if(!is_array($host)){
            $host = array($host);
        }
        $conns = $this->getInstancePools();
        foreach($conns as $k=>$conn)
        {
            if(!in_array($conn->getConnection()->getHost().':'.$conn->getConnection()->getPort(), $host)){
                continue;
            }

            foreach ($conn->listTubes() as $tube)  {
                $stats[$k][$tube] = $this->getOneTubeStats($conn, $tube);
            }
        }
        return $stats;
    }
    
    public function getOneTubeStats($conn, $tube) {
        $res = array();
        $conn->getConnection();
        foreach ($conn->statsTube($tube) as $key => $val) $res[$key] = $val;
        return $res;
    }
    
    public function peekAll($conn, $tube) {
        $res = array();
        foreach (self::$peekStats as $stat) {
            $method = "peek{$stat}";
            if (method_exists($conn, $method) && is_callable(array($conn, $method))) {
                try {
                    $job = $conn->useTube($tube)->{$method}();
                    $data = $job->getData();                    
                    $unpackData = @\Lib\Util\Broadcast::unserialize($data);
                    $res[$stat] = array(
                        'id' => $job->getId(),
                        'data' => $unpackData,                        
                    );                    
                    foreach ($conn->statsJob($job) as $key => $value) {
                        $res[$stat][$key] = $value;
                    }
                } catch (\Exception $e) {
                    $res[$stat] = array();
                }                                
            }
        }
        return $res;
    }
}

?>
