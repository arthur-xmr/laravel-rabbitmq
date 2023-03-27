##  消息队列扩展包
### 安装
 composer install "laravel-rabbitmq:1.0.1"

### 使用说明 
建立连接

```php
use PhpAmqpLib\Connection\AMQPLazyConnection;

 $this->app->singleton('amqp', function () use ($amqpConfig) {
            return new AMQPLazyConnection(
                $amqpConfig['rabbitmq']['connection']['RABBITMQ_HOST'],
                $amqpConfig['rabbitmq']['connection']['RABBITMQ_PORT'],
                $amqpConfig['rabbitmq']['connection']['RABBITMQ_USERNAME'],
                $amqpConfig['rabbitmq']['connection']['RABBITMQ_PASSWORD'],
                $amqpConfig['rabbitmq']['connection']['RABBITMQ_VHOST'],
                false,
                $amqpConfig['rabbitmq']['connection']['RABBITMQ_LOGIN_METHOD'],

            );
        });
```
```php
#生产普通队列：

MQ::publish(MqConstant::TEST_SCENE_CODE, $msg_date);
#生产延迟队列：
MQ::publish(MqConstant::TEST_SCENE_CODE, $msg_date, $seconds);

#队列消费:
php artisan mq:scheduler TEST_ABC_QUEUE # 队列名称
#
#队列绑定关系输入缓存
php artisan mq:flush-scene```

  
```# 确认模式
$channel->confirm_select();

# 发送
$producer->publish('QUEUE', $msg_date);

# 等待
$channel->wait_for_pending_acks();
```

```#消费消息

```php
use Mq\Amqp\Consumer;

$consumer = new Consumer($connection);

// 回调函数
$callback = function($messageBody) {
    var_dump($messageBody);
    return false;
};

## 配置

1、数据库 用户绑定关系创建，以及失败消息记录
 'v2-mabang-rabbitmq' => [
                'driver'   => 'mysql',
                'host'     => '192.168.2.***',
                'database' => 'mabang*****',
                'username' => 'Arthur',
                'password' => 'xumoran*****',
                'port'     => 3306,
                'charset'  => 'utf8',
                'strict'   => false,
                'options'  => [],
        ],
        <?php
 2、mq 连接配置
    'rabbitmq'  => [
        'connection'    => [
            'RABBITMQ_HOST'         => env('RABBITMQ_HOST', 'amqp-****'),
            'RABBITMQ_PORT'         => env('RABBITMQ_PORT', 5672),
            'RABBITMQ_USERNAME'     => env('RABBITMQ_USERNAME', 'Arthur***'),
            'RABBITMQ_PASSWORD'     => env('RABBITMQ_PASSWORD', 'eyJrZXlJ*******'),
            'RABBITMQ_VHOST'        => env('RABBITMQ_VHOST', 'amqp-de73***|TEST_***'),
            'RABBITMQ_LOGIN_METHOD' => env('RABBITMQ_LOGIN_METHOD', 'PLAIN')
        ],
        'qos'          => [
            'enabled'              => env('MQ_QOS_ENABLED', false),
            'prefetch_size'        => env('MQ_QOS_PREFETCH_SIZE', 0),
            'prefetch_count'       => env('MQ_QOS_PREFETCH_COUNT', 20),
            'global'               => env('MQ_QOS_GLOBAL', true),
        ],
    ]


 
        
```
