<?php
namespace App\Admin\Controller\Index;
class Index extends \App\Admin\Controller\Auth\Base
{
    public function execute()
    {
        $tpl = $this->getTemplate()->display();
    }
}