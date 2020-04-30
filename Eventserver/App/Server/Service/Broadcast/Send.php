<?php

namespace App\Server\Service\Broadcast;

use EventClient\RpcLogicalException;
use Lib\Util\Broadcast as util;
use Lib\RpcServer as RS;
use Lib\Log as Logger;
use App\Server\Cfg\Service as CS;
use App\Server\Model\MessageClasses as MMC;
use App\Server\Model\Subscriber as MS;

/**
 * Request to send a message.<br />
 * If success,the message will be pushed into the sequence for broadcasting.<br
 * />
 * <h3>Examples:</h3>
 * <h4>Params</h4>
 * <pre>
 * <code>
 * $messageClassKey = 'KoubeiUserUprade';
 * $message = array('uid'=>9527,
 * 'grade'=>3,
 * 'reason'=>'购买商品总额已经超过10万'
 * );
 * </code>
 * </pre>
 * <h4>Return</h4>
 * <pre>
 * <code>
 * $return = true; //成功: true; 失败: false
 * </code>
 * </pre>
 *
 * @author Su Chao<suchaoabc@163.com>
 */
class Send extends \Lib\BaseExternalService {
    /**
     *
     * @param string $messageClassKey
     * @param multiple $message
     * @param int $priority
     *            [optional] lower means higher priority. default is 1.
     * @param int $timeToDelay
     *            [optional] seconds to delay sending the message, if less than
     *            1 second means sending it immediately. default is 0.
     * @param string $senderKey  name key of the sender. this param is currently used for recovering data only
     */
    public function execute($messageClassKey, $message, $priority = 1, $timeToDelay = 0, $senderKey=null)
    {
        $eventclientCfgs = (array) \Lib\Util\Sys::getAppCfg('EventClient');
        if (!isset($eventclientCfgs['default'])){
            throw new \EventClient\RpcLogicalException('event client config("default") not found.');
        }
        $reqData = RS::instance()->requestData;
        $cfg = $eventclientCfgs['default'];
        $cfg['user'] = $reqData['user'];
        $cfg['secret_key'] = $reqData['secret_key'];
        $ec = \EventClient\RpcClient::instance($cfg);
        return $ec->setClass('Broadcast')->Send($messageClassKey, $message, $priority, $timeToDelay);
    }
}
