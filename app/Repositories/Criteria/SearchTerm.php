<?php

declare(strict_types=1);

namespace App\Repositories\Criteria;

use Illuminate\Database\Eloquent\Builder;

/**
 * Search Term Criteria
 * 
 * Filters query to search for a term across multiple fields.
 * Supports flexible field configuration and search operators.
 */
class SearchTerm implements CriteriaInterface
{
    /**
     * Create a new search term criteria.
     * 
     * @param string $searchTerm The term to search for
     * @param array<string> $fields Fields to search in
     * @param string $operator Search operator (LIKE, =, etc.)
     * @param bool $caseSensitive Whether search is case sensitive
     */
    public function __construct(
        private readonly string $searchTerm,
        private readonly array $fields,
        private readonly string $operator = 'LIKE',
        private readonly bool $caseSensitive = false
    ) {}

    /**
     * {@inheritDoc}
     */
    public function apply(Builder $query): Builder
    {
        if (empty($this->searchTerm) || empty($this->fields)) {
            return $query;
        }

        return $query->where(function (Builder $query) {
            foreach ($this->fields as $field) {
                $searchValue = $this->operator === 'LIKE' 
                    ? "%{$this->searchTerm}%" 
                    : $this->searchTerm;

                if ($this->caseSensitive) {
                    $query->orWhere($field, $this->operator, $searchValue);
                } else {
                    $query->orWhereRaw("LOWER({$field}) {$this->operator} LOWER(?)", [$searchValue]);
                }
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        $fieldsStr = implode(', ', $this->fields);
        $sensitivity = $this->caseSensitive ? 'case-sensitive' : 'case-insensitive';
        
        return "Search for '{$this->searchTerm}' in fields: {$fieldsStr} ({$sensitivity})";
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters(): array
    {
        return [
            'search_term' => $this->searchTerm,
            'fields' => $this->fields,
            'operator' => $this->operator,
            'case_sensitive' => $this->caseSensitive,
        ];
    }

    /**
     * Create criteria for user search (name and email).
     * 
     * @param string $searchTerm
     * @return static
     */
    public static function forUsers(string $searchTerm): static
    {
        return new static($searchTerm, ['name', 'email']);
    }

    /**
     * Create criteria for property search (address and unit_number).
     * 
     * @param string $searchTerm
     * @return static
     */
    public static function forProperties(string $searchTerm): static
    {
        return new static($searchTerm, ['address', 'unit_number']);
    }

    /**
     * Create criteria for invoice search (invoice_number and payment_reference).
     * 
     * @param string $searchTerm
     * @return static
     */
    public static function forInvoices(string $searchTerm): static
    {
        return new static($searchTerm, ['invoice_number', 'payment_reference']);
    }

    /**
     * Create exact match criteria.
     * 
     * @param string $searchTerm
     * @param array<string> $fields
     * @return static
     */
    public static function exactMatch(string $searchTerm, array $fields): static
    {
        return new static($searchTerm, $fields, '=', true);
    }

    /**
     * Create case-sensitive search criteria.
     * 
     * @param string $searchTerm
     * @param array<string> $fields
     * @return static
     */
    public static function caseSensitive(string $searchTerm, array $fields): static
    {
        return new static($searchTerm, $fields, 'LIKE', true);
    }
}