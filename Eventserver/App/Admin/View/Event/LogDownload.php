<?php require __DIR__.DS.'../header.php';?>
<?php require __DIR__.DS.'../Blocks/menu.php';?>
<h3>系统日志下载</h3>
<ul>
    <?php foreach($logs['file'] as $log):?>
    <li><a href="/Event/LogDownload/?log=<?php echo base64_encode($log)?>"><?php echo basename($log);?></a></li>
    <?php endforeach;?>
</ul>
<?php require __DIR__.DS.'../Blocks/pagination.default.php';?>
<?php require __DIR__ . DS . '../footer.php'; ?>
