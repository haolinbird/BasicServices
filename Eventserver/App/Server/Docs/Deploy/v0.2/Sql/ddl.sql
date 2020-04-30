ALTER TABLE `subscribers` ADD `privileges`  VARCHAR(2500)  CHARACTER SET utf8  COLLATE utf8_bin  NOT NULL  DEFAULT '' COMMENT '订阅者权限选项' ;
ALTER TABLE `broadcast_failure_log` ADD `message_time` DECIMAL(14,4)  NOT NULL  DEFAULT '0'  COMMENT '消息提交时间';
ALTER TABLE `broadcast_failure_log` ADD `alive` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否还存在于推送队列中,1-是, 0-否';
ALTER TABLE `broadcast_failure_log` ADD INDEX  `time_comound` (`time`,`final_status`,`alive`,`message_time`,`message_class_id`,`subscriber_id`);
ALTER TABLE `broadcast_failure_log` DROP KEY `log_id_compound`, DROP KEY `subscriber_id`, DROP KEY `message_class_id`;