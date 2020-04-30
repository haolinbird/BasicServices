$(function(){
    //event
    $('#subscription-modify-btn').click(function(){
        if ($.trim($('form').find('input[name="alerter_receiver"]').val()) == '') {
            alert('必须配置报警接收人的auth帐号!');
            $('form').find('input[name="alerter_receiver"]').focus();
            return false;
        }

        $.ajax({
            url: "/Event/SubscriptionUpdate",
            type: "POST",
            dataType: "json",
            data: $('form').serialize(),
            success: function(res) {
                if (res.code == 'error') {
                    alert(res.message);
                    return false;
                }
                alert(res.message);
                window.location = document.referrer;
            },
        });
        return false;
    });

    $('.subscription-delete-btn').click(function(){

        var isConfirm = confirm('确认要删除当前项？');
        if (isConfirm) {

            var data = {
                'id':$(this).attr('data-id')
            };
            var that = $(this);

            $.ajax({
                url: "/Event/SubscriptionDelete",
                type: "POST",
                dataType: "json",
                data: $.param(data),
                success: function(res) {
                    if (res.code == 'error') {
                        alert(res.message);
                        return false;
                    }
                    that.parent().parent().fadeOut('slow');
                },
            });

        }
    });

    //cache obj
    var subscriberClasses = {};
    /*
    $('select[name="subscriber"]').change(function(){
        var subscriber_key = $('select>option:selected').val();
        var data = {
            subscriber_key : subscriber_key,
        };
        var checkCheckbox = function(res){
            $('input[type="checkbox"]').removeAttr('checked').removeAttr('disabled');
            for (var i=0; i < res.length; i++) {
                var option = res[i];
                $('input[value="'+res[i]+'"]').attr('checked','checked').attr('disabled', 'disabled');
            };
        }
        if (subscriberClasses.hasOwnProperty(subscriber_key)) {
            checkCheckbox(subscriberClasses[subscriber_key]);
            return false;
        } 
        $.ajax({
            url: "/Event/SubscriberGet",
            type: "POST",
            dataType: "json",
            data: $.param(data),
            success: function(res) {
                checkCheckbox(res);
                subscriberClasses[subscriber_key] = res;
            },
        });
    });

*/
    //add subscription
    $('.subscription-add-btn').click(function(){
        var message_class_ids = [];
        $('input[name="class_key"]:checked').each(function(){
            if ($(this).attr('disabled')!='disabled') {
                message_class_ids.push($(this).attr('data-id'));
            }
        });
        if (!message_class_ids.length) {
            alert('请勾选消息分类!');
            return false;
        }

        if ($.trim($('input[name="alerter_receiver"]').val()) == '') {
            alert('必须配置报警接收人的auth帐号!');
            $('input[name="alerter_receiver"]').focus();
            return false;
        }

        var postData = $('form').serialize();
        postData += '&message_class_ids[]=' + message_class_ids.join('message_class_ids[]=');
        $.ajax({
            url: "/Event/SubscriptionAdd",
            type: "POST",
            dataType: "json",
            data: postData,
            success: function(res) {
                if (res.code == 'error') {
                    alert(res.message);
                    return false;
                }
                alert(res.message);
                window.location = "/Event/SubscriptionList";
            },
            error: function() {
                alert('更新失败');
            },
        });
        return false;
    }); 

    $('.delete-btn').click(function(){
        if(confirm("确认删除?")) {
          window.location = $(this).attr('url');    
        }
    });
});
