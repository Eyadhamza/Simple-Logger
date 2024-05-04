<?php

namespace Zeal\Logger\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Zeal\Logger\Core\Models\ApiLog;

class LogLoggableEntityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly Model $loggableEntity, private readonly ApiLog $apiLog)
    {

    }

    public function handle(): void
    {
        $this->apiLog->update([
            'loggable_id' => $this->loggableEntity->id,
            'loggable_type' => $this->loggableEntity->getMorphClass(),
        ]);
    }
}
