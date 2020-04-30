<?php
namespace App\Admin\Controller\Errors;
/**
 * process 404 pages
 * @author Su Chao<suchaoabc@163.com>
 */
class _404 extends \Lib\BaseController{
    public function execute()
    {
        $tpl = $this->getTemplate();
        $tpl->display();
    }
}