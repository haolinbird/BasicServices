<?php
namespace App\Server\Service\MessageClass;

use App\Server\Model\MessageClasses;
use App\Server\Model\Subscriber;
use Lib\RpcServer as RS;

/**
 * 创建消息类型.
 *
 */
class Create extends \Lib\BaseService{
    /**
     *
     * @param string $className 消息名称
     * @param string $classKey 消息类型key
     * @param string $comment   消息详细说明.
     * @throws \Lib\TransactionException
     * @return boolean
     */
    public function execute($className,$classKey,$comment='')
    {
        $reqData = RS::instance()->requestData;
        //for to use the 'user' from auth info in rpc cases
        if(!empty($reqData['user']))
        {
            $subscriberKey = $reqData['user'];
            $sModel = Subscriber::instance();
            if(!$sModel->canCreateMessageClass($subscriberKey))
            {
                throw new \Lib\TransactionException('Subscriber('.$subscriberKey.') is not allowed to create message class.', 61199);
            }
        }
        $mcModel = MessageClasses::instance();
        $re = $mcModel->add($className,$classKey,$comment);
        if($re)
        {
            return true;
        }
        throw new \Lib\TransactionException('Failed to create new message class', 61199, $mcModel->errors());
    }
}