<?php require __DIR__.DS.'../header.php';?>
<?php require __DIR__.DS.'../Blocks/menu.php';?>
<?php
    function output($arr, $key) {
        if(isset($arr[$key])) {
            echo $arr[$key];
        }
    }
?>
<h3 class="tabs_involved">订阅者管理(当前查询共 <em><?php echo $count;?></em> 条记录)</h3>
<br/><br/>
<form action="" method="get" id="query_form">
    <div class="search"><p>订阅者key:</p><input type="text" name="subscriber_key" value="<?php output($query,'subscriber_key');?>"></div>
    <div class="search"><p>订阅者名称:</p><input type="text" name="subscriber_name" value="<?php echo output($query,'subscriber_name');?>"></div>
    <a href="/Event/Subscriber?op=add" class="btn">添加订阅者</a>
    <input type="submit" value="查询" class="btn">
    <?php require __DIR__.DS.'../Blocks/pagination.default.php';?>
    <table class="table table-bordered table-fixed tb_short_str">
        <thead>
            <tr>
                <th>订阅者ID</th>
                <th>订阅者名称</th>
                <th>订阅者键</th>
                <th>订阅者私钥</th>
                <th>备注</th>
                <th>创建时间</th>
                <th>状态</th>
                <th>允许发送的消息类型</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php if(count($list)>0):
            foreach($list as $log):?>
                <tr>
                    <td><?php echo $log['subscriber_id'];?></td>
                    <td><?php echo $log['subscriber_name'];?></td>
                    <td><?php echo $log['subscriber_key'];?></td>
                    <td><?php echo $log['secret_key'];?></td>
                    <td><?php echo $log['comment'];?></td>
                    <td><?php echo $log['register_time'] > 0 ?  date('Y-m-d', $log['register_time']) : '未记录';?></td>
                    <td><?php echo !$log['status'] ? '正常' : '已注销';?></td>
                    <td style="width:30%; word-break:hyphenate;"><?php echo str_replace('|', ', ', $log['allowed_message_class_to_send']);?></td>
                    <td>
                        <a href="/Event/Subscriber?op=edit&subscriber_id=<?php echo $log['subscriber_id'];?>" class="btn">修改</a>
                        <a href="javascript:void(0);" class="btn delete-btn" url="/Event/Subscriber?op=del&subscriber_id=<?php echo $log['subscriber_id'];?>">删除</a>
                    </td>
                </tr>
            <?php  endforeach;
        else:
            ?>
            <tr>
                <td colspan="20">
                    <div class="alert alert-info" onclick="hide(this)">
                        没有记录
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php require __DIR__.DS.'../Blocks/pagination.default.php';?>
</form>
<?php require __DIR__ . DS . '../footer.php'; ?>
