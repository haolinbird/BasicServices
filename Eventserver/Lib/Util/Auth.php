<?php
namespace Lib;

/**
 * Service authentication
 * @global $_SESSION, $_GET
 */
class Auth {
    protected $cfg;
    public function __construct()
    {
        $this->cfg = \Lib\Util\Sys::getAppCfg('Auth');
    }

    public function ensureLogin()
    {
        $cfg = $this->cfg;
        $user = User::current();
        if(!$user->isLoggedIn || !isset($user->info->groupPaths['groups']) || !count($user->info->groupPaths['groups']))
        {
            $login_url = $cfg::BASE_URL.$cfg::LOGIN_PATH.'/?camefrom='. $cfg::APP_NAME;
            self::redirectExit($login_url);
        }
    }

    public function login()
    {
        if (!empty($_GET['token']) && !empty($_GET['username']))
        {
            $session_id = $this->genAuthSessionId($_GET['token'], $_GET['username']);
            $userInfo = $this->getUserInfo($session_id);
            if($userInfo && isset($userInfo['username']))
            {
                $userInfo['name'] = $userInfo['username'];
                unset($userInfo['username']);
                $userInfo = (object) $userInfo;
                $userInfo->authSessionId = $session_id;
                $userInfo->groupPaths = $this->getGroupRoles($userInfo->name);
                return User::current()->login($userInfo);
            }
        }
        return false;
    }

    protected function genAuthSessionId($authToken, $username)
    {
        $cfg = $this->cfg;
        return sha1($authToken.$cfg::APP_KEY.$username);
    }

    public function getUserInfo($sessionId)
    {
        $cfg = $this->cfg;
        $userInfo = file_get_contents($cfg::BASE_URL.$cfg::INFO_PATH.'?session_id=' . $sessionId);
        $userInfo = @json_decode($userInfo, true);
        return $userInfo;
    }

    public function getSessionId()
    {
        return $this->sessionId;
    }

    public function getGroupRoles($username)
    {
        $cfg = $this->cfg;
        $grouproles = file_get_contents($cfg::BASE_URL.$cfg::GROUP_ROLE_PATH.'?uid=' . $username.'&app_key='.$cfg::APP_KEY.'&app_name='.$cfg::APP_NAME);
        $grouproles = @json_decode($grouproles, true);
        return $grouproles;
    }

    public static function redirectExit($url, $code = 302) {
        header('Location:' . $url, $code);
        exit();
    }

}
