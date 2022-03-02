<?php

namespace MabangSdk\MQ\Commands;

use Illuminate\Console\Command;
use MabangSdk\MQ\Facades\MQ;

class DeclareCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mq:declare';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '声明队列场景配置';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $response = MQ::declareSceneConfig();
        if ($response) {
            $this->info('Declare scene config successfully!');
        }
    }
}
