<?php
namespace App\Admin\Controller\Auth;

class Base extends \Lib\BaseController {
    public function __construct()
    {
        $auth = new \Lib\Auth;
        $auth->ensureLogin();
    }

    public function __destruct()
    {
        $user = \Lib\User::current();
        \Lib\Log::instance('admin')->log(array($user->info->name, $this->getController(), $this->getAction(), $this->requestParams->getAll()));
    }
}