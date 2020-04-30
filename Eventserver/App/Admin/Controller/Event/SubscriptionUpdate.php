<?php
/**
 * Class SubscriptionUpdate
 * @author Haojieh<haojieh@jumei.com>
 */
namespace App\Admin\Controller\Event;
use App\Admin\Model;

class SubscriptionUpdate extends SubscriptionBase
{
    public function execute()
    {
        header("Content-Type:text/javascript;charset:utf-8;");
        $res = array('code' => 'error', 'message' => array());
        $formalized = $this->validateInpputData();
        if(!empty($formalized['errors'])){
            $res['message'] = $formalized['errors'];
            \Lib\Util\Response::json($res);
            return;
        }

        $data = $formalized['data'];
        $subParams = $formalized['subParams'];
        $cond['subscription_id']   = $this->requestParams->getPost('subscription_id');
        if ( false === Model\Event\Subscription::instance() ->update($data, $cond)) {
            $res = array(
                'code'    => 'error',
                'message' => '修改失败',
            );
        } else {
            try {
                // 注意api调用顺序.
                \MedApi\Client::call("SetSubscriptionParams",
                    array('SubscriptionId' => (int)$cond['subscription_id'],
                          'Params' => $subParams)
                );
                \MedApi\Client::call("ClearDataCache");
                Model\Event\Subscription::instance()->saveSubscriptionParams($cond['subscription_id'], $subParams);
                $res = array('code'=>'success', 'message' => '修改成功');
            } catch (\Exception $ex) {
                $res = array(
                    'code'    => 'error',
                    'message' => '订阅参数保存失败: '.$ex->getMessage(),
                );
            }
        }
        // 同步订阅信息到压测环境.
        // Model\Event\Subscription::instance()->syncSubscriptionInfoToBench($this->requestParams->getPost('subscription_id'));
        echo json_encode($res);
    }
}
