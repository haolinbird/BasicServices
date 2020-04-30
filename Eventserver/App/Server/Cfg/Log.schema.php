<?php
namespace App\Server\Cfg;
/**
 *
 * @author suchao
 *
 */
class Log{
    /**
     * root of all logs of type "file".
     * @var please make sure this file is writeable to php worker.
     */
    const FILE_LOG_ROOT = '/home/logs/jm-event-center/';
    
    /**
     * this log is generated by a third-party lib and cannot use the unified logger class,so it has to define a separated log file.<br />
     * please make sure this file is writeable to php worker. There should be logrotate for it if this file grows too large.
     */
    const MESSAGE_SENDER_DAEMON_LOG_FILE = '/var/log/jm-message-sender-daemon.log';

    /**
     * for database connections
     * @var array
     */
    public static $db = array('logger'=>'file',//logs are sent to PHP which is defined in php.ini as error_log
                              'rotate'=>False
                             );
    
    /**
     * for RPC services
     * @var array
     */
    public static $rpcServer = array('logger'=>'file',//logs are written to the file within the dir as below and will be devided by time
                                     'rotate'=>False
                             );
    /**
     * For all incoming message logs.
     * @var array
     */
    public static $inComingMsg = array('logger' =>'jsonfile',
                                       'rotate' => false
    );


    
    /**
     * for RPC client calls
     * @var arrray
     * @todo logger 'sys' is to be implemented
     */
    public static $rpcClient = array('logger'=>'sys',//logs will are sent to the OS(Linux) log system
                                     'msg_prefix'=>'PHP-RPC'//prefix for the each log message , since they may be mixed with other system message.
                              );
    
    /**
     * message sent log
     * @var array
     */
    public static $messageSentLogOutput = array('logger'=>'file',
                                                'rotate'=>False
                                );
    
    /**
     * server notices
     * @var array
     */
    public static $serverNotices = array('logger'=>'file',
                                         'rotate'=>False
                                        );
        
    /**
     * for general php errors e.g. E_NOTICES,E_WARNING
     * @var array
     */
    public static $phpErrors = array('logger'=>'file',
                                     'rotate' => false
                                    );
    
    /**
     * log errors about beanstalk
     * @var array
     */
    public static $beanstalkErrors = array('logger' => 'file',
                                           'rotate' => false
                                          );
    
    /**
     * log erros about redis errors
     * @var array
     */
    public static $redisErrors = array('logger' => 'file',
                                       'rotate' => false
                                      );
}
