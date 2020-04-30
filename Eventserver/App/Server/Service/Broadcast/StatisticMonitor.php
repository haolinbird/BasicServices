<?php
namespace App\Server\Service\Broadcast;

class StatisticMonitor extends \Lib\BaseService{
    public function execute()
    {
        $this->statsList();
        $this->cleanHistory();
    }

    /**
     * List数据处理.
     */
    protected function statsList()
    {
        $model = new \App\Server\Model\Statistic;
        $model->updateMessageList();
    }

    protected function cleanHistory()
    {
        $model = new \App\Server\Model\Statistic;

        $allKeys = $model->getAllSuccessListKeys();
        foreach ($allKeys as $key)
        {
            $portions = explode(':', $key);
            if(isset($portions[1]))
            {//删除4天以前的
                if(strtotime('-4 day') - strtotime($portions[1]) > 0)
                {
                    $model->deleteList($key);
                }
            }
        }
    }
}