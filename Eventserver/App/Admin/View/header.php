<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <title>消息中心管理后台<?php if(isset($page_title)) echo "-$page_title";?></title>
        <meta name="keywords" content="{$PageInfo.Keywords|escape}" />
        <meta name="description" content="{$PageInfo.Description|escape}" />
        <meta http-equiv="X-UA-Compatible" content="IE=Edge" />
        <!--<link href="/favicon.ico" rel="icon" /> -->
        <link href="/css/bootstrap.css" rel="stylesheet" type="text/css" />
        <link href="/css/bootstrap-responsive.css" rel="stylesheet" type="text/css" />
        <link href="/css/theme.css" rel="stylesheet" type="text/css" />
        <link href="/css/theme-responsive.css" rel="stylesheet" type="text/css" />
        <link href="/css/widgets.css" rel="stylesheet" type="text/css" />
        <link href="/css/bootstrap-custom.css" rel="stylesheet" type="text/css" />
        <script src="/js/jquery-1.7.2.min.js" type="text/javascript"></script>
        <script src="/js/bootstrap.min.js" type="text/javascript"></script>
        <script src="/js/custom.js" type="text/javascript"></script>
    </head>
    <body>
    <?php
        if (isset($messageLists)) {
            foreach ($messageLists as $type => $messages) {
                foreach ($messages as $key => $message) {
                    echo "<div class=\"$type\">$message</div>\n";
                }
            }
        }
    ?>
