<?php
namespace App\Admin\Controller\Event;

use App\Admin\Controller\Auth\Base;
use App\Admin\Model\Event\SysParams;

class Setting extends Base
{
    public function execute()
    {
        if ($this->requestParams->isPost()) {
            try {
                $auth = $this->requestParams->getPost('auth', '');
                $messageType = $this->requestParams->getPost('message_type');
                if (empty($messageType)) {
                    $messageType = array();
                }

                $auth = preg_replace('![^a-z0-9]+!i', ',', $auth);
                $auth = explode(',', $auth);

                $account = array();
                foreach ($auth as $user) {
                    $user = trim($user);
                    if ($user != '') {
                        $account[] = $user;
                    }
                }

                SysParams::instance()->replace(array(
                    'param_name' => SysParams::FIELD,
                    'param_value' => json_encode(array(
                        'account' => $account,
                        'message_type' => $messageType,
                    )),
                ));

                \MedApi\Client::config((array)\Lib\Util\Sys::getAppCfg("MedApi"));
                \MedApi\Client::call(
                    'SetDefaultAlarm',
                    array(
                        'default_alarm_receiver' => implode(',', $account),
                        'default_alarm_chan' => false == empty($messageType) ? str_pad(decbin(array_sum($messageType)), 8, '0', STR_PAD_LEFT) : "",
                    )
                );
            } catch (\Exception $e) {
                print_r($e);
                exit;
            }
        }

        $result = SysParams::instance()->getOne(array(
            'param_name' => SysParams::FIELD,
        ));
        $data = false == empty($result) ? json_decode($result['param_value'], true) : array();

        $tpl = $this->getTemplate();
        $tpl->assign(array(
            'message_item' => array(
                array('value' => 1, 'label' => '邮件'),
                array('value' => 2, 'label' => '短信'),
                array('value' => 4, 'label' => '微信'),
            ),
            'data' => $data,
        ));
        $tpl->display();
    }
}