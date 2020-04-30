<?php require __DIR__.DS.'../header.php';?>
<?php require __DIR__.DS.'../Blocks/menu.php';?>
<h3>Redis信息统计</h3>
<table>
    <?php foreach($info as $item => $value):?>
    <tr>
        <th align="right"><?php echo $item;?></th>
        <td>&nbsp;&nbsp;<?php echo $value;?></td>
    </tr>
    <?php endforeach;?>
</table>
<?php require __DIR__.DS.'../Blocks/pagination.default.php';?>
<?php require __DIR__ . DS . '../footer.php'; ?>
