<?php
namespace App\Server\Service\MessageClass;

use App\Server\Model\Subscription as MST;
use Lib\RpcServer as RS;
use App\Server\Model\Subscriber as MSBER;

/**
 * Subscribe a broadcast message.<br />
 * <h3>Examples:</h3>
 * <h4>Params</h4>
 * <pre>
 * <code>
 *   $messageClassKey = 'KoubeiUserUprade';
 *   $receptionChannel = 'https://br.koubei.jumei.com/notify_user_upgrade.php';
 * </code>
 * </pre>
 * <h4>Return</h4>
 * <code>
 *   $return = true; //成功: true; 失败: false
 * </code>
 *
 * @author Su Chao<suchaoabc@163.com>
 */
class Subscribe extends \Lib\BaseService{
    /**
     * @param string $messageClassKey
     * @param string $receptionChannel the channel via which the subscriber will recieve this class of the message. It's  actually a URL, e.g. https://br.koubei.jumei.com/notify_user_upgrade.php
     * @param string $subscriberKey
     * @param int $timeout timeout for pushing message
     * @param string $subscriberKey
     * @return Boolean
     */
    public function execute($messageClassKey,$receptionChannel, $timeout=5, $subscriberKey='')
    {
        $reqData = RS::instance()->requestData;
        //for to use the 'user' from auth info in rpc cases
        if(!empty($reqData['user']))
        {
            $subscriberKey = $reqData['user'];
        }

        if(! MSBER::instance()->canSelfMakeSubscription($subscriberKey))
        {
            throw new \Lib\TransactionException($subscriberKey. ' not to make subscription on its own, please request admin to do it.', 61200);
        }

        $subscription = new MST();
        $result = $subscription->make($subscriberKey, $messageClassKey, $receptionChannel);
        if(false === $result)
        {
            throw new \Lib\TransactionException('Failed to subscriber message('.$messageClassId.')', 61199, $subscription->errors());
        }
        return $result;
    }

}
