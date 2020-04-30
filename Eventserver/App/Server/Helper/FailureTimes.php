<?php
namespace App\Server\Helper;

class FailureTimes{
    const MAX_LOG_FILE_LOCK_TIME = 1;

    protected  static $instance;

    /**
     * @return static
     */
    public static function instance()
    {
        if(!static::$instance)
        {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function getLogDirFile($messageClass, $receptionAddr)
    {
        $dirHash = md5($messageClass.$receptionAddr);
        $dirHash = str_split($dirHash, 8);
        $fileName = array_pop($dirHash);
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $dirHash).DIRECTORY_SEPARATOR;
        if(!is_dir($dir) && !mkdir($dir, 0777, true))
        {
            return false;
        }
        $file = $dir.$fileName;
        if(!is_file($file))
        {
            touch($file);
        }
        return $file;
    }

    /**
     * 增加某类消息在某个接受者上推送失败的次数
     *
     * @param $messageClass
     * @param $receptionAddr
     *
     * @return boolean
     */
    public function logIncreaseMessageFailureTimes($messageClass, $receptionAddr)
    {
        $file = $this->getLogDirFile($messageClass, $receptionAddr);
        if(false === $file)
        {
            return false;
        }
        $fd = fopen($file, 'a+');
        if(!$this->getLogLock($fd))
        {
            return false;
        }

        $contentRaw = $this->readLogContent($fd);
        $content = json_decode($contentRaw, true);
        if(!isset($content['times']))
        {
            $content = array('times' => 1, 'first_failure_time' => time(),
                             'last_failure_time' => time(),
                             'times_on_last_alert' => 0,
                             'last_alert_time' => 0,
                             'message_class'=>$messageClass,
                             'reception_addr'=>$receptionAddr);
        }
        else
        {
            $content['times']++;
            $content['last_failure_time'] = time();
        }
        $content = json_encode($content, 256);
        ftruncate($fd, 0);
        $re = fwrite($fd, $content, strlen($content));
        flock($fd, LOCK_UN);
        fclose($fd);
        return $re;
    }

    protected function saveLogRecentMessageFailureInfo($content)
    {
        $file = $this->getLogDirFile($content['message_class'], $content['reception_addr']);
        if(false === $file)
        {
            return false;
        }
        $fd = fopen($file, 'a+');
        if(!$this->getLogLock($fd))
        {
            return false;
        }

        $content = json_encode($content, 256);
        ftruncate($fd, 0);
        $re = fwrite($fd, $content, strlen($content));
        flock($fd, LOCK_UN);
        fclose($fd);
        return $re;
    }

    public function getLogRecentMessageFailureInfo($messageClass, $receptionAddr)
    {
        $file = $this->getLogDirFile($messageClass, $receptionAddr);
        if(file_exists($file))
        {
            $fd = fopen($file, 'r');
            if($this->getLogLock($fd))
            {
                $content = json_decode($this->readLogContent($fd), true);
                flock($fd, LOCK_UN);
                fclose($fd);
                return $content;
            }
            return false;
        }
        return false;
    }

    public function clearMessageFailureTimes($messageClass, $receptionAddr)
    {
        $file = $this->getLogDirFile($messageClass, $receptionAddr);
        if(file_exists($file))
        {
            $fd = fopen($file, 'w');
            if($this->getLogLock($fd))
            {
                return unlink($file);
            }
            return false;
        }
        return true;
    }

    protected function getLogLock($fd)
    {
        $lock = false;
        $st = microtime(true);
        while(!($lock = flock($fd, LOCK_EX|LOCK_NB)) && microtime(true) - $st < static::MAX_LOG_FILE_LOCK_TIME)
        {
            usleep(10);
        }
        return $lock;
    }

    protected function readLogContent($fd)
    {
        $content = '';
        while(!feof($fd))
        {
            $content .= fread($fd, 4096);
        }
        return $content;
    }

    /**
     * @param string $messageClass
     * @param string $receptionAddr
     * @param string $extraContent
     * @param bool $extraContentOnly 是否只发送$extraContent
     * @param bool $ignoreThresholdCheck 忽略条件伐值检查
     *
     * @return bool
     */
    public function alertBySms($messageClass, $receptionAddr, $extraContent = '', $extraContentOnly=false, $ignoreThresholdCheck=false)
    {
        try{
            $alerterConfig = \Lib\Util\Sys::returnAppCfg('Alerter');
        }
        catch(\Exception $ex)
        {
            trigger_error($ex);
        }
        if(!isset($alerterConfig['SmsReceiver']))
        {
            return true;
        }

        $reciverConfig = $alerterConfig['SmsReceiver'];

        $failureInfo = $this->getLogRecentMessageFailureInfo($messageClass, $receptionAddr);
        if(!$failureInfo)
        {
            return false;
        }

        $shouldAlert = !$ignoreThresholdCheck && $this->shouldAlert($failureInfo);
        if($failureInfo['times'] > PHP_INT_MAX - 1000)
        {
            $this->clearMessageFailureTimes($messageClass, $receptionAddr);
        }

        if(!$shouldAlert)
        {
            return false;
        }

        if(!$ignoreThresholdCheck)
        {
            $failureInfo['times_on_last_alert'] = $failureInfo['times'];
            $failureInfo['last_alert_time'] = time();
        }

        $this->saveLogRecentMessageFailureInfo($failureInfo);

        if(!$extraContentOnly)
        {
            $content = "消息处理器(Worker)".$alerterConfig['options']['ALERT_FAILURE_TIME_THRESHOLD']."秒内连续处理失败!\n消息类型：“{$messageClass}”\nWorker地址: {$receptionAddr}\n已失败次数:{$failureInfo['times']}\n最近失败时间:".date('Y-m-d H:i:s', $failureInfo['last_failure_time']);
        }
        else
        {
            $content = '';
        }

        $extraContent = strip_tags($extraContent);
        if(!$extraContent !== '')
        {
            $extraContent = mb_substr($extraContent, 0, 500);
            if(!empty($content))
            {
                $content .= "\n".$extraContent;
            }
            else
            {
                $content = $extraContent;
            }

        }

        $content = str_replace(array('}', '{'), array('[[',']]'), $content);
        $content .= "\r\n";

        $phones = array();
        foreach($reciverConfig as $v)
        {
            $msgClasses = explode(',', $v['msg_key']);
            if(!in_array($messageClass, $msgClasses))
            {
                continue;
            }
            $phones = array_merge($phones, explode(',', $v['mobile_no']));
        }

        $phones = array_unique($phones);

        if(empty($phones))
        {
            return false;
        }
        $param = array(                            // 发送短信用到的参数
            'channel' => 'monternet',
            'key'     => 'notice_rt902pnkl10udnq',
            'task'    => 'int_notice',
            'content' => $content
        );

        $mch = curl_multi_init();
        foreach($phones as $k => $phone)
        {
            $c = 'ch'.$k;
            $$c = curl_init();
            curl_setopt($$c, CURLOPT_URL, $alerterConfig['SmsGateway']);
            curl_setopt($$c, CURLOPT_POST, 1);
            curl_setopt($$c, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($$c, CURLOPT_TIMEOUT, 3);

            $param['num'] = $phone;
            curl_setopt($$c, CURLOPT_POSTFIELDS, http_build_query($param));
            curl_multi_add_handle($mch, $$c);
        }

        do
        {
            curl_multi_exec($mch, $running);
        }
        while($running > 0);

        foreach($phones as $k => $phone)
        {
            $c = 'ch'.$k;
            curl_multi_remove_handle($mch, $$c);
        }
        curl_multi_close($mch);
        $this->clearMessageFailureTimes($messageClass, $receptionAddr);
    }


    public function shouldAlert($lastFailureTimeInfo)
    {
        try{
            $alerterConfig = \Lib\Util\Sys::returnAppCfg('Alerter');
        }
        catch(\Exception $ex)
        {
            trigger_error($ex);
            return false;
        }

        if(!isset($alerterConfig['SmsGateway']) || !isset($alerterConfig['options']['ALERT_FAILURE_TIME_THRESHOLD']) || !isset($alerterConfig['options']['ALERT_FAILURE_COUNT_THRESHOLD']))
        {
            return false;
        }

        if(!$lastFailureTimeInfo)
        {
            return false;
        }

        if(!isset($lastFailureTimeInfo['times_on_last_alert']))
        {
            $lastFailureTimeInfo['times_on_last_alert'] = $lastFailureTimeInfo['times'];
        }

        if(!isset($lastFailureTimeInfo['last_alert_time']))
        {
            $lastFailureTimeInfo['last_alert_time'] = $lastFailureTimeInfo['last_failure_time'];
        }

        if($lastFailureTimeInfo['last_failure_time'] - $lastFailureTimeInfo['last_alert_time'] >= $alerterConfig['options']['ALERT_FAILURE_TIME_THRESHOLD'] && $lastFailureTimeInfo['times'] - $lastFailureTimeInfo['times_on_last_alert'] >= $alerterConfig['options']['ALERT_FAILURE_COUNT_THRESHOLD'])
        {
            return true;
        }

        return false;
    }

}