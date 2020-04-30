<?php
$pid = pcntl_fork();
if($pid > 0)
{
    echo 'Starting jmevent...'."\r\n";
    die(0);
}
else if($pid < 0)
{
    echo 'Failed to daemonize..'."\r\n";
    die(1);
}

use App\Server\Cfg;
use App\Server\Service;
/**
 * Monitoring the broadcast message tube and invoke message send service.
 * @todo when one child process exit,master process will terminate unexpectedly
 */
if(php_sapi_name() != 'cli')
{
    die('This script runs on CLI only!');
}

define('EVENT_CENTER_ROOT',realpath(__DIR__.DIRECTORY_SEPARATOR.'../../').DIRECTORY_SEPARATOR);

//register sys contants and autoloader
require EVENT_CENTER_ROOT.'constants.php';
require SYS_ROOT.'Lib/Autoloader.php';
\Lib\Autoloader::setNamespacePrefixPath(SYS_ROOT);
spl_autoload_register(array('\Lib\Autoloader','loadByNamespace'));
use \Lib\Log as Log;
$logger = Log::instance('serverNotices');

//init log dir
$logDir = Cfg\Log::FILE_LOG_ROOT;
if(!is_dir($logDir))
{
    mkdir($logDir, 0750, true);
}
$workerUserGroup = Cfg\Service::DAEMON_GID;
$workerUser = Cfg\Service::DAEMON_UID;
`chown -R $workerUser:$workerUserGroup $logDir`;

declare(ticks = 30);

pcntl_signal(SIGCHLD, 'service_signal_handler');
pcntl_signal(SIGTERM, 'service_signal_handler');
pcntl_signal(SIGHUP, 'service_signal_handler');
pcntl_signal(SIGQUIT, 'service_signal_handler');
pcntl_signal(SIGUSR1, 'service_signal_handler');
pcntl_signal(SIGUSR2, 'service_signal_handler');

define('EVENT_SERVICE_MASTER_PID', posix_getpid());

$pidFile = '/var/run/jmevent/jmevent.pid';
if((!is_dir(dirname($pidFile)) && !mkdir(dirname($pidFile))) || !is_writable(dirname($pidFile)))
{
    echo 'Failed to save pid file! exit.'."\r\n";
}
file_put_contents($pidFile, EVENT_SERVICE_MASTER_PID);

$childPids = array('q_1'=>array('active'=>array(),'dead'=>array()),
                   'q_2'=>array('active'=>array(),'dead'=>array()),
                   'rec_q'=>array(),
                   'statistic_q' => array()
);

$childNum = 0;
$isMaster = true;
$workerType=null;
$service_exiting = false;

//Fork statistic job process

$pid = pcntl_fork();
if($pid == 0)
{
    $workerType = 'statistic';
    $isMaster = false;
}
else if($pid > 0)
{
    $childPids['statistic_q'][] = $pid;
}
else
{
    $logger->log('failed to fork statistic process for job restoring!');
    exit(1);
}

//Fork recovering job process
if($isMaster)
{
    $pid = pcntl_fork();
    if($pid == 0)
    {
        $workerType = 'recover';
        $isMaster = false;
    }
    else if($pid > 0)
    {
        $childPids['rec_q'][] = $pid;
    }
    else
    {
        $logger->log('failed to fork recovering process for job restoring!');
        exit(1);
    }
}

//fork message dispatchers
while($isMaster && $childNum < Cfg\Service::MAX_MESSAGE_DELIVERER_WORKER_NUM)
{
    $pid = pcntl_fork();
    if($pid > 0)
    {
        $childNum++;
        $childPids['q_1']['active'][]=$pid;
    }
    else if($pid===0)
    {
        $workerType = 'dispatcher';
        $isMaster = false;
        break;
    }
    else
    {
        exit(1);
    }
}

if($isMaster)
{
    //fork message re-dispatchers to send message from failure queue.
    $childNum = 0;
    while($isMaster && $childNum < Cfg\Service::MAX_MESSAGE_RE_DELIVERER_WORKER_NUM)
    {
        $pid = pcntl_fork();
        if($pid > 0)
        {
            $childNum++;
            $childPids['q_2']['active'][]=$pid;
        }
        else if($pid===0)
        {
            $workerType = 're-dispatcher';
            $isMaster = false;
            break;
        }
        else
        {
            exit(1);
        }
    }
}

if($isMaster)
{//monitoring..
 //@todo better assemble
    while(true)
    {
       foreach($childPids['rec_q'] as $k=>$pid)
       {
           $return = posix_kill($pid, 0);
           if(!$return && !$service_exiting)
           {
               $logger->log('Rec-Worker('.$pid.') is gone. Re-spawning...');
               $cpid = pcntl_fork();
               if($cpid > 0)
               {
                    unset($childPids['rec_q'][$k]);
                    $childPids['rec_q'][] = $cpid;
                    $logger->log('Re-spawned recover worker('.$cpid.')');
               }
               else if($cpid === 0)
               {
                    $isMaster = false;
                    spawnWorker('recover');
                    exit();
               }
               else
               {
                    $logger->log('Failed to spawn recover worker. Retry soon...');
               }
           }
       }

       foreach($childPids['statistic_q'] as $k=>$pid)
       {
           $return = posix_kill($pid, 0);
           if(!$return && !$service_exiting)
           {
               $logger->log('Statistic-Worker('.$pid.') is gone. Re-spawning...');
               $cpid = pcntl_fork();
               if($cpid > 0)
               {
                   unset($childPids['statistic_q'][$k]);
                   $childPids['statistic_q'][] = $cpid;
                   $logger->log('Re-spawned statistic worker('.$cpid.')');
               }
               else if($cpid === 0)
               {
                   $isMaster = false;
                   spawnWorker('statistic');
                   exit();
               }
               else
               {
                   $logger->log('Failed to spawn statistic worker. Retry soon...');
               }
           }
       }

       foreach($childPids['q_1']['active'] as $k=>$pid)
       {
           $return = posix_kill($pid, 0);
           if(!$return && !$service_exiting)
           {
                $logger->log('Worker('.$pid.') is gone. Re-spawning...');
               $cpid = pcntl_fork();
               if($cpid > 0)
               {
                    unset($childPids['q_1']['active'][$k]);
                    $childPids['q_1']['active'][] = $cpid;
                    $logger->log('Re-spawned dispatcher worker('.$cpid.')');
               }
               else if($cpid === 0)
               {
                    $isMaster = false;
                    spawnWorker('dispatcher');
                    exit();
               }
               else
               {
                    $logger->log('Failed to spawn dispatcher worker. Retry soon...');
               }
           }
       }

       foreach($childPids['q_2']['active'] as $k=>$pid)
       {
           $return = posix_kill($pid, 0);
           if(!$return && !$service_exiting)
           {
               $logger->log('Re-dispatcher worker('.$pid.') is gone. Re-spawning...');
               $cpid = pcntl_fork();
               if($cpid > 0)
               {
                    unset($childPids['q_2']['active'][$k]);
                    $childPids['q_2']['active'][] = $cpid;
                    $logger->log('Re-spawned re-dispatcher worker('.$cpid.')');
               }
               else if($cpid === 0)
               {
                    $isMaster = false;
                    spawnWorker('re-dispatcher');
                    exit();
               }
               else
               {
                    $logger->log('Failed to spawn re-dispatcher worker. Retry soon...');
               }
           }
       }
       usleep(200000);
    }
}
else{
    spawnWorker($workerType);
}
exit();
/*******functions*******/
function setId()
{
    posix_setuid(Cfg\Service::DAEMON_UID);
    posix_setgid(Cfg\Service::DAEMON_GID);
}

function executeService($service, $excInterval = null)
{
    global $logger, $workerType;
    $retryIntervalOnException = 10;
    $maxRetryTimes = 0;
    $lastRetrySum = 0;
    while(true)
    {
        pcntl_signal_dispatch();
        $startTime = microtime(true);
        try
        {
            $service->execute();
            $lastRetrySum = 0;
            $errorRemains = false;
        }
        catch(Exception $e)
        {//malfunction checks and restoring
            /*
            $errorRemains = true;
            if(($e instanceof RedisException || $e instanceof \Lib\RedisException) && (false !== stripos($e->getMessage(), 'connection')))
            {
                //check and re-create redis instances. but not cover init run.
                \Lib\Log::instance('redisErrors')->log($e->__toString());
                $logger->log(get_class($e).' Retry '. ++$lastRetrySum);
                foreach(\Lib\Redis::getInstances() as $name => $redisInstance)
                {
                    $et = null;
                    $pingTest = false;
                    try
                    {
                        $pingTest = $redisInstance->ping();
                    }
                    catch(Exception $et)
                    {

                    }
                    if(!$pingTest || $et)
                    {
                        $logger->log('Redis connection('.$redisInstance->getEndpoint().') is broken. Re-establishing...');
                        try
                        {
                            if($redisInstance->reEstablishConnection())
                            {
                                $logger->log('Redis connection('.$redisInstance->getEndpoint().') restored.');
                                $errorRemains = false;
                            }
                        }
                        catch(Exception $etr)
                        {
                            \Lib\Log::instance('redisErrors')->log($etr->__toString());
                            $logger->log('Failed to Re-establish connection for redis ('.$name.')'."\n");
                            $errorRemains = true;
                        }
                    }
               }
            }
            else if($e instanceof Pheanstalk_Exception_ConnectionException)
            {//check and re-create beanstalk instances. but not cover init run.
                \Lib\Log::instance('beanstalkErrors')->log($e->__toString());
                $logger->log(get_class($e).'Retry '. ++$lastRetrySum);
                foreach(\Lib\Beanstalk::getInstances() as $name => $beanstalkInstance)
                {
                    $eb = null;
                    try
                    {
                        $stats = $beanstalkInstance->stats();
                    }
                    catch(\Exception $eb)
                    {

                    }

                    if(!$stats || $eb)
                    {
                        $logger->log('Trying to re-establish beanstalk connection of "'.$name.'"');
                        try
                        {
                            \Lib\Beanstalk::destroyInstance($name);
                            if($stats = \Lib\Beanstalk::instance($name)->stats())
                            {
                                $logger->log('Beanstalk connection('.$name.') restored.');
                                $errorRemains = false;
                            }
                        }
                        catch(\Exception $eb)
                        {
                            $allChecked = false;
                            \Lib\Log::instance('beanstalkErrors')->log($eb->__toString());
                            $logger->log('Failed to Re-establish connection for beanstalk ('.$name.')'."\n");
                            $errorRemains = true;
                        }
                    }
                }
            }
            else
            {//do not retry for other exception, because don't know how handle them, so just restart child process
                $logger->log('Caugth exception: '.$e->__toString())
                       ->log('I\'m exiting now. Master will bring me up again soon...');
                sleep($retryIntervalOnException);
                exit(0);
            }

            if($errorRemains)
            {
                $logger->log('Error remains, retry in '.$retryIntervalOnException.' seconds!');
                sleep($retryIntervalOnException);
            }*/
            $logger->log('Caugth exception: '.$e->__toString())
                   ->log('I\'m exiting now. Master will bring me up again soon...('.$workerType.')')
                   ->log('Retry in '.$retryIntervalOnException.' seconds!');
            sleep($retryIntervalOnException);
            exit(1);
        }

        $endTime = microtime(true);
        $excTime = ($endTime - $startTime)*1000;
        if(empty($excInterval))
        {
            $excInterval = Cfg\Service::BROADCAST_INTERVAL;
        }

        if($excTime < $excInterval)
        {//if $excTime is too short and less than the defined interval time, then sleep the remaining milliseconds.
            usleep(($excInterval - $excTime)*1000);
        }
        if(EVENT_SERVICE_MASTER_PID != posix_getppid())
        {
            $logger->log("Master has crashed. Now {$workerType} exit(".posix_getpid().")!");
            exit();
        }
    }
}

function spawnWorker($type)
{
    global $workerType;
    $workerType = $type;
    setId();
    switch($workerType)
    {
        case 'dispatcher' :
            //service worker(send messages to subscribers)
            executeService(new Service\Broadcast\Dispatch());
            return true;
        case 're-dispatcher' :
            //another service worker(send messages of the failure queue to subscribers)
            executeService(new Service\Broadcast\HandleDispatchFailures());
            return true;
        case 'recover':
            executeService(new Service\Broadcast\RecoverFromRedis());
            return true;
        case 'statistic':
            executeService(new Service\Broadcast\StatisticMonitor(), Cfg\Service::STATIC_UPDATE_INTERVAL);
            return true;
        default:
            return false;
    }
}

function service_signal_handler($signo)
{
    global $isMaster, $logger, $workerType;
    switch($signo)
    {
        case SIGTERM:
        case SIGQUIT:
//         case SIGKILL: //uncatchable sig
            if($isMaster)
            {
                $logger->log("Received quit signal, stopping service...");
                global $childPids;
                $GLOBALS['service_exiting'] = true;
                foreach( $childPids['q_1']['active'] as $cpid)
                {
                    stop_worker($cpid);
                }

                foreach( $childPids['q_2']['active'] as $cpid)
                {
                    stop_worker($cpid);
                }

                foreach( $childPids['rec_q'] as $cpid)
                {
                    stop_worker($cpid);
                }

                foreach( $childPids['statistic_q'] as $cpid)
                {
                    stop_worker($cpid);
                }

                while(pcntl_wait($status)>0)
                {
                }
                $logger->log('Service stopped !');
                exit(0);
            }
            else
            {
                $logger->log('Received quit signal, '. $workerType. ' worker('.posix_getpid().') exiting...');
                exit(0);
            }
        continue;
        case SIGCHLD:
            if($isMaster && !$GLOBALS['service_exiting'])
            {//let the child gone completely
                while(($cpid = pcntl_wait($status, WNOHANG|WUNTRACED)) > 0)
                {
                    pcntl_wifexited($status);
                    if($status)
                    {
                        $logger->log('Worker('.$cpid.') exited normaly.');
                    }
                    else
                    {
                        $logger->log('Worker('.$cpid.') exited unexpectedly!');
                    }
                }
            }
        continue;
        case SIGUSR2:
            if($isMaster)
            {
                global $childPids;
                echo "Datetime: ".date('Y-m-d H:i:s e')."\n";
                echo "UPTIME: ".\Lib\Util\Sys::uptime()."\nMemory Usage: ".(memory_get_usage(true)/1024)."k\n";
                echo "Dispatch worker processlist:\n";
                foreach($childPids['q_1']['active'] as $cpid)
                {
                    echo "$cpid\n";
                }
                echo "Re-dispatch worker processlist:\n";
                foreach($childPids['q_2']['active'] as $cpid)
                {
                    echo "$cpid\n";
                }
                echo "\r";
            }
            continue;
        default:
        continue;
    }
}

function stop_worker($cpid)
{
    global $logger;
    $logger->log("Stopping worker({$cpid})!");
    $result = posix_kill($cpid, SIGTERM);
}
