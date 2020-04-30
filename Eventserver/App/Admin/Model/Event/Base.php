<?php
namespace App\Admin\Model\Event;
class Base extends \Lib\BaseDbModel {
    /**
     * configuration name of database
     * @var string
     */
    const DATABASE = 'default';
    const DATABASE_BENCH = 'default_bench';
    const TABLE_MSG_CLASSES = 'message_classes';
    const TABLE_BROADCAST_LOG= 'broadcast_failure_log';
    const TABLE_SUBSCRIBERS = 'subscribers';
    const TABLE_SUBSCRIPTIONS = 'subscriptions';

}
