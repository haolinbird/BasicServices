<?php 
namespace App\Admin\Controller\Auth;

class Login extends \Lib\BaseController {
	public function execute(){
        $auth = new \Lib\Auth;
        $auth->login();
        $auth->redirectExit(HTTP_BASE_URL);
	}
}
