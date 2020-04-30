<?php
namespace App\Admin\Controller\Event;

use App\Admin\Controller\Auth\Base;

class Subscriber extends Base {

    protected $model = null;
    protected $fieldList = array('subscriber_name', 'subscriber_key', 'status', 'secret_key', 'comment', 'privileges', 'allowed_message_class_to_send');
    public function model() {
        if(!$this->model || !($this->model instanceof \App\Admin\Model\Event\Subscriber)) {
            $this->model = new \App\Admin\Model\Event\Subscriber();
        }
        return $this->model;
    }

    public function execute() {
        $op = $this->requestParams->getGet('op');
        $id = $this->requestParams->getGetInt('subscriber_id');
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
        $key        = $this->requestParams->getGet('subscriber_key');
        $name       = $this->requestParams->getGet('subscriber_name');

        $cond = array();
        if (!empty($key)) {
            $cond['subscriber_key'] = $key;
        }
        if (!empty($name)) {
            $cond['subscriber_name'] = $name;
        }
        $result     = $this->model()->getList($cond, $pageNo);
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
        if ($this->requestParams->getPost('subscriber_name')) {
            $fields = array_intersect_key($_POST, array_flip($this->fieldList));
            $class_send = &$fields['allowed_message_class_to_send'];
            $class_send = empty($class_send) ? '' : implode('|', $class_send);
            $fields['privileges'] = empty($fields['privileges']) ? '' : implode(',', $fields['privileges']);

            $fields['subscriber_key'] = trim($fields['subscriber_key']);
            $fields['secret_key'] = trim($fields['secret_key']);
            try {
                if (empty($fields['subscriber_key'])) {
                    throw new \Exception('订阅者key不能为空');
                }

                if (empty($fields['secret_key'])) {
                    throw new \Exception('订阅者私钥不能为空');
                }

                $this->model()->updateByPrimaryKey($this->requestParams->getPostInt('subscriber_id'), $fields);
                $_SESSION['message']['alert alert-sucess'][] = "修改成功!!";

                //log
                $user = \Lib\User::current();
                $username = $user->info->name;
                $args = var_export($fields, true);
                \Lib\Log::instance('admin')->log("[$username] [编辑订阅者] [$args]\r\n");

            } catch (\Exception $e) {
                $_SESSION['message']['alert alert-error'][] = "修改失败!!";
                $_SESSION['message']['alert alert-error'][] = $e->getMessage();
            }
            header("Location:/Event/Subscriber");
            exit;
        }
        $id = $this->requestParams->getGetInt('subscriber_id');
        $tpl = $this->getTemplate();
        $subscriber = $this->model()->getByPrimaryKey($id);
        $selected = array_flip(explode('|', $subscriber['allowed_message_class_to_send']));
        $msgList = new \App\Admin\Model\Event\Message();
        $msgList = $msgList->getMsgList(array(), 1, 2000);
        $msgList = $msgList['data'];
        $params = array(
            'selected' => $selected,
            'privileges' => explode(',', $subscriber['privileges']),
            'msg' => $subscriber,
            'title' => "编辑订阅者 <em>( 当前ID:{$subscriber['subscriber_id']} )</em>",
            'button' => '修改',
            'msgList' => $msgList,
        );
        $tpl->assign($params);
        $tpl->display('Event/EditSubscriber');
    }

    public function del() {
        $id = $this->requestParams->getGetInt('subscriber_id');
        try {
            $this->model()->deleteByPrimaryKey($id);
            $_SESSION['message']['alert alert-info'][] = "删除成功!!";
            \App\Admin\Model\Event\Subscription::instance()->delete(array('subscriber_id' => $id));
            //log
            $user = \Lib\User::current();
            $username = $user->info->name;
            $args = var_export(array('id' => $id), true);
            \Lib\Log::instance('admin')->log("[$username] [删除订阅者] [$args]\r\n");

        } catch (\Exception $e) {
            $_SESSION['message']['alert alert-error'][] = "删除失败!!";
            $_SESSION['message']['alert alert-error'][] = $e->getMessage();
        }
        header("Location:/Event/Subscriber");
    }

    public function add() {
        if ($this->requestParams->getPost('subscriber_name')) {
            $fields = array_intersect_key($_POST, array_flip($this->fieldList));
            $class_send = &$fields['allowed_message_class_to_send'];
            $class_send = empty($class_send) ? '' : implode('|', $class_send);
            $fields['privileges'] = empty($fields['privileges']) ? '' : implode(',', $fields['privileges']);
            $fields['register_time'] = time();

            $fields['subscriber_key'] = trim($fields['subscriber_key']);
            $fields['secret_key'] = trim($fields['secret_key']);
            try {
                if (empty($fields['subscriber_key'])) {
                    throw new \Exception('订阅者key 不能为空');
                }

                if (empty($fields['secret_key'])) {
                    throw new \Exception('订阅者私钥 不能为空');
                }

                $this->model()->insert($fields);
                $_SESSION['message']['alert alert-info'][] = "添加成功!!";

                //log
                $user = \Lib\User::current();
                $username = $user->info->name;
                $args = var_export($fields, true);
                \Lib\Log::instance('admin')->log("[$username] [添加订阅者] [$args]\r\n");
            } catch (\Exception $e) {
                $_SESSION['message']['alert alert-error'][] = "添加失败!!";
                $_SESSION['message']['alert alert-error'][] = $e->getMessage();
            }
            header("Location:/Event/Subscriber");
            exit;
        }

        $msgList = new \App\Admin\Model\Event\Message();
        $msgList = $msgList->getMsgList(array(), 1, 2000);
        $msgList = $msgList['data'];
        $params = array(
            'msgList' => $msgList,
            'title' => '添加订阅者',
            'button' => '添加',
        );
        $tpl = $this->getTemplate();
        $tpl->assign($params);
        $tpl->display("Event/AddSubscriber");
    }
}

