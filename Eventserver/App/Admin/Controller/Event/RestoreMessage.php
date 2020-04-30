<?php
/**
 * restore message form failure log to re-send queue
 *
 * @author chaos<suchaoabc@163.com>
 */
namespace App\Admin\Controller\Event;

use App\Admin\Controller\Auth\Base;
use \App\Admin\Model\Event\Log;
use App\Server\Model\BroadcastFailureLog as MBFL;

class RestoreMessage extends Base {

    public function execute() {
        $logIds = $this->requestParams->getPost('log_id');
        $destHosts = $this->requestParams->getPost('dest_host');
        if(empty($logIds))
        {
            $_SESSION['message']['alert alert-error'][] = "Log ID为空!!";
        } else if(empty($destHosts)){
            $_SESSION['message']['alert alert-error'][] = "请选择队列服务器!";
        }
        else
        {
            $errors = array();
            $hostArr = array();
            $destHosts = explode(',', $destHosts);
            foreach($destHosts as $destHost){
                $destHost= explode(':', $destHost);
                $hostArr[] = array('host' => $destHost[0], 'port'=>$destHost[1]);
            }
            $bt =  \Lib\Beanstalk::instance($hostArr);
            $logIds = explode(',', $logIds);
            foreach($logIds as $logId)
            {
                $bt = $bt->selectServer();
                $data = Log::instance()->getLogDetail($logId);
                if(!$data)
                {
                    $errors[] = $logId.' 获取失败: '.Log::instance()->errorsToString();
                    continue;
                }

                $bt->useTube(static::getReQueueName($data['class_key'], $data['subscription_id']));
                $message = new \stdClass();
                $message->Body = json_decode($data['message_body'], true);
                $message->MsgKey = $data['class_key'];
                $message->Time = (float)$data['message_time'];
                $message->Sender = $data['subscriber_key'];
                $jobId = explode('-', $data['job_id']);
                $message->OriginJobId = (int)$jobId[1];
                $message->LogId =(int) $data['log_id'];
                $message->RetryTimes = 0;

                $message = \Lib\Util\Broadcast::serialize((array)$message);
                if($bt->put($message))
                {
                    Log::instance()->resetRetryTimes($logId);
                    MBFL::instance()->setFinalStatus($data['log_id'], $data['final_status'], 1);
                }
                else
                {
                    $errors[] = $logId.' 恢复失败: '.Log::instance()->errorsToString();
                }
                usleep(70);
            }

            if(!empty($errors))
            {
                $_SESSION['message']['alert alert-error'] = $errors;
            }
            else
            {
                $_SESSION['message']['alert alert-success'][] = "恢复成功!";
            }
        }
        header('Location: '.$_SERVER['HTTP_REFERER']);
    }

    public static function getReQueueName($messageClassKey, $subscriptionId){
        return sprintf("%s/sub-queue/%s/FAIL", $messageClassKey, $subscriptionId);
    }

}

