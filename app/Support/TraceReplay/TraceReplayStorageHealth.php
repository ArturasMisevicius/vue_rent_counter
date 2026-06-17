<?php

namespace App\Support\TraceReplay;

use Illuminate\Support\Facades\Schema;
use Throwable;

class TraceReplayStorageHealth
{
    public function isReady(): bool
    {
        try {
            return Schema::hasTable('tr_traces') && Schema::hasTable('tr_trace_steps');
        } catch (Throwable) {
            return false;
        }
    }
}
