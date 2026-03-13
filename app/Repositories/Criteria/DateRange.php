<?php

declare(strict_types=1);

namespace App\Repositories\Criteria;

use Illuminate\Database\Eloquent\Builder;

/**
 * Date Range Criteria
 * 
 * Filters query to include records within a specific date range.
 * Can be applied to any model with date fields.
 */
class DateRange implements CriteriaInterface
{
    /**
     * Create a new date range criteria.
     * 
     * @param string $field The date field to filter on
     * @param \DateTimeInterface $startDate Start of the date range
     * @param \DateTimeInterface $endDate End of the date range
     */
    public function __construct(
        private readonly string $field,
        private readonly \DateTimeInterface $startDate,
        private readonly \DateTimeInterface $endDate
    ) {}

    /**
     * {@inheritDoc}
     */
    public function apply(Builder $query): Builder
    {
        return $query->whereBetween($this->field, [
            $this->startDate,
            $this->endDate,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return "Filter {$this->field} between {$this->startDate->format('Y-m-d')} and {$this->endDate->format('Y-m-d')}";
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters(): array
    {
        return [
            'field' => $this->field,
            'start_date' => $this->startDate->format('Y-m-d H:i:s'),
            'end_date' => $this->endDate->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Create criteria for created_at field.
     * 
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return static
     */
    public static function createdBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): static
    {
        return new static('created_at', $startDate, $endDate);
    }

    /**
     * Create criteria for updated_at field.
     * 
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return static
     */
    public static function updatedBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): static
    {
        return new static('updated_at', $startDate, $endDate);
    }

    /**
     * Create criteria for any date field.
     * 
     * @param string $field
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return static
     */
    public static function between(string $field, \DateTimeInterface $startDate, \DateTimeInterface $endDate): static
    {
        return new static($field, $startDate, $endDate);
    }
}