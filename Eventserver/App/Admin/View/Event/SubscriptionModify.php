<?php require __DIR__.DS.'../header.php';?>
<?php require __DIR__.DS.'../Blocks/menu.php';?>
<script src="/js/jquery-ui.min.js" type="text/javascript"></script>
<script src="/js/subscription_page.js" type="text/javascript"></script>
<script src="/js/jquery.ui.touch-punch.min.js" type="text/javascript"></script>
<script src="/js/mobiledevices.min.js" type="text/javascript"></script>
<?php
    if($subParams['ProcessTimeout'] > 0) {
    $timeout = $subParams['ProcessTimeout'];
} else if(isset($rs['timeout'])) {
    $timeout = $rs['timeout'] * 1000;
} else {
    $timeout =  $defaultParams["DefaultMaxMessageProcessTime"];
}
?>
<div id="main">
<div id="page-content">
    <!-- page header -->
    <h1 id="page-header">订阅管理 &gt; 修改订阅</h1>
    <div class="fluid-container">
        <!-- widget grid -->
        <section id="widget-grid" class="">
            <!-- row-fluid -->
            <div class="row-fluid ">
                <article class="span12 sortable-grid ui-sortable">
                    <div class="jarviswidget jarviswidget-sortable" id="widget-id-0" role="widget" style="">
                        <header role="heading">
                            <h2>ID: <?php echo $rs['subscription_id'];?></h2>
                        </header>
                        <!-- wrap div -->
                            <div class="inner-spacer">
                                <!-- content goes here -->
                                <form  action='' method="post" class="form-horizontal themed">
                                    <fieldset>
                                        <div class="control-group">
                                            <label class="control-label">订阅者</label>
                                            <div class="controls">
                                                <select name="subscriber_id"  style="width: 120px;" class="disabled">
                                                    <?php
                                                    if (!empty($subList)) {
                                                        foreach ($subList as $key => $val) {
                                                            $selected = (bool) $rs && $val['subscriber_id'] == $rs['subscriber_id'] ? ' selected="selected"' : null;
                                                            if(!$selected){
                                                                continue;
                                                            }
                                                            echo "<option value='" . $val['subscriber_id'] . "'{$selected}>" . $val['subscriber_name'] . "</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="control-group">
                                            <label class="control-label">消息分类</label>
                                            <div class="controls">
                                                <?php echo $rs['class_name'], " ({$rs['class_key']})"; ?>
                                            </div>
                                        </div>
                                        <div class="control-group">
                                            <label class="control-label">订阅状态</label>
                                            <div class="controls">
                                                <input type="radio" name="status" value="0" <?php echo $rs['status'] == 0 ? 'checked' : ''; ?> />正常 &nbsp;&nbsp;
                                                <input type="radio" name="status" value="1" <?php echo $rs['status'] == 1 ? 'checked' : ''; ?> /> 已经取消
                                                <p class="help-block">
                                                    (取消订阅后，新进来的消息会被直接丢弃掉；已经在队列中的消息会暂停推送，但不会被丢弃。)
                                                </p>
                                            </div>
                                        </div>
                                        <div class="control-group">
                                            <label class="control-label">消息处理地址</label>
                                            <div class="controls">
                                                <textarea name="reception_channel" placeholder="地址为url。 可以设置多个。"  class="span12 uniform" rows="3"><?php echo !empty($subParams['ReceptionUri']) ? $subParams['ReceptionUri'] : (empty($rs['reception_channel']) ? '' : $rs['reception_channel']);?></textarea>
                                                <p class="help-block">
                                                <dl>
                                                    <dd>可以设置多个地址，每个地址占一行,系统每次推送消息时将随机地从以上列表中选取一个地址;</dd>
                                                    <dd>地址前面可打上环境标签，推送服务将根据运行环境及环境标签选取匹配的地址，若未匹配到对应环境标签的地址，则将选用不带标签的地址。例如(目前已支持<?php echo empty(\App\Admin\Cfg\App::$supportEnvs) ? 'pub、prod-cl、prod-yz' : implode('、', \App\Admin\Cfg\App::$supportEnvs) ;?>)：</dd>
                                                    <dd>
                                                    <?php
                                                    if (empty(\App\Admin\Cfg\App::$supportEnvs)) {
                                                    ?>
                                                        [t_env:pub]http://127.0.0.1/pub/message_processor<br />
                                                        [t_env:dev]http://127.0.0.1/dev/message_processor<br />
                                                    <?php
                                                    } else {
                                                        foreach (\App\Admin\Cfg\App::$supportEnvs as $env) {
                                                            echo "[t_env:$env]http://127.0.0.1/$env/message_processor<br />";
                                                        }
                                                    }?>
                                                        http://127.0.0.1/message_processor
                                                    </dd>
                                                </dl>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="control-group">
                                            <label class="control-label">消息处理超时</label>
                                            <div class="controls">
                                                <p class="alert-block"></p>
                                                <div id="slider-timeout" class="warning-slider"></div>
                                                <input type="hidden" id="field-timeout" name="timeout"  class="span1" min="1" max="<?php echo $defaultParams['MaxMessageProcessTime']; ?>" value="<?php echo $timeout; ?>">
                                                <p class="help-block">
                                                (单位：毫秒。最小为1, 默认为<?php echo $defaultParams['DefaultMaxMessageProcessTime']; ?>，最大: <?php echo $defaultParams['MaxMessageProcessTime']; ?>)
                                                </p>
                                            </div>
                                        </div>
                                        <div class="control-group">
                                            <label class="control-label">推送并发数</label>
                                            <div class="controls">
                                                <p class="alert-block"></p>
                                                <div id="slider-concurrency" class="important-slider"></div>
                                                <input type="hidden" id="field-concurrency" name="concurrency"  class="span1" min="0" max="<?php echo $defaultParams['MaxSendersPerChannel']; ?>" value="<?php echo $subParams['ProcessTimeout'] == 0 ? $defaultParams['SendersPerChannel'] : $subParams['Concurrency'];?>">
                                                <p class="help-block">
                                                    (最大：<?php echo $defaultParams['MaxSendersPerChannel']; ?>. 并发数越大，消息推送速率越大。当设置为０时，能起到暂停推送的效果，消息不会被丢弃.)
                                                </p>
                                            </div>
                                        </div>
                                        <div class="control-group info">
                                            <label class="control-label">(重试队列)推送并发数：</label>
                                            <div class="controls">
                                                <p class="alert-block"></p>
                                                <div id="slider-concurrency_as_retry" class="info-slider"></div>
                                                <input type="hidden" id="field-concurrency_as_retry" name="concurrency_as_retry" class="span1" min="0" max="<?php echo $defaultParams['MaxSendersPerRetryChannel']; ?>" value="<?php echo $subParams['ProcessTimeout'] == 0 ? $defaultParams['SendersPerRetryChannel'] : $subParams['ConcurrencyOfRetry'];?>">
                                                <p class="help-block">
                                                    (最大：<?php echo $defaultParams['MaxSendersPerRetryChannel']; ?>. 并发数越大，消息推送速率越大。)
                                                </p>
                                            </div>
                                        </div>
                                        <div class="control-group">
                                            <label class="control-label">推送最小间隔时间</label>
                                            <div class="controls">
                                                <p class="alert-block"></p>
                                                <div id="slider-interval_of_pushes" class="success-slider"></div>
                                                <input type="hidden" id="field-interval_of_pushes" name="interval_of_pushes" class="span1" min="0" max="600000" value="<?php echo $subParams['ProcessTimeout'] == 0 ? $defaultParams['IntervalOfSendingForSendRoutine'] : $subParams['IntervalOfSending'];?>">
                                                <p class="help-block">
                                                    (并发的每个推送进程/线程/协程上, 最小的消息推送间隔时间（单位：毫秒）。间隔时间越小，消息推送速率越大。)
                                                </p>
                                            </div>
                                        </div>
                                        <div class="control-group">
                                            <label class="control-label">是否接收压测消息</label>
                                            <div class="controls">
                                                <input type="radio" name="receive_bench_msgs" value="0" <?php echo ! isset($subParams['ReceiveBenchMsgs']) || $subParams['ReceiveBenchMsgs'] == 0 ? 'checked' : ''; ?> />不接收压测消息 &nbsp;&nbsp;
                                                <input type="radio" name="receive_bench_msgs" value="1" <?php echo isset($subParams['ReceiveBenchMsgs']) && $subParams['ReceiveBenchMsgs'] == 1 ? 'checked' : ''; ?> /> 接收压测消息
                                                <p class="help-block">
                                                    (如果选择不接收压测消息，那么这些消息将会被丢弃)
                                                </p>
                                            </div>
                                        </div>
                                        <div class="control-group warning">
                                            <label class="control-label">报警设置</label>
                                            <div class="controls">
                                                <div>
                                                    <input type="radio" name="alerter_enabled" value="1" <?php echo isset($subParams['AlerterEnabled']) && $subParams['AlerterEnabled'] == false ? '': 'checked';?> />开启 &nbsp;&nbsp;
                                                    <input type="radio" name="alerter_enabled" value="0" <?php echo isset($subParams['AlerterEnabled']) && $subParams['AlerterEnabled'] == false ? 'checked': ''; ?> />关闭
                                                    <p class="help-block">
                                                    </p>
                                                </div>
                                                <!-- <div>
                                                    <input class="span5" type="tel" name="alerter_tel" value="<?php echo isset($subParams['AlerterPhoneNumbers']) ? $subParams['AlerterPhoneNumbers'] : '' ?>"/>
                                                    <p class="help-block">
                                                        手机。多个号码之间用逗号","分隔。
                                                    </p>
                                                </div>
                                                <div>
                                                    <input class="span5" type="email" name="alerter_email" value="<?php echo isset($subParams['AlerterEmails']) ? $subParams['AlerterEmails'] : '' ?>"/>
                                                    <p class="help-block">
                                                        邮箱。多个邮箱之间用逗号","分隔。
                                                    </p>
                                                </div> -->
                                                <div>
                                                    <input class="span5" type="text" name="alerter_receiver" value="<?php echo isset($subParams['AlerterReceiver']) ? $subParams['AlerterReceiver'] : '';?>" placeholder="auth帐号1,auth帐号2,auth帐号3" />
                                                    <p>
                                                        接收报警的auth帐号，多个帐号使用<span style="color:red;font-weight:bold">英文逗号</span>分割。
                                                    </p>
                                                </div>
                                                <p class="help-block">
                                                    <span class="info">
                                                        当消息处理失败频率或某条消息的处理失败次数达到一定值时，系统会向上面的手机号码及邮箱发送报警信息。
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="control-group">
    <label class="control-label">报警间隔</label>
    <div class="controls">
        <input type="number" min="1" name="AlarmInterval" value="<?php echo isset($subParams['AlarmInterval']) ? $subParams['AlarmInterval'] : 180; ?>" />
        <p class="help-block">
            (单位: 秒，默认180秒)
        </p>
    </div>
</div>
                                        <div class="control-group">
    <label class="control-label">错误次数计数器重置间隔</label>
    <div class="controls">
        <input type="number" min="1" name="IntervalOfErrorMonitorAlert" value="<?php echo isset($subParams['IntervalOfErrorMonitorAlert']) ? $subParams['IntervalOfErrorMonitorAlert'] : 180; ?>" />
        <p class="help-block">
            (单位: 秒，默认180秒)
        </p>
    </div>
</div>

<div class="control-group">
    <label class="control-label">错误次数</label>
    <div class="controls">
        <input type="number" min="1" name="SubscriptionTotalFailureAlertThreshold" value="<?php echo isset($subParams['SubscriptionTotalFailureAlertThreshold']) ? $subParams['SubscriptionTotalFailureAlertThreshold'] : 120; ?>" />
        <p class="help-block">
            向"特定订阅者"推送消息失败的次数
        </p>
    </div>
</div>

<div class="control-group">
    <label class="control-label">消息重试失败的报警阈值</label>
    <div class="controls">
        <input type="number" min="1" max="10" name="MessageFailureAlertThreshold" value="<?php echo isset($subParams['MessageFailureAlertThreshold']) ? $subParams['MessageFailureAlertThreshold'] : 7; ?>" />
        <p class="help-block">
            当"特定的消息"重试失败"的次数达到该阈值的时候会触发报警(0 < n < 10)
        </p>
    </div>
</div>



<div class="control-group">
    <label class="control-label">消息堆积的报警极限</label>
    <div class="controls">
        <input type="number" min="1" name="MessageBlockedAlertThreshold" value="<?php echo isset($subParams['MessageBlockedAlertThreshold']) ? $subParams['MessageBlockedAlertThreshold'] : 5000; ?>" />
        <p class="help-block">
            &nbsp
        </p>
    </div>
</div>
                                        <div class="control-groups">
                                            <div class="controls">
                                            <div class="pull-left post-msg">
                                                <input type="button" class="btn medium" onclick="history.go(-1);" value="返回" ></input>
                                                <input class="btn btn-primary"  type="submit" id="subscription-modify-btn" value="提交" >
                                            </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                    <input type="hidden" name="subscription_id" value="<?php echo $rs['subscription_id']; ?>" />
                                </form>
                            </div>
                            <!-- end content-->
                        </div>
                        <!-- end wrap div -->
                    </div>
            </article>
            </div>
            <!-- end row-fluid -->
        </section>
        <!-- end widget grid -->
    </div>
</div>
</div>
<?php require __DIR__ . DS . '../footer.php'; ?>
