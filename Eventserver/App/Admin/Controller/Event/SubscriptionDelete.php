<?php
/**
 * Class Subscriptiondelete
 * @author Haojieh<haojieh@jumei.com>
 */

namespace App\Admin\Controller\Event;
use App\Admin\Controller\Auth\Base;
use App\Admin\Model;

class Subscriptiondelete extends Base
{

    public function execute()
    {
        header("Content-Type:text/javascript;charset:utf-8;");

        $res = array(
            'code'    => 'success',
            'message' => '删除成功',
        );

        $id = $this->requestParams->getPost('id');
        if ( ! Model\Event\Subscription::instance() ->deleteByPrimaryKey($id)) {
            $res = array(
                'code'    => 'error',
                'message' => '删除失败',
            );
        }
        Model\Event\Subscription::instance()->clearSubscriptionParams($id);
        \MedApi\Client::config((array)\Lib\Util\Sys::getAppCfg("MedApi"));
        \MedApi\Client::call("ClearDataCache");
        echo json_encode($res);
    }

}

