<?php require __DIR__.DS.'../header.php';?>
<?php require __DIR__.DS.'../Blocks/menu.php';?>
<?php use Lib\Util\Broadcast as util;
    $redis = \Lib\Redis::instance();
?>
<h3>消息队列Redis使用情况</h3>
<form>
<input name="action" type="hidden" value="peek_success"></input>
<table>
    <tr>
        <th align="left" colspan="2">正常队列-已经成功推入beanstalkd队列</th>
    </tr>
    <?php if(!count($successLogKeys)):?>
    <tr><th colspan="2" align="center">无</th>
    <?php else:
              $total = 0;
              echo '<td><ol>';
              foreach($successLogKeys as $hostKeys):
                 if(count($hostKeys) < 1) continue;
                 foreach($hostKeys as $key):
                 $length = $redis->lLen($key);
                 $total += $length;
                 $checked = ($currentKey == $key) ? 'checked' : '';
                 echo '<li><input name="key" '.$checked.' type="radio" value="'.$key.'" /> &nbsp;'.$key.'&nbsp; count('.$length.')&nbsp;<a href="?action=del_key&key='.urlencode($key).'" onclick="return confirm(\'确定删除此列表?\');">删除</a>&nbsp;</li>';
                 endforeach;
               endforeach;
              echo '</ol></td>';
    ?>
    <?php endif;?>
    <tr>
        <td colspan="2">
            <strong>历史消息总数:</strong><?php echo (!isset($statics['success_in']) ? 0: $statics['success_in']) ;?>
         &nbsp;<strong>上次更新时间:</strong>
        <?php echo (!isset($statics['last_update']) ? '无': date('Y-m-d H:i:s', $statics['last_update'])) ;?>
        <a class="btn btn-warning btn-small" href="?action=update_statistic">立即更新</a>
        </td>
    </tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr>
        <td colspan="2" align="left">
        范围:<input class="num_range_field" name="index_start" type="number" value="<?php echo is_null($indexStart)? '' : $indexStart;?>"/>
            —
            <input class="num_range_field" name="index_end" type="number" value="<?php echo is_null($indexEnd)? '' : $indexEnd;?>"/>
        <input value="查看" type="submit"/>
        </td>
    </tr>
</table>
</form>
<ol>
    <?php
        foreach($listSuccess as $value):
            echo "<li>".var_export(util::unserialize($value), true)."</li>";
        endforeach;

    ?>
</ol>
<form>
<input name="action" type="hidden" value="peek_failure"></input>
<table>
    <tr>
        <th align="left" colspan="2">失败队列-没有进入beantalkd队列</th>
    </tr>
    <?php if(!count($failureLogKeys)):?>
    <tr><th colspan="2" align="center">无</th>
    <?php else:
              echo '<td><ol>';
              foreach($failureLogKeys as $hostKeys):
                 if(count($hostKeys) < 1) continue;
                 foreach($hostKeys as $key):
                  $checked = ($currentKey == $key) ? 'checked' : '';
                  echo '<li><input name="key" '.$checked.' type="radio" value="'.$key.'" /> &nbsp;'.$key.'&nbsp; count('.$redis->lLen($key).')';
                  if(preg_match('#_recover_lock$#', $key))
                  {
                      echo '<a href="?action=del_recover_lock_key" onclick="return confirm(\'确定删除消息恢复LOCK?\');">删除</a>&nbsp;';
                  }
                  echo '</li>';
                  endforeach;
              endforeach;
              echo '</ol></td>';
    ?>
    <?php endif;?>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr>
        <td colspan="2" align="left">
        范围:<input class="num_range_field" name="index_start" type="number" value="<?php echo is_null($indexStart)? '' : $indexStart;?>"/>
            —
            <input class="num_range_field" name="index_end" type="number" value="<?php echo is_null($indexEnd)? '' : $indexEnd;?>"/>
        <input value="查看" type="submit"/>
        </td>
    </tr>
</table>
</form>
<ol>
    <?php
        foreach($listFailure as $value):
            echo "<li>". var_export(util::unserialize($value), true)."</li>";
        endforeach;

    ?>
</ol>
<?php require __DIR__ . DS . '../footer.php'; ?>
