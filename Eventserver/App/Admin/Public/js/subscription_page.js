function show_slide_tip(id, value){
    var paddingFix = "-11";
    if(value < 100){
        paddingFix = "-3";
    } else if(value < 1000) {
        paddingFix = "-5";
    }
    paddingFix = 'style="margin-left:'+paddingFix+'px"';
    $('#'+id+' .ui-slider-handle:first').html('<div class="tooltip top slider-tip" '+paddingFix+'><div class="tooltip-arrow"></div><div class="tooltip-inner">' + value + '</div></div>');
}
function setup_uislider() {
    // 处理超时
    var timeoutSlider = $( "#slider-timeout" ).slider({
        range: "min",
        value: 1,
        min: 1,
        max: 900,
        slide: function( event, ui ) {
            $( "#field-timeout" ).val(ui.value );
            show_slide_tip('slider-timeout', ui.value)
        }
    });
    timeoutSlider.slider("option", "min", parseInt($("#field-timeout").attr("min")));
    timeoutSlider.slider("option", "max", parseInt($("#field-timeout").attr("max")));
    timeoutSlider.slider("value", $("#field-timeout").val());
    show_slide_tip('slider-timeout', $("#field-timeout").val());

    // 并发数
    var concurrencySlider = $( "#slider-concurrency" ).slider({
        range: "min",
        value: 1,
        min: 1,
        max: 900,
        slide: function( event, ui ) {
            $( "#field-concurrency" ).val(ui.value );
            show_slide_tip('slider-concurrency', ui.value)
        }
    });
    concurrencySlider.slider("option", "min", parseInt($("#field-concurrency").attr("min")));
    concurrencySlider.slider("option", "max", parseInt($("#field-concurrency").attr("max")));
    concurrencySlider.slider("value", $("#field-concurrency").val());
    show_slide_tip('slider-concurrency', $("#field-concurrency").val());

    // 重推并发数
    var concurrencySlider = $( "#slider-concurrency_as_retry" ).slider({
        range: "min",
        value: 1,
        min: 1,
        max: 900,
        slide: function( event, ui ) {
            $( "#field-concurrency_as_retry" ).val(ui.value );
            show_slide_tip('slider-concurrency_as_retry', ui.value)
        }
    });
    concurrencySlider.slider("option", "min", parseInt($("#field-concurrency_as_retry").attr("min")));
    concurrencySlider.slider("option", "max", parseInt($("#field-concurrency_as_retry").attr("max")));
    concurrencySlider.slider("value", $("#field-concurrency_as_retry").val());
    show_slide_tip('slider-concurrency_as_retry', $("#field-concurrency_as_retry").val());

    // 推送间隔时间
    var concurrencySlider = $( "#slider-interval_of_pushes" ).slider({
        range: "min",
        value: 1,
        min: 1,
        max: 900,
        slide: function( event, ui ) {
            $( "#field-interval_of_pushes" ).val(ui.value );
            show_slide_tip('slider-interval_of_pushes', ui.value)
        }
    });
    concurrencySlider.slider("option", "min", parseInt($("#field-interval_of_pushes").attr("min")));
    concurrencySlider.slider("option", "max", parseInt($("#field-interval_of_pushes").attr("max")));
    concurrencySlider.slider("value", $("#field-interval_of_pushes").val());
    show_slide_tip('slider-interval_of_pushes', $("#field-interval_of_pushes").val());
}
$(setup_uislider);