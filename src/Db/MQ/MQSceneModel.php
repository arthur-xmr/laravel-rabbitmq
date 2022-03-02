<?php

namespace  MabangSdk\Db\MQ;

use  MabangSdk\Db\Model;

class MQSceneModel extends Model
{
    protected $connection = 'v2-mabang-rabbitmq';
    protected $table = 'rabbitmq_scene';
}
