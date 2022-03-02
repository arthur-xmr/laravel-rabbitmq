<?php

namespace MabangSdk\MQ\Commands;

use Illuminate\Console\Command;
use MabangSdk\MQ\Facades\MQ;

class FlushSceneCommand extends Command
{
    /**
     * @var string
     */
    protected $name = 'mq:flush-scene';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '刷新队列的业务场景配置缓存';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $response = MQ::setSceneCache();
        if ($response) {
            $this->info('Mq scene cache flushed successfully!');
        }
    }
}
