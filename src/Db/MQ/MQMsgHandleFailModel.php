<?php

namespace MabangSdk\Db\MQ;

use MabangSdk\Db\Model;

class MQMsgHandleFailModel extends Model
{
    protected $connection = 'v2-mabang-rabbitmq';
    protected $table = 'rabbitmq_handle_fail';
}
