<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Building;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when summer average is calculated and stored.
 */
final class SummerAverageCalculated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Building $building,
        public readonly float $summerAverage,
        public readonly int $monthCount,
    ) {}
}