<?php require __DIR__.DS.'../header.php';?>
<?php require __DIR__.DS.'../Blocks/menu.php';?>
<script src="/js/bootstrap-datetimepicker.min.js" type="text/javascript"></script>
<script src="/js/bootstrap-datetimepicker.zh-CN.js" type="text/javascript"></script>
<link rel="stylesheet"  href="/css/bootstrap-datetimepicker.min.css" type="text/css" />
<style type="text/css">
    input[type="checkbox"], input[type="radio"]{margin: 0!important}
</style>
<script type="text/javascript">
$(function(){
    $("#datetime_start").datetimepicker({format: 'yyyy-mm-dd hh:ii:ss', showMeridian: true, language:'zh-CN', autoclose: true, todayBtn: true});
    $("#datetime_end").datetimepicker({format: 'yyyy-mm-dd hh:ii:ss', language:'zh-CN', autoclose: true, todayBtn: true});
    $('#tip-restore-to-queue').tooltip({html: true, title: "将已经不在消息队列中,并且最终推送失败的消息重新恢复至队列中."});
    $('#tip-force-restore-to-queue').tooltip({html: true, title: "有些消息可能还在消息队列中，如果您能确认队列中已无相应的消息，或者消息Worker有完善的消息去重机制，则可进行此操作！"});
    $('#checkall').click(function(){
        var checked = this.checked;
        $('input[name^=log_id]').each(function(){
                if($(this).attr('can-be-restored') || $('#tip-force-restore-to-queue').attr('checked')){
                    this.checked=checked;
                }
                else{
                    this.checked = false;
                }
            }
        );
    });

    function refine_log_id_fields()
    {
        var ids = [];
        $('input[name^=log_id]:checked').each(function(){ids.push(this.value)});
        $('input[name^=log_id]').attr('disabled', true);
        $('<input type="hidden" name="log_id" />').val(ids.join(',')).appendTo('#form-restore-job');

    }
    $('#btn-restore-to-queue').click(function(){
        var confirmed = false;
        if($('#tip-force-restore-to-queue').attr('checked')){
            confirmed = confirm('确认强制恢复至队列中?！\n此操作可能造成不正常的重复推送，\n您确定需要进行此操作码？');
        } else {
            confirmed = confirm('确认恢复至队列中?！');
        }
        if(!confirmed){
            return false;
        }
        var logs = [];
        $('input[name^=log_id]').each(function(){
            if(this.checked && ($('#tip-force-restore-to-queue').attr('checked') ||  $(this).attr('can-be-restored')))
            {
                logs.push(this.value);
            }
            else
            {
                this.checked = false;
            }
         }
        );
        if(logs.length < 1)
        {
            alert('请选择需要恢复的推送！');
            return false;
        }

        if(!$('#dest_host').val()){
            alert('请选择队列服务器！');
            return false;
        } else{
            $('input[name=dest_host]').val($('#dest_host').val().join(','));
        }

        refine_log_id_fields();
        $('#form-restore-job').submit();
    });

    $('#dest_host').css('height', $('#dest_host')[0].scrollHeight);
});
</script>
<div id="main">
    <div id="page-content">
<h3 id="page-header">系统信息&gt;消息推送失败日志</h3>
<form id="query_form" action="?#restore-operations" method="get" class="form-horizontal themed">
<div class="fluid-container">
    <section>
        <div class="row-fluid">
            <article class="span12 sortable-grid ui-sortable">
                <div class="jarviswidget jarviswidget-sortable">
                    <header>
                        <h2>查询</h2>
                    </header>
                    <div class="inner-spacer">
                        <div class="control-group">
                            <label class="control-label">订阅者key</label>
                            <div class="controls">
                                <input class="span2 search-query" type="text" name="subscriber_key" value="<?php echo isset($query['subscriber_key'])? $query['subscriber_key'] : ''; ?>"/>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">消息类型key</label>
                            <div class="controls">
                                <input class="span2 search-query" type="text" name="class_key" value="<?php echo isset($query['class_key'])? $query['class_key'] : ''; ?>" />
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">重推状态</label>
                            <div class="controls">
                                <label class="radio inline">
                                    <input type="radio" class="radio" name="alive" value="1" <?php if(isset($query['alive']) && $query['alive'] === 1)echo 'checked';?>/> 队列中
                                </label>
                                <label class="radio inline">
                                    <input type="radio" class="radio" name="alive" value="0" <?php if(isset($query['alive']) && $query['alive'] == 0)echo 'checked';?> /> 已停止
                                </label>
                                <label class="radio inline">
                                    <input type="radio" class="radio" name="alive" value="*" <?php if(!isset($query['alive']) || !in_array($query['alive'], array("1", "0"))) echo 'checked';?>/> 所有
                                </label>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">重推次数</label>
                            <div class="controls">
                            <input type="number" name="retry_times" class="span1 search-query" <?php if(isset($query['retry_times'])) echo 'value="'.$query['retry_times'].'"'; ?>/>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">最后一次推送结果</label>
                            <div class="controls">
                                <label class="radio inline">
                                    <input type="radio" class="radio" name="final_status" value="1" <?php if(isset($query['final_status']) && $query['final_status']) echo 'checked';?> /> 成功
                                </label>
                                <label class="radio inline">
                                    <input type="radio" class="radio" name="final_status" value="0" <?php if(isset($query['final_status']) && !$query['final_status'])  echo 'checked';?> /> 失败
                                </label>
                                <label class="radio inline">
                                    <input type="radio" class="radio" name="final_status" value="*"   <?php if(!isset($query['final_status']) || !in_array($query['final_status'], array("1", "0"))) echo 'checked';?>/> 所有
                                </label>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">首次推送时间</label>
                            <div class="controls">
                                <div id="datetime_start" class="input-append date form_datetime">
                                    <input size="16" type="text" name="first_push_time_start"  readonly <?php if($query['first_push_time_start']){$datetimeStart = date('Y-m-d H:i:s', $query['first_push_time_start']); echo 'value="'.$datetimeStart.'"'.' data-date="'.$datetimeStart.'"';}?>>
                                    <span class="add-on"><i class="icon-remove"></i></span>
                                    <span class="add-on"><i class="icon-calendar"></i></span>
                                </div>
                                -
                                <div id="datetime_end" class="input-append date form_datetime">
                                    <input size="16" type="text" name="first_push_time_end" readonly <?php if($query['first_push_time_end']){$datetimeEnd = date('Y-m-d H:i:s', $query['first_push_time_end']); echo 'value="'.$datetimeEnd.'"'.' data-date="'.$datetimeEnd.'"';}?>>
                                    <span class="add-on"><i class="icon-remove"></i></span>
                                    <span class="add-on"><i class="icon-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label"></label>
                            <div class="controls">
                                <input type="submit" value="查询" class="btn btn-primary"/>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </section>
</div>
<input type="hidden" name="page_no" value="<?php echo $paging->page_no; ?>"/>
    <input type="hidden" name="rows_per_page" value="<?php echo $paging->rows_per_page; ?>"/>
</form>
<div class="fluid-container form-horizontal themed" id="restore-operations" >
    <section>
        <div class="row-fluid">
           <article class="span12 sortable-grid ui-sortable">
                <div class="jarviswidget jarviswidget-sortable">
                    <header>
                        <h2>消息恢复</h2>
                    </header>
                    <div class="inner-spacer">
                        <div class="control-group">
                            <label class="control-label">队列服务器</label>
                            <div class="controls">
                                    <select id="dest_host" multiple style="max-height: 320px;">
                                        <?php
                                        foreach($hosts as $gk => $gHosts){
                                            echo '<option value="" style="font-weight: bold;" disabled>'.$gk.'</option>';
                                            foreach($gHosts as $cHost){
                                                $hostStr = $cHost['host'].':'.$cHost['port'];
                                                echo '<option style="padding-left: 20px;" value="'.$hostStr.'" '.(!empty($host) && $host == $hostStr ? 'selected' : '').'>'.$hostStr.'</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label"></label>
                            <div class="controls">
                                <label class="radio inline">
                                <input type="radio" name="restore_method" value="normal" id="tip-restore-to-queue" checked  title="将已经不在消息队列中,并且最终推送失败的消息重新恢复至队列中." /> 普通恢复
                                </label>
                                <label class="radio inline">
                                <input type="radio" name="restore_method" value="force" id="tip-force-restore-to-queue"  title="有些消息可能还在消息队列中，如果您能确认队列中已无相应的消息，或者消息Worker有完善的消息去重机制，则可进行此操作！" />
                                    <span class="text-error">强制恢复<span>
                                </label>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label"></label>
                            <div class="controls">
                                <label class="checkbox">
                                    <input id="checkall" type="checkbox" /> 全部
                                </label>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label"></label>
                            <div class="controls">
                                <button type="button" class="btn btn-danger" id="btn-restore-to-queue" title="">恢复</button>
                            </div>
                        </div>
                    </div>
                </div>
           </article>
        </div>
    </section>
</div>

<div class="fluid-container">
    <div class="row-fluid">
        <div class="pull-right">
            <?php require __DIR__.DS.'../Blocks/pagination.default.php';?>
        </div>
    </div>
<section>
    <div class="row-fluid">
        <article class="span12 sortable-grid ui-sortable">
            <div class="jarviswidget jarviswidget-sortable">
                <header>
                    <h2>日志列表</h2>
                </header>
                <div class="inner-spacer">
                    <form method="post" id="form-restore-job" action="/Event/RestoreMessage">
                        <input type="hidden" name="type" />
                        <input type="hidden" name="dest_host" />
                    <table class="table table-bordered table-fixed tb_short_str">
                        <thead>
                            <tr>
                                <th>日志ID</th>
                                <th>订阅者</th>
                                <th>消息分类</th>
                                <th>消息提交时间</th>
                                <th>首次推送时间</th>
                                <th>重推次数</th>
                                <th>上次重推时间</th>
                                <th>最后一次推送状态</th>
                                <th>已停止推送</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(count($list)>0):
                            foreach($list as $log):
                                $canbeRestored = \App\Admin\Model\Event\Log::instance()->canbeRestored($log);
                        ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="log_id[]" value="<?php echo $log['log_id'];?>" can-be-restored="<?php echo $canbeRestored;?>" />
                                        <a title="查看日志详细内容" href="/Event/LogDetail?log_id=<?php echo $log['log_id']; ?>"><?php echo $log['log_id']; ?></a>
                                    </td>
                                    <td><?php echo $log['subscriber_name']."  ({$log['subscriber_key']})"; ?></td>
                                    <td><?php echo $log['class_name']."  ({$log['class_key']})"; ?></td>
                                    <td>
                                        <?php echo $log['message_time'] > 0 ? date('Y-m-d H:i:s', $log['message_time']) : '无记录';?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i:s', $log['time']); ?></td>
                                    <td><?php echo $log['retry_times']; ?></td>
                                    <td><?php echo $log['last_retry_time'] > 0 ? date('Y-m-d H:i:s', $log['last_retry_time']) : '无'; ?></td>
                                    <td><?php echo $log['final_status'] == 1 ? '接收成功' : '接收失败'; ?></td>
                                    <?php if(!$log['alive']):?>
                                    <td class="badge-warning" title="点击“恢复”操作可将消息送入重试队列。">是<span class="icon-info-sign" ></span></td>
                                    <?php     else:?>
                                    <td>否</td>
                                    <?php endif;?>
                                </tr>
                            <?php  endforeach;
                        else:
                            ?>
                            <tr>
                                <td colspan="22">
                                    <div class="alert alert-info" onclick="hide(this)">
                                        没有记录
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    </form>
                </div>
            </div>
        </article>
    </div>
</section>
    <div class="row-fluid">
        <div class="pull-right">
            <?php require __DIR__.DS.'../Blocks/pagination.default.php';?>
        </div>
    </div>
</div>
    </div>
</div>
<?php require __DIR__ . DS . '../footer.php'; ?>