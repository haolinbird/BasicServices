<?php
require __DIR__ . '/init.php';

$app_key = '57937f78f5a211e2a3b0d4ae52a40775';
$url = "https://auth.int.jumei.com/api/member/?app_key={$app_key}&app_name=meman&mobile={phone}";

$db = \App\Admin\Model\Event\Subscription::instance()->db()->write(\App\Admin\Model\Event\Subscription::DATABASE);

function getAuth($phone) {
    global $url;

    $ch = curl_init(str_replace('{phone}', $phone, $url));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = @json_decode($response, true);
    if ($data && isset($data['username'])) {
        return $data['username'];
    }

    return false;
}

function saveAlerterReceiver($subscription_id, $account) {
    global $db;

    if (empty($account)) {
        return;
    }

    // 导入对应的auth设置.
    $sql = 'INSERT INTO subscription_params(subscription_id, param_name, param_value)VALUES("' . $subscription_id . '", "AlerterReceiver", "' . $account . '")';
    $db->query($sql);
    echo "auth帐号已导入:$subscription_id/$account\r\n";
}

// 查找之前设置的所有报警phone和email.
// 找到之前所有的报警接收方式.
$data = $db->query('SELECT * FROM subscription_params WHERE param_name = "AlerterPhoneNumbers" OR param_name = "AlerterEmails" OR param_name = "AlerterReceiver"')->fetchAll(\PDO::FETCH_ASSOC);

$mapping = array();

foreach ($data as $row) {
    if (! isset($mapping[$row['subscription_id']])) {
        $mapping[$row['subscription_id']] = array();
    }

    if (empty($row['param_value']) || trim($row['param_value']) == '') {
        continue;
    }

    if ($row['param_name'] == 'AlerterEmails') {
        $emails = explode(',', $row['param_value']);
        foreach ($emails as $email) {
            $pos = strpos($email, '@');
            if ($pos === false) {
                echo "email格式错误:$email\r\n";
                continue;
            }

            $account = substr($email, 0, $pos);
            $mapping[$row['subscription_id']][] = strtolower($account);
        }
    } else if ($row['param_name'] == 'AlerterPhoneNumbers') {
        $phones = explode(',', $row['param_value']);
        foreach ($phones as $phone) {
            $account = getAuth($phone);
            if (empty($account)) {
                echo "无法根据号码查询auth帐号:$phone\r\n";
                continue;
            }

            $mapping[$row['subscription_id']][] = strtolower($account);
        }
    } else if ($row['param_name'] == 'AlerterReceiver') {
        $auths = explode(',', $row['param_value']);
        foreach ($auths as $account) {
            $account = trim($account);
            if (empty($account)) {
                echo "用户填写的auth帐号存在问题:" . $row['param_value'] . "\r\n";
                continue;
            }

            $mapping[$row['subscription_id']][] = strtolower($account);
        }
    }
}

foreach ($mapping as $subscription_id => $accounts) {
    $accounts = array_unique($accounts);

    $sql = 'DELETE FROM subscription_params WHERE subscription_id = "' . $subscription_id . '" AND param_name = "AlerterReceiver"';
    $db->query($sql);

    saveAlerterReceiver($subscription_id, implode(',', $accounts));
}

\MedApi\Client::config((array)\Lib\Util\Sys::getAppCfg("MedApi"));
// 导入auth后需要推送参数到medis.
$data = $db->query('SELECT DISTINCT subscription_id FROM subscription_params')->fetchAll(\PDO::FETCH_ASSOC);
if ($data) {
    foreach ($data as $row) {
        $sql = 'SELECT * FROM subscription_params WHERE subscription_id = "' . $row['subscription_id'] . '"';
        $params = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        if ($params) {
            $subParams = array();
            foreach ($params as $param) {
                $subParams[$param['param_name']] = $param['param_value'];
                if (in_array($param['param_name'], array('Concurrency', 'ConcurrencyOfRetry', 'IntervalOfSending', 'ProcessTimeout'))) {
                    $subParams[$param['param_name']] = (int)$param['param_value'];
                } else if ($param['param_name'] == 'AlerterEnabled') {
                    $subParams[$param['param_name']] = (bool)$param['param_value'];
                }
            }

            try {
                // 注意api调用顺序.
                \MedApi\Client::call("SetSubscriptionParams",
                    array('SubscriptionId' => (int)$row['subscription_id'],
                          'Params' => $subParams)
                );
                \MedApi\Client::call("ClearDataCache");
                echo "推送MedApi参数:", $row['subscription_id'], "\r\n";
            } catch (\Exception $e) {
                echo "推送MedApi参数失败:", $row['subscription_id'], ':', $e->getMessage(), "\r\n";
            }
        }
    }
}

