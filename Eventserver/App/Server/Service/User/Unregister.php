<?php

namespace App\Server\Service\User;

/**
 * Unregister a subscriber.It will delete/deactivate the subscriber and cancel
 * all of its subscribes.<br />
 * <h3>Examples:</h3>
 * <h4>Params</h4>
 * <pre>
 * <code>
 * $subscribeId = 9527;
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
 *        
 */
class Unregister extends \Lib\BaseService {
    /**
     *
     * @param string $subscribeId            
     * @return boolean
     */
    public function execute($subscribeId) {
        $subscriber = new \Model\Subscriber ();
        $result = $subscriber->unregister ( $subscribeId );
        if (false === $result) {
            throw new \Lib\TransactionException ( 'Failed to unregister subscriber', null, $subscriber->errors () );
        }
        return $result;
    }
}
