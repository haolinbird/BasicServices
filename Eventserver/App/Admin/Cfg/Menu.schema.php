<?php
namespace App\Admin\Cfg;
class Menu{
    public static $items = array(array('group_name'=>'消息分类管理',
                                       'list'=>array(array('title'=>'添加消息类型',
                                                           'url'=>'/Event/MsgList?op=add'
                                                          ),
                                                     array('title'=>'消息类型列表',
                                                           'url'=>'/Event/MsgList'
                                                          )
                                                   )
                                    ),
                                array('group_name'=>'账号管理',
                                      'list'=>array(array('title'=>'添加账号',
                                                          'url'=>'/Event/Subscriber?op=add'
                                                          ),
                                                    array('title'=>'账号列表',
                                                          'url'=>'/Event/Subscriber'
                                                          )
                                                   )
                                      ),
                                array('group_name'=>'订阅管理',
                                        'list'=>array(array('title'=>'添加订阅',
                                                            'url'=>'/Event/SubscriptionAdd'
                                                           ),
                                                      array('title'=>'订阅列表',
                                                            'url'=>'/Event/SubscriptionList'
                                                           )
                                                     )
                                    ),
                                array('group_name'=>'系统信息',
                                        'list'=>array(array('title'=>'消息队列状态统计',
                                                            'url'=>'/QueueMonitor/Stats'
                                                           ),
                                                      array('title'=>'消息推送失败日志',
                                                            'url'=>'/Event/LogList'
                                                           ),
                                                      array('title'=>'系统日志下载',
                                                            'url'=>'/Event/LogDownload'
                                                           ),
                                                      array('title'=>'Redis 信息统计',
                                                                'url'=>'/RedisInfo/Stats'
                                                        ),
                                                      array('title'=>'消息队列Redis使用情况',
                                                                'url'=>'/RedisInfo/MessageList'
                                                            ),
                                                      array('title'=>'缺省报警设置',
                                                            'url'=>'/Event/Setting'
                                                           ),
                                                      array(
                                                        'title' => 'prometheus',
                                                        'url' => "#{mec.prometheus.url}",
                                                        'target' => '_blank',
                                                      ),
                                                    )
                                    )
                                );
}
