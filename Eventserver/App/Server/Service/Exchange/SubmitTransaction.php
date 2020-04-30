<?php

namespace App\Server\Service\Exchange;

/**
 * start a transation to exchange certain tasks.<br />
 * Exchange center will send the message to the destination server, after the
 * destination has done the job, it will respone to the notify URL.
 * @todo implemented
 * @author Su Chao<suchaoabc@163.com>
 */
class SubmitTranscation extends \Lib\BaseService {
    /**
     *
     * @param multiple $message
     *            the message that will be delivered to the destination.
     * @param string $channelIdKey
     *            the unique key to identify the channel which should has be
     *            register by {@link RegisterChannel}.
     * @param string $destUrl
     *            the destination to send the message. e.g.
     *            http://koubei.jumei.com/charge.php
     * @param string $notifyUrl
     *            the callback Url the destination server will use when the job
     *            is done.
     */
    public function __construct($message, $channelIdKey, $destUrl, $notifyUrl) {
    }
}
