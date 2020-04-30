<?php require __DIR__.DS.'../header.php';?>
<?php require __DIR__.DS.'../Blocks/menu.php';?>
<?php
    function output($arr, $key) {
        if(isset($arr[$key])) {
            echo $arr[$key];
        }
    }
?>
<h3 class="tabs_involved">消息分类管理(当前查询共 <em><?php echo $count;?></em> 条记录)</h3>
<br/><br/>
<form action="" method="get" id="query_form">
    <div class="search">
        <label>分类key:</label><input type="text" name="class_key" value="<?php output($query,'class_key');?>">
        <label>分类名称:</label><input type="text" name="class_name" value="<?php echo output($query,'class_name');?>">
    </div>
        <a href="/Event/MsgList?op=add" class="btn">添加消息分类</a>
        <input type="submit" value="查询" class="btn">
    <?php require __DIR__.DS.'../Blocks/pagination.default.php';?>
    <table class="table table-bordered table-fixed tb_short_str">
        <thead>
            <tr>
                <th>分类ID</th>
                <th>分类名称</th>
                <th>分类键</th>
                <th>备注</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php if(count($list)>0):
            foreach($list as $log):?>
                <tr>
                    <td><?php echo $log['class_id'];?></td>
                    <td><?php echo $log['class_name'];?></td>
                    <td><?php echo $log['class_key'];?></td>
                    <td><?php echo $log['comment'];?></td>
                    <td><?php echo $log['create_time'] > 0 ? date('Y-m-d H:i:s', $log['create_time']) : '未记录';?></td>
                    <td>
                        <a href="/Event/MsgList?op=edit&class_id=<?php echo $log['class_id'];?>" class="btn">修改</a>
                        <a href="javascript:void(0);" class="btn delete-btn" url="/Event/MsgList?op=del&class_id=<?php echo $log['class_id'];?>">删除</a>
                    </td>
                </tr>
            <?php  endforeach;
        else:
            ?>
            <tr>
                <td colspan="6">
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
