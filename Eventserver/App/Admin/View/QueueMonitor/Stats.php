<?php require __DIR__.DS.'../header.php';?>
<?php require __DIR__.DS.'../Blocks/menu.php';?>
<div id="rightside">
    <div class="contentcontainer">
        <div class="headings altheading">
            <h3>队列状态</h3>
        </div>
        <div class="contentbox">
            <form id="stats-form">
            <table width="100%" class="table table-hover table-bordered">
                <thead>
                    <tr>
                        <th style="text-align: right"  colspan="2">队列服务器列表</th>
                        <th style="text-align: left" colspan="2">
                            <select name="host[]" id="queue-server-selects" multiple style="margin-bottom: 0">
                                <?php
                                foreach($hosts as $gk => $gHosts){
                                    $selected = in_array($gk, $hostGroup)? 'selected': '';
                                    echo '<option value="group-'.$gk.'" '.$selected.' class="queue-server-select-group">'.$gk.'</option>';
                                    foreach($gHosts as $cHost){
                                        $hostStr = $cHost['host'].':'.$cHost['port'];
                                        echo '<option data-queue-server-group="'.$gk.'" style="padding-left: 20px;" value="'.$hostStr.'" '.(!empty($host) && in_array($hostStr, $host) ? 'selected' : '').'>'.$hostStr.'</option>';
                                    }
                                }
                                ?>
                            </select>
                        </th>
                        <th style="min-width: 50px;">订阅者</th>
                        <th colspan="2">
                            <select name="subscriber_key" style="margin-bottom: 0">
                                <option value="">ALL</option>
                                <?php foreach($subscribers as $subscriber){
                                        echo '<option value="'.$subscriber['subscriber_key'].'" '.(isset($subscriberKey) && $subscriberKey == $subscriber['subscriber_key'] ? 'selected' : '').'>'.$subscriber['subscriber_key'].'('.$subscriber['subscriber_name'].')</option>';
                                    }
                                ?>
                            </select>
                        </th>
                        <th style="text-align: right; width: 60px;"  >消息类型</th>
                        <th colspan="<?php echo count($fields)-10;?>">
                            <select name="messageClass" style="margin-bottom: 0">
                                <option value="">ALL</option>
                                <option value="#main" <?php if(isset($messageClass) && $messageClass == '#main')echo 'selected';?>>#main</option>
                                <?php
                                foreach($messageClasses as $msg){
                                    echo '<option '.((isset($messageClass) && $messageClass == $msg['class_key']) ? 'selected':'').' value="'.$msg['class_key'].'">'.$msg['class_key'].'('.$msg['class_name'].')</option>';
                                }
                                ?>
                            </select>
                        </th>

                    </tr>
                    <tr>
                        <td colspan="<?php echo count($fields)+1;?>" style="text-align: center">
                            <button type="submit" class="btn btn-primary">查询</button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="col" rowspan="2">操作</th><th style="text-align: center" colspan="<?php echo count($fields);?>">状态统计</th>
                    </tr>
                    <tr>
                        <?php                        
                        foreach ($fields as $field) echo "<th scope=\"col\">{$field}</th>\n";
                        ?>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $totals = array();
                $queueList = array();
                foreach($tubesStats as $num=>$status):?>
                <tr><th colspan="<?php echo count($fields)+1;?>">Server: <a href="javascript:void(0);" onclick="$('#stats_container_<?php echo $num;?>').toggle();"><?php $conn = $bsPool[$num]->getConnection(); echo $conn->getHost().':'.$conn->getPort();?></a></th></tr>
                <tr id="stats_container_<?php echo $num;?>" style="display:none">
                    <td colspan="<?php echo count($fields)+1;?>">
                        <table width="100%">
                        <?php $itemsRow = 3;
                              $itemCount = 0;
                         foreach($stats = $bsPool[$num]->stats() as $k => $v): 
                             if($itemCount%$itemsRow == 0):
                                 echo '<tr>';
                              endif;
                         ?>
                        <th><?php echo $k;?></th>
                        <?php 
                        if($itemCount == count($stats)-1 && ($itemCount+1)%$itemsRow != 0)
                        {
                            $colspan = ($itemsRow - ($itemCount%$itemsRow))*2-1;
                        }
                        else
                        {
                            $colspan = 0;
                        }
                        ?>
                        <td colspan="<?php echo $colspan?>">
                            <?php echo $v;?>
                        </td>
                        <?php 
                        if(is_int(($itemCount+1)/$itemsRow)|| $itemCount == count($stats)-1):
                        echo '</tr>'."\n";
                        endif;
                        $itemCount++;
                        endforeach;?>
                        </table>
                    </td>
                </tr>
                    <?php
                    $data = array();
                    $i = 1;

                    foreach ($status as $name => $stat) {
                        if(isset($messageClass)){
                            if($messageClass == '#main'){
                                if($stat[\Lib\BeansTalkMonitor::NAME] != \App\Server\Cfg\Service::TUBE_EVENT_CENTER_MESSAGES){
                                    continue;
                                }
                            }else if( strpos($stat[\Lib\BeansTalkMonitor::NAME], $messageClass.'/') !== 0){
                                continue;
                            }
                        }
                        if(!is_null($subscriptionIds)){
                            $belongsToSubscriber = false;
                            foreach($subscriptionIds as $id){
                                if(preg_match('#/'.$id.'(/FAIL)?$#', $stat[\Lib\BeansTalkMonitor::NAME])){
                                    $belongsToSubscriber = true;
                                }
                            }
                            if(!$belongsToSubscriber){
                                continue;
                            }
                        }

                        $value = $conn->getHost() . ':' . $conn->getPort() . ':' . $stat[\Lib\BeansTalkMonitor::NAME];
                        $queueList[$value] = 'E' . md5($value);

                        $trCls = $i % 2 == 0 ? ' class="alt"' : null;
                        $data[] = "<tr{$trCls}>";
                        $data[] = "<td><a href=\"/QueueMonitor/Tube?name={$stat[\Lib\BeansTalkMonitor::NAME]}&server_index={$num}\">查看</a> <a id=\"" . $queueList[$value] . "\" href=\"javascript:void(cleanQueue('" . $stat[\Lib\BeansTalkMonitor::NAME] . "','" . $conn->getHost() . "','" . $conn->getPort() ."'))\">清除队列</a></td>";
                        foreach ($fields as $key => $val): 
                            // $totals[$key] = isset($totals[$key]) ? $totals[$key]+(isset($stat[$key])?$stat[$key] : 0) : $stat[$key];
                            $totals[$key] = isset($totals[$key]) ? $totals[$key]+(isset($stat[$key])?$stat[$key] : 0) : (isset($stat[$key])?$stat[$key] : 0);
                            $data[] = "<td class=\"tube-status-{$key}\" title=\"{$key}\">".(isset($stat[$key]) ? $stat[$key] : 0)."</td>";
                        endforeach;
                        $data[] = '</tr>';
                        $i++;
                    }
                    echo implode("\n", $data);
                    ?>
                <?php endforeach;?>
                <tr>
                    <th colspan="2" style="background-color:#ccc;">Total</th>
                    <?php 
                        array_shift($totals);
                        foreach($totals as $v):
                        echo '<td style="background-color:#eee;">'.$v.'</td>';
                          endforeach;;
                    ?>
                </tr>
                </tbody>
            </table>
            </form>
        </div>
    </div>
</div>
<script type="text/javascript">
    var hostSel = $('#queue-server-selects');
    hostSel.height(hostSel[0].scrollHeight);
    $('option[value^="group-"]').click(function(){
        if(this.selected){
            $('option[data-queue-server-group="'+this.value.split("-")[1]+'"]').attr('selected', true);
        } else{
            $('option[data-queue-server-group="'+this.value.split("-")[1]+'"]').removeAttr('selected');
        }
    });

    var queueList = JSON.parse('<?php echo json_encode($queueList);?>');
    function updateCleanProcess() {
        $.post('/Queue/Stats', {queue: '<?php echo json_encode(array_keys($queueList));?>'}, function(response) {
            var result = JSON.parse(response);
            for (var k in result) {
                if (result.hasOwnProperty(k)) {
                    if (result[k] > 0) {
                        // $('#' + queueList[k]).text('正在清理,当前剩余' + result[k]);
                        $('#' + queueList[k]).text('正在清理');
                    } else {
                        if ($.trim($('#' + queueList[k]).text()) == '清除完成') {
                            $('#' + queueList[k]).text('清除队列');
                        } else {
                            if ($.trim($('#' + queueList[k]).text()) != '清除队列') {
                                $('#' + queueList[k]).text('清除完成');
                            }
                        }
                    }
                }
            }
        });

        setTimeout(updateCleanProcess, 5000);
    }

    updateCleanProcess();

    function cleanQueue(queue, host, port) {
        var key = host + ':' + port + ':' + queue;
        if ($.trim($('#' + queueList[key]).text()) != '清除队列') {
            return;
        }
        console.debug(queueList);

        var hosts = [];
        var group = $('option[value="' + host + ':' + port + '"]').attr('data-queue-server-group');
        $('option[data-queue-server-group="' + group + '"').each(function() {
            hosts.push($(this).val() + ':' + queue);
        });

        if (confirm('确实要在' + group + '环境的所有服务器上清空' + queue + '队列吗?')) {
            var slice = null;
            for(var i = 0; i < hosts.length; i++) {
                if ($('#' + queueList[hosts[i]]).length > 0) {
                    $('#' + queueList[hosts[i]]).text('正在清理');
                }
                
                slice = hosts[i].split(':');
                $.post('/Queue/Clear', {queue: queue, host: slice[0], port: slice[1]}, function() {});
            }
        }
    }
</script>
<?php require __DIR__ . DS . '../footer.php'; ?>