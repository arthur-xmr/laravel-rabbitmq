<?php

return [
    'rabbitmq'  => [
        'connection'    => [
            'RABBITMQ_HOST'         => env('RABBITMQ_HOST', 'amqp-de73x58kr5ra.rabbitmq.ap-gz.public.tencenttdmq.com'),
            'RABBITMQ_PORT'         => env('RABBITMQ_PORT', 5672),
            'RABBITMQ_USERNAME'     => env('RABBITMQ_USERNAME', 'xmr-test-kandeng'),
            'RABBITMQ_PASSWORD'     => env('RABBITMQ_PASSWORD', 'eyJrZXlJZCI6ImFtcXAtZGU3M3g1OGtyNXJhIiwiYWxnIjoiSFMyNTYifQ.eyJzdWIiOiJhbXFwLWRlNzN4NThrcjVyYV94bXItdGVzdC1rYW5kZW5nIn0.V97iHJF4gUFHOEIE2wi-lII_uzXP0SDdN4CWXeG3zWU'),
            'RABBITMQ_VHOST'        => env('RABBITMQ_VHOST', 'amqp-de73x58kr5ra|TEST_KANDENG'),
            'RABBITMQ_LOGIN_METHOD' => env('RABBITMQ_LOGIN_METHOD', 'PLAIN')
        ],
        'qos'          => [
            'enabled'              => env('MQ_QOS_ENABLED', false),
            'prefetch_size'        => env('MQ_QOS_PREFETCH_SIZE', 0),
            'prefetch_count'       => env('MQ_QOS_PREFETCH_COUNT', 20),
            'global'               => env('MQ_QOS_GLOBAL', true),
        ],
    ],
];
