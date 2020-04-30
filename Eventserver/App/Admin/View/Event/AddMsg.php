<?php require __DIR__.DS.'../header.php';?>
<?php require __DIR__.DS.'../Blocks/menu.php';?>

<h3 class="tabs_involved"><?php echo $title;?></h3>
<br/>
<form action="" method="post">
    <div>
        <p style="float: left;width: 80px;text-align: right;margin-top: 3px;">分类名称：</p><input type="text" value="" name="class_name">
    </div>
    <div>
        <p style="float: left;width: 80px;text-align: right;margin-top: 3px;">分类key：</p><input type="text" value="" name="class_key">
    </div>
    <div>
        <p style="float: left;width: 80px;text-align: right;margin-top: 3px;">备注：</p><input type="text" value="" name="comment">
    </div>
    <div>
        <input type="submit" value="新增" style="margin-left: 80px;">
    </div>
</form>
<?php require __DIR__ . DS . '../footer.php'; ?>
