<div class="navbar navbar-fixed-top">
<div class="navbar-inner">
<div class="container">
<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
<span class="icon-bar"></span>
<span class="icon-bar"></span>
<span class="icon-bar"></span>
</a>
<a class="brand" href="/">消息中心管理后台</a>
<div class="nav-collapse">
<ul class="nav">
<?php
$menus = \Lib\Util\Sys::getAppCfg('Menu');
foreach ($menus::$items as $group):
?>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    	<?php echo $group['group_name']; ?> 
                    	<b class="caret"></b>
                    </a>
                    <ul class="dropdown-menu">
                    	<?php foreach ($group['list'] as $item): ?>
                        <li>
                        	<a href="<?php echo $item['url']; ?>" <?php echo isset($item['target']) ? ('target="' . $item['target'] . '"') : '';?> >
                        		<?php echo $item['title']; ?>
                        	</a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endforeach; ?>
            </ul>
            <ul class="pull-right nav">
            	<li class="">
            		<a href="<?php echo HTTP_BASE_URL;?>Auth/Logout">
            			退出 (<?php echo \Lib\User::current()->info->fullname; ?>)
            		</a>
            	</li>
            </ul>
        </div><!--/.nav-collapse -->
    </div>
</div>
</div>