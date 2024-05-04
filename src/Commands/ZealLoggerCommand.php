<?php

namespace Zeal\Logger\Commands;

use Illuminate\Console\Command;

class ZealLoggerCommand extends Command
{
    public $signature = 'zeal-logger';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
