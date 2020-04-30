<?php
namespace App\Admin\Controller\Event;
use App\Admin\Controller\Auth\Base;
use App\Admin\Model;

class SubscriptionBase extends Base{
    /**
     * @return array array('data' => array(), 'subParams' => array(), 'errors' => array())
     */
    public function validateInpputData(){
        \MedApi\Client::config((array)\Lib\Util\Sys::getAppCfg("MedApi"));
        \MedApi\Client::call('GetDefaultSubscriptionSettings');
        $defaultSettings = \MedApi\Client::getLastCallResultFromCenterNode();
        $res = array('data' => array(), 'subParams' => array(), 'errors' => array());
        $data = array(
            'subscriber_id'     => $this->requestParams->getPostInt('subscriber_id'),
            'timeout'           => (int)$this->requestParams->getPostInt('timeout')/1000,
            'reception_channel' => $this->requestParams->getPost('reception_channel'),
            'status'            => $this->requestParams->getPostInt('status'),
        );
        if(!$this->requestParams->getPost('subscription_id')){
            $data['subscribe_time'] = time();
        }
        if(empty($data['subscriber_id'])){
            $res['errors'][] = '请选择订阅者';
        } else if(!Model\Event\Subscriber::instance()->exists($data['subscriber_id'], 'subscriber_id')){
            $res['errors'][] = '订阅者不存在';
        } else {
            $data['reception_channel'] = preg_split('#\n|\r\n|\r#', trim($data['reception_channel']));
            foreach($data['reception_channel'] as $k => $url){
                $data['reception_channel'][$k] = trim($url);
                if(!preg_match('/^(\[.+?\])*https?:\/\//i', $data['reception_channel'][$k]))
                    $res['errors'][] = '消息处理地址('.$data['reception_channel'][$k].')格式不正确!';
            }
            $data['reception_channel'] = implode("\n", $data['reception_channel']);
        }
        $subParams['Concurrency']   = $this->requestParams->getPostInt('concurrency');
        $subParams['ConcurrencyOfRetry'] = $this->requestParams->getPostInt('concurrency_as_retry');
        $subParams['IntervalOfSending'] = $this->requestParams->getPostInt('interval_of_pushes');
        $subParams['ProcessTimeout'] = $this->requestParams->getPostInt('timeout');
        $subParams['ReceptionUri'] = $data['reception_channel'];
        // $subParams['AlerterPhoneNumbers']   = $this->requestParams->getPost('alerter_tel');
        // $subParams['AlerterEmails'] = $this->requestParams->getPost('alerter_email');
        $subParams['AlerterReceiver'] = $this->requestParams->getPost('alerter_receiver');
        $subParams['AlerterEnabled'] = (bool)$this->requestParams->getPost('alerter_enabled');
        // 是否接收压测环境消息.
        $subParams['ReceiveBenchMsgs'] = (bool)$this->requestParams->getPost('receive_bench_msgs');

        // 报警阈值.
        $subParams['IntervalOfErrorMonitorAlert'] = (int)$this->requestParams->getPost('IntervalOfErrorMonitorAlert');
        $subParams['SubscriptionTotalFailureAlertThreshold'] = (int)$this->requestParams->getPost('SubscriptionTotalFailureAlertThreshold');
        $subParams['MessageFailureAlertThreshold'] = (int)$this->requestParams->getPost('MessageFailureAlertThreshold');
        $subParams['MessageBlockedAlertThreshold'] = (int)$this->requestParams->getPost('MessageBlockedAlertThreshold');

        $subParams['AlarmInterval'] = (int)$this->requestParams->getPost('AlarmInterval');

        if ($subParams['IntervalOfErrorMonitorAlert'] <= 0) {
            $subParams['IntervalOfErrorMonitorAlert'] = 180;
        }

        if ($subParams['SubscriptionTotalFailureAlertThreshold'] <= 0) {
            $subParams['SubscriptionTotalFailureAlertThreshold'] = 120;
        }

        if ($subParams['MessageFailureAlertThreshold'] <= 0 || $subParams['MessageFailureAlertThreshold'] >= 10) {
            $subParams['MessageFailureAlertThreshold'] = 7;
        }

        if ($subParams['MessageBlockedAlertThreshold'] <= 0) {
            $subParams['MessageBlockedAlertThreshold'] = 5000;
        }

        if ($subParams['AlarmInterval'] <= 0) {
            $subParams['AlarmInterval'] = 180;
        }

        /*if(!empty($subParams['AlerterEmails'])){
            $emails = explode(',', $subParams['AlerterEmails']);
            foreach($emails as $v){
                if(!\Lib\Util\String::isValidEmail($v)){
                    $res['errors'][] = '电子邮箱('.$v.')格式非法！';
                }
            }
        }
        if(!empty($subParams['AlerterPhoneNumbers'])){
            $phones = explode(',', $subParams['AlerterPhoneNumbers']);
            foreach($phones as $v){
                if(!\Lib\Util\String::isValidPhoneNumber($v)){
                    $res['errors'][] = '电话号码('.$v.')格式非法！';
                }
            }
        }*/
        if($subParams['Concurrency'] > $defaultSettings['MaxSendersPerChannel']){
            $res['errors'][] = '推送并发数超过最大限制！';
        }
        if($subParams['ConcurrencyOfRetry'] > $defaultSettings['MaxSendersPerRetryChannel']){
            $res['errors'][] = '"(重试队列)推送并发数"超过最大限制！';
        }

        if($subParams['ProcessTimeout'] > $defaultSettings['MaxMessageProcessTime']){
            $res['errors'][] = '消息处理超时"超过最大限制！';
        } else if($subParams['ProcessTimeout'] == 0){
            $subParams['ProcessTimeout'] = $defaultSettings['DefaultMaxMessageProcessTime'];
        }

        if($subParams['IntervalOfSending'] <= 0){
            $subParams['IntervalOfSending'] = 0;
        }

        $res['data'] = $data;
        $res['subParams'] = $subParams;
        $res['defaultParams'] = $defaultSettings;
        return $res;
    }

}