<?php require __DIR__.DS.'../header.php';?>
<?php require __DIR__.DS.'../Blocks/menu.php';?>
<h3 class="tabs_involved">默认报警接收人设置(针对所有未设置报警的订阅)</h3>
<form method="post" id="setting">
    <div>
        <p style="float: left;width: 80px;text-align: right;margin-top: 3px;">Auth帐号:</p><input type="text" name="auth" value="<?php if($data){echo implode(',', $data['account']);} ?>" placeholder="多个auth帐号以英文逗号分隔" />
    </div>
    <div>
        <p style="float: left;width: 80px;text-align: right;margin-top: 3px;">&nbsp;</p><table><tr>
            <?php
                foreach ($message_item as $item) {
                    ?><td><em><input type="checkbox" name="message_type[]" value="<?php echo $item['value'] ?>" <?php
                    if ($data && in_array($item['value'], $data['message_type'])) {
                        echo 'checked="checked"';
                    }
                    ?> /><?php echo $item['label']; ?></em></td><?php
                }
            ?></tr></table>
    </div>
    <p>&nbsp;</p>
    <div>
        <input type="submit" value="更新" style="margin-left: 80px;">
    </div>
</form>
<?php require __DIR__ . DS . '../footer.php'; ?>
