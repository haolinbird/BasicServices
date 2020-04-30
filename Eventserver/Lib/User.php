<?php
namespace Lib;
/**
 * 当前登录用户对象
 */
class User{
    const USER_INFO_SESSION_KEY = 'user_info';
    protected static $instance;
    /**
     * @var int   -1 未登录; 0 已经登录
     */
    protected $status;
    protected $info;
    protected $isLoggedIn;

    protected function __construct()
    {

        if(isset($_SESSION[self::USER_INFO_SESSION_KEY]) && $this->checkUserInfo($_SESSION[self::USER_INFO_SESSION_KEY]))
        {
            $this->info = $_SESSION[self::USER_INFO_SESSION_KEY];
            $this->status = 0;
        }
        else
        {
            $this->resetUserInfo();
        }
    }

    public function __get($name)
    {
        switch ($name) {
            case 'status':
                return $this->status();
                break;
            case 'name':
                return $this->name();
            case 'info':
                return $this->info;
                break;
            case 'isLoggedIn' :
                    return $this->status === 0;
                break;
            default:
                trigger_error('Undefined property: '.__CLASS__.'::$'.$name, E_USER_NOTICE);
                break;
        }
    }

    public static function current()
    {
        return self::$instance instanceof self ? self::$instance : self::$instance = new self();
    }

    protected function resetUserInfo()
    {
        $this->info = (object) array('name'=>null, 'fullname'=>null);
        $this->status = -1;
    }

    protected function info()
    {
        return $this->info;
    }
    protected function name()
    {
        return $this->info->name;
    }
    protected function status()
    {
        return $this->status;
    }
    /**
     * $userInfo = array('name'=>'username', 'fullname'=>'Michael Lee')
     */
    public function login($userInfo)
    {
        if($this->checkUserInfo($userInfo))
        {
            $_SESSION[self::USER_INFO_SESSION_KEY] = $userInfo;
            $this->status = 0;
            return true;
        }
        return false;
    }

    public function logOut()
    {
        unset($_SESSION[self::USER_INFO_SESSION_KEY]);
        $this->resetUserInfo();
    }
    public function checkUserInfo($userInfo, &$message=array())
    {
        if(empty($userInfo->name))
        {
            $message[] = '用户名为空';
        }
        else if(empty($userInfo->fullname))
        {
            $message[] = '没有完整的用户名';
        }
        else
        {
            return true;
        }
        return false;
    }
}