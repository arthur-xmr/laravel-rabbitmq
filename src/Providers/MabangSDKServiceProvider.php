<?php

namespace MabangSdk\Providers;

use MabangSdk\MQ\Amqp;
use MabangSdk\MQ\Commands\CommonSchedulerCommand;
use MabangSdk\MQ\Commands\DeclareCommand;
use MabangSdk\MQ\Commands\FlushSceneCommand;
use MabangSdk\MQ\Commands\NormalSchedulerCommand;
use MabangSdk\MQ\Consumer;
use MabangSdk\MQ\Producer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Config;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use Illuminate\Container\Container;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;

class MabangSDKServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerMQ();
        $this->registerCommands();
    }

     protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                FlushSceneCommand::class,
                DeclareCommand::class,
                NormalSchedulerCommand::class,
                CommonSchedulerCommand::class,
            ]);
        }
    }

    protected function registerMQ()
    {
        $amqpConfig = Config('mq');
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

        $this->app->bind(Producer::class, function (Container $container) {
            try {
                $producer = new Producer($container->make('amqp'));
            } catch (AMQPConnectionClosedException $e) {
                usleep(200000);
                $producer = new Producer($container->make('amqp'));
            }

            return $producer;
        });

        $this->app->bind(Consumer::class, function (Container $container) use ($amqpConfig) {
            try {
                $consumer = new Consumer($container->make('amqp'));
            } catch (AMQPConnectionClosedException $e) {
                usleep(200000);
                $consumer = new Consumer($container->make('amqp'));
            }
            if (isset($amqpConfig['rabbitmq']['qos']['enabled']) && $amqpConfig['rabbitmq']['qos']['enabled']) {
                $consumer->setQos([
                    'prefetch_size'     => $amqpConfig['rabbitmq']['qos']['enabled'],
                    'prefetch_count'    => $amqpConfig['rabbitmq']['qos']['prefetch_count'],
                    'global'            => $amqpConfig['rabbitmq']['qos']['global'],
                ]);
            }

            return $consumer;
        });

        $this->app->singleton(Amqp::class, function () {
            return new Amqp();
        });
        $this->app->alias(Amqp::class, 'base.mq');
    }
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
