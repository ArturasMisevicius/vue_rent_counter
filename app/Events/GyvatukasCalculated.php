<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Building;
use App\ValueObjects\CalculationResult;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when gyvatukas calculation is completed.
 */
final class GyvatukasCalculated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Building $building,
        public readonly CalculationResult $result,
    ) {}
}