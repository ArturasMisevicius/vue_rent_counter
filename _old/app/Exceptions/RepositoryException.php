<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Repository Exception
 * 
 * Custom exception for repository-related errors.
 * Provides structured error handling for data access operations.
 */
class RepositoryException extends Exception
{
    /**
     * Create a new repository exception.
     * 
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = 'Repository operation failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for model not found.
     * 
     * @param string $model
     * @param mixed $id
     * @return static
     */
    public static function modelNotFound(string $model, mixed $id): static
    {
        return new static("Model {$model} with ID {$id} not found");
    }

    /**
     * Create exception for invalid criteria.
     * 
     * @param array<string, mixed> $criteria
     * @return static
     */
    public static function invalidCriteria(array $criteria): static
    {
        $criteriaString = json_encode($criteria);
        return new static("Invalid search criteria: {$criteriaString}");
    }

    /**
     * Create exception for bulk operation failure.
     * 
     * @param string $operation
     * @param int $count
     * @return static
     */
    public static function bulkOperationFailed(string $operation, int $count): static
    {
        return new static("Bulk {$operation} operation failed for {$count} items");
    }
}