DROP TABLE IF EXISTS `broadcast_failure_log`;

CREATE TABLE `broadcast_failure_log` (
  `log_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `message_class_id` int(7) unsigned NOT NULL DEFAULT '0',
  `subscriber_id` int(7) unsigned NOT NULL DEFAULT '0',
  `time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '首次发送时间',
  `message_body` text NOT NULL COMMENT '消息主体',
  `retry_times` smallint(5) DEFAULT '0' COMMENT '重发重试次数',
  `last_retry_time` int(11) DEFAULT '0' COMMENT '上次重试发送时间',
  `last_failure_message` varchar(200) DEFAULT '' COMMENT '最后一次发送时,接收服务的错误消息',
  `final_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '最终发送状态.1 接收成功, 0 接收不成功',
  PRIMARY KEY (`log_id`),
  KEY `subscriber_id` (`subscriber_id`),
  KEY `message_class_id` (`message_class_id`),
  KEY `q_1` (`subscriber_id`,`message_class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table message_classes
# ------------------------------------------------------------

DROP TABLE IF EXISTS `message_classes`;

CREATE TABLE `message_classes` (
  `class_id` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `class_name` char(20) COLLATE utf8_bin NOT NULL DEFAULT '',
  `class_key` char(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `comment` varchar(300) COLLATE utf8_bin NOT NULL DEFAULT '',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`class_id`),
  UNIQUE KEY `unique_name` (`class_name`),
  UNIQUE KEY `unique_key` (`class_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

LOCK TABLES `message_classes` WRITE;
/*!40000 ALTER TABLE `message_classes` DISABLE KEYS */;

INSERT INTO `message_classes` (`class_id`, `class_name`, `class_key`, `comment`, `create_time`)
VALUES
	(28,'满返处理','PromoSalesCard','',1374486232),
	(29,'订单创建包裹','CreateShipping','',1374486590),
	(30,'用户返利','UserPromoCard','',1374486684),
	(31,'其他优惠处理','otherCoupon','',1374486706),
	(32,'用户积分和等级处理','UserUpgrade','',1374486727),
	(33,'处理商城商品购买数','updateMallProductBuyNumber','',1374486750),
	(34,'首张订单返券','firstOrderCoupon','',1374486774),
	(35,'败装活动','loveBuy','',1374486792),
	(36,'用户金币处理','userGold','',1374486817);

/*!40000 ALTER TABLE `message_classes` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table subscribers
# ------------------------------------------------------------

DROP TABLE IF EXISTS `subscribers`;

CREATE TABLE `subscribers` (
  `subscriber_id` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `subscriber_name` char(20) NOT NULL DEFAULT '',
  `subscriber_key` char(30) NOT NULL DEFAULT '',
  `secret_key` char(30) NOT NULL DEFAULT '',
  `comment` varchar(300) NOT NULL DEFAULT '',
  `register_time` int(11) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0 正常; 1 已经注销',
  `allowed_message_class_to_send` varchar(500) NOT NULL DEFAULT '' COMMENT '用户允许发送的消息类型.用|分开,如:user_upgrade|user_report',
  `email_address` char(30) NOT NULL DEFAULT '' COMMENT '接收系统邮件的地址.当服务推送失败时,可能发送警告信息至指定的邮箱',
  PRIMARY KEY (`subscriber_id`),
  UNIQUE KEY `subscriber_key` (`subscriber_key`),
  UNIQUE KEY `subscriber_name` (`subscriber_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `subscribers` WRITE;
/*!40000 ALTER TABLE `subscribers` DISABLE KEYS */;

INSERT INTO `subscribers` (`subscriber_id`, `subscriber_name`, `subscriber_key`, `secret_key`, `comment`, `register_time`, `status`, `allowed_message_class_to_send`, `email_address`)
VALUES
	(10,'聚美前端','jumei_order','mypassword','',1372868119,0,'PromoSalesCard|CreateShipping|UserPromoCard|otherCoupon|UserUpgrade|updateMallProductBuyNumber|firstOrderCoupon|loveBuy|userGold','');

/*!40000 ALTER TABLE `subscribers` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table subscriptions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `subscriptions`;

CREATE TABLE `subscriptions` (
  `subscription_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `subscriber_id` int(6) unsigned NOT NULL DEFAULT '0',
  `message_class_id` int(7) unsigned NOT NULL DEFAULT '0',
  `reception_channel` varchar(225) NOT NULL DEFAULT '' COMMENT '接受消息的地址',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0=有效 1=无效',
  `subscribe_time` int(11) unsigned NOT NULL DEFAULT '0',
  `timeout` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '超时时间',
  PRIMARY KEY (`subscription_id`),
  UNIQUE KEY `unique_1` (`subscriber_id`,`message_class_id`),
  KEY `q_1` (`subscriber_id`,`message_class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;

INSERT INTO `subscriptions` (`subscription_id`, `subscriber_id`, `message_class_id`, `reception_channel`, `status`, `subscribe_time`, `timeout`)
VALUES
	(67,10,36,'http://worker.srv.jumei.com/User/UserGold',0,1374488202,10),
	(68,10,35,'http://worker.srv.jumei.com/PromoCard/LoveBuy',0,1374488232,0),
	(69,10,34,'http://worker.srv.jumei.com/PromoCard/FirstOrderCoupon',0,1374488258,0),
	(70,10,33,'http://worker.srv.jumei.com/Product/UpdateMallProductBuyNumber',0,1374488291,0),
	(71,10,32,'http://worker.srv.jumei.com/User/UserUpgrade',0,1374488320,0),
	(72,10,31,'http://worker.srv.jumei.com/PromoCard/OtherCoupon',0,1374488342,0),
	(73,10,30,'http://worker.srv.jumei.com/PromoCard/UserPromoCard',0,1374488379,0),
	(74,10,28,'http://worker.srv.jumei.com/PromoCard/PromoSalesCard',0,1374488406,5),
	(75,10,29,'http://worker.srv.jumei.com/Batch/CreateShipping',0,1374488454,5);

/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;