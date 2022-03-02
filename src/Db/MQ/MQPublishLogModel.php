<?php

namespace MabangSdk\Db\MQ;

use  MabangSdk\Db\Model;

class MQPublishLogModel extends Model
{
    protected $connection = 'v2-mabang-rabbitmq';
    protected $table = 'rabbitmq_publish_log';
}
