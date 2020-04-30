<?php
namespace App\Server\Service\MessageClass;

use App\Server\Model\MessageClasses;
use App\Server\Model\Subscriber;
use Lib\RpcServer as RS;

/**
 * 设置允许发送某类型消息
 *
 */
class EnableSend extends \Lib\BaseService{
    /**
     *
     * @param string $classKey 消息类型key
     * @throws \Lib\TransactionException
     * @return boolean
     */
    public function execute($classKey)
    {
        $sModel = Subscriber::instance();
        $reqData = RS::instance()->requestData;
        //for to use the 'user' from auth info in rpc cases
        $senderKey = $reqData['user'];
        if(!$sModel->canSelfEnableSendMessage($senderKey))
        {
            throw new \Lib\TransactionException('User('.$senderKey.') is not allowed to make this action, please contact admin to complete your request.', 61005);
        }
        $re = $sModel->enableSendMessage($senderKey, $classKey);
        if($re)
        {
            return true;
        }
        throw new \Lib\TransactionException('Failed to failed to enable send message', $sModel->errors());
    }
}