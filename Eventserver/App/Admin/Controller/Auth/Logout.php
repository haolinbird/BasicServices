<?php
namespace App\Admin\Controller\Auth;

class Logout extends \Lib\BaseController {
    public function execute(){
        \Lib\User::current()->logout();
        header('Location: '.HTTP_BASE_URL, true, 302);
    }
}