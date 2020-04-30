<?php
namespace App\Server\Service\Broadcast;
use App\Server\Model as Model;

/**
 * Unsubscribe a broadcast message.<br />
 * <h3>Examples:</h3>
 * <h4>Params</h4>
 * <pre>
 * <code>
 * $subscribeId = 9527;
 * $messageClassKey = 'KoubeiUserUprade';
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
class Unsubscribe extends \Lib\BaseService {
    /**
     *
     * @param int $subscriberKey
     * @param string $messageClassKey
     *            a unique key represents the class of the message, e.g.
     *            KoubeiUserUprade
     */
    public function execute($subscriberKey, $messageClassKey) {
        $subscription = new Model\Subscription ();
        $result = $subscription->cancel ( $subscriberKey, $messageClassKey );
        if (false === $result) {
            throw new \Lib\TransactionException ( 'Failed to subscriber message(' . $messageClassKey . ')', null, $subscription->errors () );
        }
        return $result;
    }
}
