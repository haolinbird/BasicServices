<?php
return array(/* Sms接收者
                 如: 'SmsReceiver' => array (
                                        0 =>
                                            array (
                                                'msg_key' => 'PromoSalesCard,CreateShipping',
                                                'mobile_no' => '18683527809',
                                            ),
                                        1 =>
                                            array (
                                                'msg_key' => 'deal_inventory_sku,deal_inventory',
                                                'mobile_no' => '18030629256',
                                            ),
            */
            'SmsReceiver' => "#{EventCenter.Alerter.SmsReceiver}",
             // 短信报警网关
            'SmsGateway' => "#{EventCenter.Alerter.SmsGateway}",
             /*
              * 相关选项
             包括: ALERT_FAILURE_TIME_THRESHOLD 规定时间(此此时间内推送失败次数超过某值); ALERT_FAILURE_COUNT_THRESHOLD 在规定时间(秒)内达到某个次数则发警告消息.
              如: 'options' => array (
                                      'ALERT_FAILURE_TIME_THRESHOLD' => 20,
                                      'ALERT_FAILURE_COUNT_THRESHOLD' => 1,
             */
            'options' => "#{EventCenter.Alerter.Options}"
);

