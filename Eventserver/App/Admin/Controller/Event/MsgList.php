<?php
namespace App\Admin\Controller\Event;

use App\Admin\Controller\Auth\Base;

class MsgList extends Base {

    protected $model = null;
    protected $fieldList = array('class_name', 'class_key', 'comment');
    public function model() {
        if(!$this->model || !($this->model instanceof \App\Admin\Model\Event\Message)) {
            $this->model = new \App\Admin\Model\Event\Message();
        }
        return $this->model;
    }

    public function execute() {
        $op = $this->requestParams->getGet('op');
        $id = $this->requestParams->getGetInt('class_id');
        if (empty($op)) {
            $op = 'show';
        }
        switch ($op) {
            default:
            case 'show':
                $this->showList();
                break;
            case 'edit':
                $this->edit($id);
                break;
            case 'del':
                $this->del();
                break;
            case 'add':
                $this->add();
                break;
        }
    }

    public function showList(){
        $pageNo     = $this->requestParams->getGetInt('page_no');
        if (!$pageNo) {
            $pageNo = 1;
        }
        $key        = $this->requestParams->getGet('class_key');
        $name       = $this->requestParams->getGet('class_name');
        $cond = array();
        if (!empty($key)) {
            $cond['class_key'] = $key;
        }
        if (!empty($name)) {
            $cond['class_name'] = $name;
        }
        $result     = $this->model()->getMsgList($cond, $pageNo);
        $params     = array('list' => $result['data']);
        $paging     = \Lib\Util\View::generatePagingParams($result['count'], 10, $pageNo);

        $tpl        = $this->getTemplate();
        $params = array(
            'list' => $result['data'],
            'query' => $_GET,
            'paging' => $paging,
            'count' => $result['count'],
            'messageLists' => isset($_SESSION['message']) ? $_SESSION['message'] : null,
        );
        unset($_SESSION['message']);
        $tpl->assign($params, NULL);
        $tpl->display();

    }

    public function edit() {
        if ($this->requestParams->getPost('class_name')) {
            $fields = array_intersect_key($_POST, array_flip($this->fieldList));
            $fields['class_key'] = trim($fields['class_key']);
            try {
                if (empty($fields['class_key'])) {
                    throw new \Exception('分类key 不能为空');
                }

                $this->model()->updateByPrimaryKey($this->requestParams->getPostInt('class_id'), $fields);
                \MedApi\Client::config((array)\Lib\Util\Sys::getAppCfg("MedApi"));
                \MedApi\Client::call("ClearDataCache");
                $_SESSION['message']['alert alert-info'][] = "修改成功!!";

                //log
                $user = \Lib\User::current();
                $username = $user->info->name;
                $args = var_export($fields, true);
                \Lib\Log::instance('admin')->log("[$username] [编辑消息分类] [$args]\r\n");

            } catch (\Exception $e) {
                $_SESSION['message']['alert alert-error'][] = "修改失败!!";
                $_SESSION['message']['alert alert-error'][] = $e->getMessage();
            }
            header("Location:/Event/MsgList");
            exit;
        }
        $id = $this->requestParams->getGetInt('class_id');
        $tpl = $this->getTemplate();
        $msg = $this->model()->getByPrimaryKey($id);
        $params = array(
            'msg' => $msg,
            'title' => "编辑消息分类 <em>( 当前ID:{$msg['class_id']} )</em>",
            'button' => '修改',
        );
        $tpl->assign($params);
        $tpl->display('Event/EditMsg');
    }

    public function del() {
        $id = $this->requestParams->getGet('class_id');
        try {
            $subscription = new \App\Admin\Model\Event\Subscription();
            if ($subscription->getSubscriptionCount(array('mc.class_id' => $id)) > 0) {
                throw new \Exception('消息分类无法被删除，消息分类下还有订阅者.');
            }

            $this->model()->deleteByPrimaryKey($id);
            $_SESSION['message']['alert alert-info'][] = "删除成功!!";
            \MedApi\Client::config((array)\Lib\Util\Sys::getAppCfg("MedApi"));
            \MedApi\Client::call("ClearDataCache");
            //log
            $user = \Lib\User::current();
            $username = $user->info->name;
            $args = var_export(array('class_id' => $id), true);
            \Lib\Log::instance('admin')->log("[$username] [删除消息分类] [$args]\r\n");
        } catch (\Exception $e) {
            $_SESSION['message']['alert alert-error'][] = "删除失败!!";
            $_SESSION['message']['alert alert-error'][] = $e->getMessage();
        }
        header("Location:/Event/MsgList");
    }

    public function add() {
        if ($this->requestParams->getPost('class_name')) {
            $fields = array_intersect_key($_POST, array_flip($this->fieldList));
            $fields['class_key'] = trim($fields['class_key']);
            try {
                if (empty($fields['class_key'])) {
                    throw new \Exception('分类key 不能为空');
                }

                $fields['create_time'] = time();
                $this->model()->insert($fields);
                $_SESSION['message']['alert alert-info'][] = "添加成功!!";

                //log
                $user = \Lib\User::current();
                $username = $user->info->name;
                $args = var_export($fields, true);
                \Lib\Log::instance('admin')->log("[$username] [添加消息分类] [$args]\r\n");

            } catch (\Exception $e) {
                $_SESSION['message']['alert alert-error'][] = "添加失败!!";
                $_SESSION['message']['alert alert-error'][] = $e->getMessage();
            }
            header("Location:/Event/MsgList");
            exit;
        }
        $params = array(
            'title' => '添加消息分类',
            'button' => '添加',
        );
        $tpl = $this->getTemplate();
        $tpl->assign($params);
        $tpl->display("Event/AddMsg");
    }
}

