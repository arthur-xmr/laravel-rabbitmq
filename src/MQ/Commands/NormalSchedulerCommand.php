<?php

namespace MabangSdk\MQ\Commands;

use Illuminate\Console\Command;
use MabangSdk\MQ\Facades\MQ;
use MabangSdk\MQ\Schedulers\NormalScheduler;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\InputArgument;

class NormalSchedulerCommand extends Command
{
    /**
     * @var string
     */
    protected $name = "mq:scheduler";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '队列消息分发';

    /**
     * @var NormalScheduler
     */
    protected $scheduler;

    /**
     * @return array|array[]
     */
    protected function getArguments()
    {
        return [
            ['queue_name', InputArgument::REQUIRED, 'queue name'],
        ];
    }

    /**
     * NormalSchedulerCommand constructor.
     * @param NormalScheduler $scheduler
     */
    public function __construct(NormalScheduler $scheduler)
    {
        $this->scheduler = $scheduler;

        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Start ' . __METHOD__);
        $queueName = $this->argument('queue_name');
        $sceneInfo = MQ::getSceneInfoByQueue($queueName);

        $this->scheduler->run($queueName, $sceneInfo['handler']);
    }
}
