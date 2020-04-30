<?php
namespace App\Admin\Controller\RedisInfo;

use App\Admin\Controller\Auth\Base;
use Lib\Util\Sys;

class MessageList extends Base
{
    public function execute()
    {
        $redis = \Lib\Redis::instance();

        $statisticsModel = new \App\Server\Model\Statistic();

        $info = $redis->info();
        $srvCfg = Sys::getAppCfg('Service', 'Server');
        $indexStart = $this->requestParams->index_start;
        $indexEnd = $this->requestParams->index_end;
        $currentKey = $this->requestParams->key;
        $listSuccess = $listFailure = array();
        if($currentKey === '') $currentKey = null;
        if($indexStart === '') $indexStart = null;
        if($indexEnd === '') $indexEnd = null;
        if(!is_null($currentKey) && (!is_null($indexEnd) || !is_null($indexStart)))
        {
            if(is_null($indexEnd) && !is_null($indexStart))
            {
                $indexEnd = $indexStart = (int) $indexStart;
            }
            else if(is_null($indexStart) && !is_null($indexEnd))
            {
                $indexEnd = $indexStart = (int) $indexEnd;
            }
            else
            {
                $indexEnd = (int)$indexEnd;
                $indexStart = (int)$indexStart;
            }
            if($this->requestParams->action == 'peek_success')
            {
                $listSuccess = $redis->lRange($currentKey, $indexStart, $indexEnd);
            }
            if($this->requestParams->action == 'peek_failure')
            {
                $listFailure = $redis->lRange($currentKey, $indexStart, $indexEnd);
            }
        }

        if($this->requestParams->action == 'del_key' && $this->requestParams->key && strpos($this->requestParams->key, $srvCfg::MESSAGE_IN_LOG_SUCCESS_KEY) === 0)
        {//删除成功入列的日志
            $statisticsModel->updateMessageList();
            $redis->del($this->requestParams->key);
        }

        if($this->requestParams->action == 'del_recover_lock_key')
        {
            $redis->del(\App\Server\Cfg\Service::MESSAGE_IN_LOG_FAILURE_KEY.'_recover_lock');
        }

        if($this->requestParams->action == 'update_statistic')
        {
            ignore_user_abort(true);
            $statisticsModel->updateMessageList();
        }

        $tpl        = $this->getTemplate();
        $tpl->assign(
            array(
                'successLogKeys'       => $statisticsModel->getAllSuccessListKeys(),
                'failureLogKeys'       => $redis->keys($srvCfg::MESSAGE_IN_LOG_FAILURE_KEY.'*'),
                'listSuccess'              => $listSuccess,
                'listFailure'              => $listFailure,
                'currentKey'                => $currentKey,
                'indexStart'        => $indexStart,
                'indexEnd'        => $indexEnd,
                'statics'        => $statisticsModel->getAllStatistics()
            )
        );
        $tpl->display();
    }
}

