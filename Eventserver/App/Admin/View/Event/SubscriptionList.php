<?php require __DIR__.DS.'../header.php';?>
<?php require __DIR__.DS.'../Blocks/menu.php';?>
<h3>订阅管理列表</h3>
<form id="query_form" action="" method="get">
订阅者:
    <select name="subscriber_key" style="width: auto;">
        <option value="">-全部-</option>
        <?php foreach($subscribers as $subcriber):?>
            <option <?php if($subcriber['subscriber_key'] == $subscriber_key):?>selected="true"<?php endif;?>
                value="<?php echo $subcriber['subscriber_key'];?>"><?php echo "{$subcriber['subscriber_key']}({$subcriber['subscriber_name']})";?></option>
        <?php endforeach;?>
    </select>
消息分类:
    <select name="class_key" style="width:auto;">
        <option value="">-全部-</option>
        <?php foreach($message_classes as $sub):?>
            <option <?php if($sub['class_key'] == $class_key):?>selected="true"<?php endif;?>
                    value="<?php echo $sub['class_key'];?>"><?php echo "{$sub['class_key']}({$sub['class_name']})";?></option>
        <?php endforeach;?>
    </select>
   </br>
<a href="/Event/SubscriptionAdd" class="btn">添加订阅</a>
<input type="submit" value="查询" class="btn"/>
<input type="hidden" name="page_no" value="<?php echo $query['page_no']; ?>"/>
</form>
<?php if(count($list)>0): ?>
<?php require __DIR__.DS.'../Blocks/pagination.default.php';?>

<table class="table table-bordered table-fixed tb_short_str">
    <thead>
        <tr>
            <th style="min-width:50px;">订阅ID</th>
            <th>订阅者</th>
            <th>消息分类</th>
            <th>接收消息的地址</th>
            <th style="min-width:40px">状态</th>
            <th style="min-width:60px">超时时间</th>
            <th style="min-width:150px">创建时间</th>
            <th style="min-width:120px">操作</th>
        </tr>
    </thead>
    <tbody>
    <?php 
        foreach($list as $subscription):?>
            <tr>
                <td><?php if(!empty($subscription['subscription_id'])) echo $subscription['subscription_id']?></td>
                <td><?php if(!empty($subscription['subscriber_name'])) echo $subscription['subscriber_name']?>&nbsp;(<?php echo $subscription['subscriber_key']; ?>)</td>
                <td><?php if(!empty($subscription['class_name'])) echo $subscription['class_name']?>&nbsp;(<?php echo $subscription['class_key']; ?>)</td>
                <td><textarea style="border: none;max-height: 120px;"><?php if(!empty($subscription['reception_channel'])) echo $subscription['reception_channel']?></textarea></td>
                <td><?php if(isset($subscription['status']) && $subscription['status']==0)
                			{
                				echo '正常';
                			}else{
                				echo '已经取销';
                			}
                	?></td>
                <td><?php echo (int)$subscription['timeout']?></td>
                <td><?php if(!empty($subscription['subscribe_time'])) echo date('Y-m-d H:i:s',$subscription['subscribe_time']);?></td>
                <td>
                    <a href="javascript:void(0);" data-id="<?php echo $subscription['subscription_id']; ?>" class="subscription-delete-btn btn btn-danger" >删除</a>
                    <a href="/Event/SubscriptionModify?id=<?php echo $subscription['subscription_id']?>" class="btn">修改</a>
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
<?php require __DIR__ . DS . '../footer.php'; ?>
