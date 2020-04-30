<?php
namespace App\Server\Cfg;
/**
 * Configurations for service daemon.<br />
 * <b>Note,only the following CONSTANTS have to be configured when deploying considering the server environment.</b>
 * <ul>
 * <li>ENABLE_SENT_LOG</li><li>ENABLE_OUTPUT_LOG</li>
 * </ul>
 *
 */
class Service{
    /**
     * user ID of  event service.
     */
    const DAEMON_UID=33;

   /**
    * user group ID of event service.
    */
    const DAEMON_GID=33;

    /**
     * compress messages using deflate.
     *
     * @var boolean
     */
    const COMPRESS_MESSAGE = true;

   /**
    * @var boolean whether to log all incoming messages.
    */
    const ENABLE_INCOMING_MESSAGE_LOG = true;

    /**
     * TRUE to enable  to log all messages successfully recived and pushed into queue.
     *
     * @var boolean
     */
    const ENABLE_IN_SUCCESS_LOG = true;

    /**
     * TRUE to enable  to log all messages successfully recived but failed being pushed into queue.
     *
     * @var boolean
     */
    const ENABLE_IN_FAILURE_LOG = true;

    /**
     * redis key to stores all messages received from clients and successfully sent to message queue
     *
     * @var string
     */
    const MESSAGE_IN_LOG_SUCCESS_KEY = 'event_center_message_in_success_log';

    /**
     * redis key to stores all messages received from clients and failed sent to message queue
     *
     * @var string
     */
    const MESSAGE_IN_LOG_FAILURE_KEY = 'event_center_message_in_failure_log';

    /**
     * Redis key to store statistics.
     * @var string
     */
    const EVENT_CENTER_SATISTIC_KEY = 'event_center_message_statistic';

    /**
     * Static update interval in ms.
     * @var int
     */
    const STATIC_UPDATE_INTERVAL = 10000;


    /**
     * True to log ouput, logger is configured in {@link \Cfg\Log::$messageSentLogOutput}
     * @var boolean
     */
    const ENABLE_OUTPUT_LOG = false;
    /**
     * name of the tube/queue for the message to send.It will be used on beanstalk or other message queue servers.
     * @var string
     */
    const TUBE_EVENT_CENTER_MESSAGES = 'event_center_messages';
    /**
     * name of the tube/queue for the log of message that was failed to sent to the target subscriber.It will be used on beanstalk or other message queue servers.
     * @var string
     */
    const TUBE_EVENT_CENTER_MESSAGES_SENT_FAILURE = 'event_center_messages_sent_failure';

    /**
     * message serializer (required). default is json.<br />
     * if you have msgpack extension installed,you can set it as 'msgpack' to gain better performance.<br />
     * but DONOT change it once the system is online.
     * @var string
     */
    const EVENT_MESSAGE_SERIALIZER = 'msgpack';//*required*

    /**
     * maxium retries to send the message to a subscriber when failure.
     * @var int
     */
    const MAX_MESSAGE_SEND_RETRY_TIMES = 10;//*required*

    /**
     * Coefficent of delayed time for re-sending messages
     * @var int
     */
    const RETRY_INTERVAL_CE = 10;

    /**
     * the interval between every broadcast.<br />
     * after a broadcast the daemon will sleep {@link BROADCAST_INTERVAL} to avoid impacting system resources.<br />
     * value is in milliseconds.
     * @var int
     */
    const BROADCAST_INTERVAL = 3;//*required*

    /**
     * maxium time in seconds for delieverying a message to a subscriber.
     * @var int
     */
    const MAX_MESSAGE_DELIVERY_TIME = 5;//*required*

    /**
     * maxium number of worker processes for pushing messages to subscribers
     * @var int
     */
    const MAX_MESSAGE_DELIVERER_WORKER_NUM = 5;

    /**
     * maxium number of worker processes for re-pushing messages to subscribers
     * @var int
     */
    const MAX_MESSAGE_RE_DELIVERER_WORKER_NUM = 2;

    /**
     * maxium memory that each service daemon can consume.
     * @var string
     */
    const SERVICE_DAEMON_MEMORY_LIMIT = '256M';//*required;

    /**
     * what services are opened. All sort of RPC services are subset of these open services.
     * @var array
     * *required*
     */
    public static $openServices = array('Broadcast::Send','MessageClass::Create','MessageClass::Subscribe', 'MessageClass::enableSend'
                );
}
