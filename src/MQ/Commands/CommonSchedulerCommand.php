<?php

namespace MabangSdk\MQ\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use MabangSdk\MQ\Schedulers\CommonScheduler;
use Symfony\Component\Console\Input\InputArgument;

class CommonSchedulerCommand extends Command
{
    /**
     * @var string
     */
    protected $name = "mq:common-scheduler";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '公用队列消息分发';

    /**
     * @var CommonScheduler
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
     * CommonSchedulerCommand constructor.
     * @param CommonScheduler $scheduler
     */
    public function __construct(CommonScheduler $scheduler)
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

        $this->scheduler->run($queueName);
    }
}
