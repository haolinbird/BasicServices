<?php

namespace App\Server\Service\Broadcast;

/**
 * Query the status of a class of message.
 * It can be used to get the information including the sending status, who
 * received or failed to receive the messages etc.<br /><br />
 * <strong>ParamExample</strong>
 * <code>
 * $messageClassKey = 'KoubeiUserUprade';
 * $subscribeIdKey = 'Jumei';
 * $hitoryLength = 10;
 * </code>
 * 
 * @author Su Chao<suchaoabc@163.com>
 * @todo not implemented
 */
class QueryStatus extends \Lib\BaseService {
    /**
     *
     * @param string $messageClassKey
     *            a unique key represents the class of the message, e.g.
     *            KoubeiUserUprade
     * @param string $subscribeIdKey
     *            Unique key to identify the subscriber.
     * @param int $historyLength
     *            the maxium history record count to retrieve. default 1 , means
     *            the last message.
     */
    public function execute($messageClassKey, $subscribeIdKey, $hitoryLength = 1) {
    }
}
