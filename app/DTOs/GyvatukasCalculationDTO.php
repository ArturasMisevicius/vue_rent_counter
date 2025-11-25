<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Building;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Gyvatukas Calculation Data Transfer Object
 * 
 * Immutable DTO for gyvatukas calculation requests.
 *
 * @package App\DTOs
 */
final readonly class GyvatukasCalculationDTO
{
    public function __construct(
        public int $buildingId,
        public Carbon $billingMonth,
        public ?string $distributionMethod = 'equal'
    ) {}

    /**
     * Create from HTTP request.
     *
     * @param Request $request
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            buildingId: (int) $request->input('building_id'),
            billingMonth: Carbon::parse($request->input('billing_month')),
            distributionMethod: $request->input('distribution_method', 'equal')
        );
    }

    /**
     * Create from building and month.
     *
     * @param Building $building
     * @param Carbon $billingMonth
     * @param string $distributionMethod
     * @return self
     */
    public static function fromBuilding(
        Building $building,
        Carbon $billingMonth,
        string $distributionMethod = 'equal'
    ): self {
        return new self(
            buildingId: $building->id,
            billingMonth: $billingMonth,
            distributionMethod: $distributionMethod
        );
    }

    /**
     * Validate the DTO data.
     *
     * @return array Validation errors (empty if valid)
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->buildingId <= 0) {
            $errors['building_id'] = 'Building ID must be positive';
        }

        if ($this->billingMonth->isFuture()) {
            $errors['billing_month'] = 'Billing month cannot be in the future';
        }

        if (!in_array($this->distributionMethod, ['equal', 'area'])) {
            $errors['distribution_method'] = 'Distribution method must be "equal" or "area"';
        }

        return $errors;
    }

    /**
     * Check if DTO is valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
