<?php 

namespace App\Admin\Cfg;

class Auth{
	const APP_NAME              = 'jm_event_admin_dev';
	const APP_KEY               = 'fdd3a728ef9411e2bac600163611654a';
	const SESSION_KEY_AUTH_INFO = 'meman_auth_info';
    const BASE_URL              = 'http://auth.jumeird.com/api/';
    //以下配置一般情况下不需要修改.
    const LOGIN_PATH = 'login/';
    const GROUP_PATH = 'getgroupbyuid/';
    const ROLE_PATH = 'getrolebyuid/';
    const INFO_PATH = 'info/';
    const MEMBER_PATH = 'member/';
    const GROUP_ROLE_PATH = 'grouprole/';
    const ROLE_MAIL_PATH = 'rolemail/';
    const GROUP_MAIL_PATH = 'groupmail/';

    //group同步时间间隔5min
    const GROUP_SYNC_TIME= '300';
}
