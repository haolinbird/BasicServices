系统简介
=======
消息中心主要包含以下三个服务

* ######消息接收网关
  EventServer/App/Server/Gateway/Rpc.php
* #####消息推送服务
  EventServer/App/Server/Cli/Daemon/BroadcastSender
  
* #####消息中心管理后台
 EventServer/App/Admin/Public

系统服务需求
==========
* PHP5.3+
* Redis server
* Beanstalkd
* Mysql5.5+

PHP pecl扩展依赖 
===============
### 基础扩展 ###
* pdo_mysql
* mb_string
* apc
* curl
* pcntl
* zip
* zlib

代码库
=======
`hg clone -b default https://hg.jumeicd.com/EventServer`


### 服务端扩展 ###
* redis ([https://github.com/nicolasff/phpredis](https://github.com/nicolasff/phpredis "phpredis"))   
  __redis客户端版本2.2+__
* msgpack ([https://github.com/msgpack/msgpack-php.git](https://github.com/msgpack/msgpack-php.git))    

消息队列服务Beanstalkd安装
=======================
* 注意提高消息大小的最大值

   -z 默认为6k, 可调高至1m左右
   

Redis服务器配置要求参考
====================
由于大部分消息内容都是非常重要（如:用户等级更新、订单完成状况等），
所以对数据的完整性有很高的要求，所使用的redis服务需要尽快把数据写入磁盘，
以避免故障或者重启导致重要数据的丢失。

配置指令"SAVE 1 1", 在1秒之内有数据更改则将数据同步至磁盘。

消息提交网关搭建
=================
* ####RPC Example
  * ##### Nginx配置
<pre>
    server {
        listen       80;
        server_name  rpc.me.int.jumei.com;
     	gzip on;
        root /home/www/EventServer/App/Server/Gateway/;

       access_log /var/log/nginx/failbackup.rpc.me.int.access.log;
       error_log  /var/log/nginx/failbackup.rpc.me.int.error.log;
       location / {
           try_files $uri /Rpc.php?$query_string;
       }    
       
	  location ~ \.php {
	        fastcgi_pass   127.0.0.1:9000;
	        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
	        include fastcgi_params;
	   }          
    }
</pre>

* ####灾备服务搭建
###### _此服务在消息推送网关无法工作的情况下自动启用， 不需要依赖外部服务，将客户端推送过来的消息直接写入本地磁盘中，以备恢复。_ 
  * 日志目录 /home/www/logs/     (*不可修改。。请务必保证此目录对fpm进程可写*)
  
  * ####Nginx配置:
<pre>
server {
  listen   80; 
  server_name  rpc.me.int.jumei.com;

  root /home/www/EventServer/App/Failbackup/Public;

  access_log /var/log/nginx/failbackup.rpc.me.int.access.log;
  error_log  /var/log/nginx/failbackup.rpc.me.int.error.log;
  location / { 
      try_files $uri /index.php?$query_string;
  }

    
  location ~ \.php {
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        include fastcgi_params;
   }   

}
</pre>

* #### RPC前端负载均衡(基于nginx)配置参考, 包括灾备服务搭建部分
   * #####Nnginx配置(只包括upstream和rpc前端)
<pre>

    upstream rpc-event {
         server  10.0.19.228:80 weight=10; #rpc.me.int.jumei.com后端
         server  10.0.19.229:80 weight=10; #rpc.me.int.jumei.com后端
         server  10.0.19.231:80 backup;   #rpc.me.int.jumei.com灾备，前两组服务无法提供服务自动启用
    } 
    

    server {
        listen 80;
        server_name rpc.me.int.jumei.com;   #rpc.me.int.jumei.com灾备，前两组服务无法提供服务自动启用
        
        #必须加上以下指令,否则服务自动启用灾备backup.        
        proxy_next_upstream error timeout invalid_header http_500 http_502 http_503 http_504 http_404;
        
        location / {
            proxy_pass http://rpc-event;
        }    
</pre>


消息事件中心管理后台搭建
====================
#####Example:

<pre>
server {
  listen   80; 
  server_name  dev.meman.jumeicd.com;

 root /home/www/EventServer/App/Admin/Public/;

  access_log /var/log/nginx/dev.meman.jumeicd.access.log;
  error_log  /var/log/nginx/dev.meman.jumeicd.error.log;
  location / { 
      try_files $uri /index.php?$query_string;
  }

    
  location ~ \.php {
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        include fastcgi_params;
   }   

}
</pre>



消息事件中心服务安装
==============

* 进入EventServer/App/Server/    
* run setup.sh



配置检查
=======
###以下为必需检查的配置选项

*如果没有以下文件, 请从Event/Server/{AppName}/Docs/Examples/Cfg/ 下进行拷贝！*

* 消息接收RPC及消息推送服务器

    * EventServer/App/Server/Cfg/Beanstalk
    * EventServer/App/Server/Cfg/Db
    * EventServer/App/Server/Cfg/Redis
    * EventServer/App/Server/Cfg/Service::DAEMON_UID
    * EventServer/App/Server/Cfg/Service::DAEMON_GID       
    
* 消息管理中心后台

    * EventServer/App/Admin/Cfg/Db
    * EventServer/App/Admin/Cfg/Auth     
    * EventServer/App/Admin/Cfg/Redis   

***注意日志目录和文件的权限设置**    
**Server/Cfg/Redis和Admin/Cfg/Redis 配置需一致，以免无法命中缓存**

日志配置
=======
###开启DB日志
>设置EventServer/App/Server/Cfg/Db::DEBUG=true    
>生产环境建议将DEBUG_LEVEL设置为 1   

###开启Rpc服务端日志
>设置EventServer/App/Server/Cfg/RpcServer::DEBUG=true  
>确认EventServer/App/Server/Cfg/Log::$rpcServer 已经正确配置，为便于管理，请把logger设置为"file"   

###开启事件中心服务日志

*非特殊情况建议不要开启*
>设置EventServer/App/Server/Cfg/Service::ENABLE_OUTPUT_LOG=true  

###日志目录权限配置

* 消息接收服务(rpc) 
   RPC 默认日志目录为： /var/log/jm-event-center    
   需要注意修改此目录权限，使其可以被php-fpm进程用户读写.
   
* 消息推送服务
  日志默认目录为： /var/log/jm-event-center
  此目录将由服务自动创建.


###Log转储配置
* ####消息中心所有服务日志转储配置: 创建etc/logrotate.d/jmevent, 内容如下：   
<pre>
      /var/log/jm-event-center/*/*.log {
             daily
             create 0640 www-data adm  
             rotate 8 
             compress
             notifempty
             size 100M
      }
</pre>
  运行   `sudo logrotate -f /etc/logrotate.d/jmevent`
  
