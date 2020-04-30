<?php require __DIR__.DS.'../header.php';?>
<?php require __DIR__.DS.'../Blocks/menu.php';?>
<h3>失败日志详情</h3>
<table class="event-log-list">
     <tr>
        <td>日志ID：</td>
        <td><?php if(!empty($info['log_id'])) echo $info['log_id'];?></td>
    </tr>
    <tr>
        <td>订阅ID：</td>
        <td>
            <?php if(!empty($info['subscription_id'])):?>
            <a href="/Event/SubscriptionModify?id=<?php echo $info['subscription_id']; ?>"><?php echo $info['subscription_id']; ?></a>
            <?php endif; ?>
        </td>
    </tr>
     <tr>
        <td>Job ID：</td>
        <td><?php if(!empty($info['job_id'])) echo $info['job_id'];?></td>
    </tr>
     <tr>
        <td>订阅者名称：</td>
        <td><?php if(!empty($info['subscriber_name'])) echo $info['subscriber_name'];?></td>
    </tr>

    <td>订阅者Key：</td>
    <td><?php if(!empty($info['subscriber_key'])) echo $info['subscriber_key'];?></td>
    </tr>
    <tr>
        <td>推送地址:</td>
        <td><?php if(!empty($info['last_target'])) echo $info['last_target'];?></td>
    </tr>
    <tr>
        <td>推送内容:</td>
        <td><pre><?php if(!empty($info['message_body']))  echo substr_replace(highlight_string('<?php '.var_export(json_decode($info['message_body']),true),true),'',0,73);?></pre></td>
    </tr>
     <tr>
        <td>消息类型名称：</td>
        <td><?php if(!empty($info['class_name'])) echo $info['class_name'];?></td>
    </tr>
    <tr>
        <td>消息类型key：</td>
        <td><?php if(!empty($info['class_key'])) echo $info['class_key'];?></td>
    </tr>
     <tr>
        <td>首次发送时间：</td>
        <td><?php if(!empty($info['time']))  echo date('Y-m-d H:i:s',$info['time']);?></td>
    </tr>
    <tr>
        <td>重发次数：</td>
        <td><?php if(!empty($info['retry_times'])) echo (int) $info['retry_times'];?></td>
    </tr>
    <tr>
        <td>上次重发时间：</td>
        <td><?php if(!empty($info['last_retry_time'])) echo date('Y-m-d H:i:s',$info['last_retry_time']);?></td>
    </tr>
    <tr>
        <td>最终发送状态：</td>
        <td><?php if(isset($info['final_status']) && $info['final_status']==1)
                        {
                            echo '接收成功';
                        }else{
                            echo '接收失败';
                        }
                ?></td>
    </tr>
    <tr>
        <td>第一次接收服务的错误消息：</td>
        <td><?php if(!empty($info['first_failure_message'])) echo htmlspecialchars($info['first_failure_message']);?></td>
    </tr>
    <tr>
        <td>最后一次接收服务的错误消息：</td>
        <td><?php if(!empty($info['last_failure_message'])) echo htmlspecialchars($info['last_failure_message']);?></td>
    </tr>
</table>
<?php require __DIR__.DS.'../Blocks/pagination.default.php';?>
<?php require __DIR__ . DS . '../footer.php'; ?>
