<?php

namespace App\Server\Service\User;

use Lib\TransactionException;

/**
 * Register a subscriber.<br />
 * <h3>Examples:</h3>
 * <h4>Params</h4>
 * <pre>
 * <code>
 * $subscribeName = '聚美电商平台';
 * $subscribeIdKey = 'jumei';
 * $secretKey = 'XD$FF%ML';
 * $comment = '聚美电商平台,主要包括特卖及商城';
 * </code>
 * </pre>
 * <h4>Return</h4>
 * <pre>
 * <code>
 * $return = 23123; //成功: 新的订阅者ID; 失败: false
 * </code>
 * </pre>
 * 
 * @author Su Chao<suchaoabc@163.com>
 */
class Register extends \Lib\BaseService {
    /**
     *
     * @param string $subscriberName            
     * @param string $subscriberKey
     *            Unique key to identify the subscriber. Rather than
     *            subscribeId, subscriveIdKey can be generated manually and easy
     *            to recognize.
     * @param string $secretKey
     *            secret key when login
     * @param string $comment
     *            additional comments for the subscriber
     * @return multiple false when failed, subscribeId when successs
     */
    public function execute($subscriberName, $subscriberKey, $secretKey, $comment = '') {
        $subscriber = new \Model\Subscriber ();
        $result = $subscriber->register ( $subscriberName, $subscriberKey, $secretKey, $comment );
        if (false === $result) {
            throw new TransactionException ( 'Failed to register subscriber', null, $subscriber->errors () );
        }
        return $result;
    }
}
