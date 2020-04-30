<?php
/**
 * Class Tube 
 * @author Haojie Huang<haojieh@jumei.com>
 */
namespace App\Admin\Controller\QueueMonitor;

use App\Admin\Controller\Auth\Base;
use \App\Admin\Model;

class Tube extends Base
{
    public function execute()
    {
    

        $name = $this->requestParams->getGet('name');
        $serverIndex = $this->requestParams->getGetInt('server_index');
        $jobStatus= array();
        $fields = \Lib\BeanstalkMonitor::$statsMapping;
        $jobAttris = \Lib\BeanstalkMonitor::$jobAttris;
        
        if (!$name) {
            $this->tipMessageManager->addMessageError("tube name is required");
        } else {
            /*$hosts = \Lib\Util\Sys::getAppCfg('Beanstalk');
            $rc = new \ReflectionClass($hosts);
            $hosts = $rc->getStaticProperties();*/
            $beanstalkCfg = \Lib\Util\Sys::getAppCfg('Beanstalk');
            $hosts = $beanstalkCfg::$servers;
            $hostList = array();
            foreach($hosts as $gHosts){
                foreach($gHosts as $cHost){
                    $hostList[] = $cHost;
                }
            }
            $bsm = new \Lib\BeanstalkMonitor($hostList);
            $bsPool = $bsm->getInstancePools();
            $tubeStats = $bsm->getOneTubeStats($bsPool[$serverIndex], $name);
            $jobStatus = $bsm->peekAll($bsPool[$serverIndex], $name);
        }
        $tpl        = $this->getTemplate();
        $tpl->assign(compact('tubeStats', 'jobStatus', 'fields', 'jobAttris'));
        $tpl->display();

    }
}
