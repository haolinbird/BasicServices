<?php require __DIR__.DS.'../header.php';?>
<?php require __DIR__.DS.'../Blocks/menu.php';?>

<h3 class="tabs_involved"><?php echo $title;?></h3>
<br/>
<form action="" method="post">
    <dl class="dl-horizontal">
        <dt>订阅者名称：</dt>
        <dd><input type="text" name="subscriber_name"></dd>
        <dt>订阅者key：</dt>
        <dd><input type="text" name="subscriber_key"></dd>
        <dt>订阅者私钥：</dt>
        <dd><input type="text" name="secret_key"></dd>
        <dt>订阅者状态：</dt>
        <dd class="controls">
            <input class="radio" type="radio" name="status" value="0" checked>正常
            <input class="radio" type="radio" name="status" value="1">已注销
        </dd>
       <dt>权限选项：</dt>
        <dd class="controls">
            <input class="checkbox" type="checkbox" name="privileges[]" value="create_message_class">允许自己创建消息类型
            <input class="checkbox" type="checkbox" name="privileges[]" value="self_enable_send_message">允许自己设置可发消息
            <input class="checkbox" type="checkbox" name="privileges[]" value="self_make_subscription">允许自己订阅消息
        </dd>
        <dt>允许发送的消息类型：</dt>
        <dd style="border:1px solid gray; padding-left: 20px;">
            <ol>
            <?php foreach ($msgList as $class_msg):?>
                <li>
                    <input style="float:left;" type="checkbox" name="allowed_message_class_to_send[]" id=<?php
                        echo "\"{$class_msg['class_key']}\" ";
                        echo " value=\"{$class_msg['class_key']}\"";
                        ?> >
                    <label for="<?php echo $class_msg['class_key'];?>"><?php echo "{$class_msg['class_name']}({$class_msg['class_key']})"; ?></label>
                </li>
            <?php endforeach; ?>
            </ol>
        </dd>
        <dt>备注：</dt>
        <dd><textarea name="comment"></textarea></dd>
    <dl>
    <div>
        <input type="submit" value="增加" class="btn">
    </div>
</form>
<?php require __DIR__ . DS . '../footer.php'; ?>
