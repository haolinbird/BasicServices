<?php
/**
 * Rpc gateway.<br />
 * <h4>Nginx configuration<h4>
 * <pre>
 * <code>
 *   server {
 *       listen 80; 
 *       server_name rpc.event.jumeicd.com;
 *       root /Path/To/Event/App/Server/Gateway;
 *       location / { 
 *           try_files $uri /Rpc.php?$query_string;
 *       }   
 *      location ~ \.php$ {
 *           fastcgi_pass   127.0.0.1:9000;
 *           fastcgi_index  index.php;
 *           include        fastcgi_params;
 *           fastcgi_param  SCRIPT_FILENAME    $document_root$fastcgi_script_name;
 *       }   
    }  
 * </code>
 * </pre>
 * @author Su Chao<suchaoabc@163.com>.
 */
require __DIR__.'/../constants.php';
require SYS_ROOT . 'Vendor/Bootstrap/Autoloader.php';

spl_autoload_register ( array (
    Bootstrap\Autoloader::instance()->addRoot(SYS_ROOT.'/App/Server')->addRoot(SYS_ROOT)->init(),
        'loadByNamespace' 
) );
$mnloggerConfig = Lib\Util\Sys::getAppCfg('MNLogger');
MNLogger\TraceLogger::setUp($mnloggerConfig->trace);
MNLogger\EXLogger::setUp($mnloggerConfig->exception);
MNLogger\DATALogger::setUp($mnloggerConfig->data);
$srv = Lib\RpcServer::instance();
$srv->serve ();
