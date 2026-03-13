<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Models\User;

/**
 * Value object representing authorization context.
 * 
 * This encapsulates the context information needed for authorization decisions,
 * providing better type safety and reducing parameter passing complexity.
 */
final readonly class AuthorizationContext
{
    public function __construct(
        public User $user,
        public string $operation,
        public ?string $resourceType = null,
        public ?int $resourceId = null,
        public array $additionalData = []
    ) {}

    /**
     * Create context for a specific operation.
     * 
     * @param User $user The authenticated user
     * @param string $operation The operation being performed
     * @param array $additionalData Additional context data
     * @return self
     */
    public static function forOperation(User $user, string $operation, array $additionalData = []): self
    {
        return new self(
            user: $user,
            operation: $operation,
            additionalData: $additionalData
        );
    }

    /**
     * Create context for a resource operation.
     * 
     * @param User $user The authenticated user
     * @param string $operation The operation being performed
     * @param string $resourceType The resource type
     * @param int $resourceId The resource ID
     * @param array $additionalData Additional context data
     * @return self
     */
    public static function forResource(
        User $user, 
        string $operation, 
        string $resourceType, 
        int $resourceId, 
        array $additionalData = []
    ): self {
        return new self(
            user: $user,
            operation: $operation,
            resourceType: $resourceType,
            resourceId: $resourceId,
            additionalData: $additionalData
        );
    }

    /**
     * Get context data for logging.
     * 
     * @return array
     */
    public function toLogContext(): array
    {
        return [
            'user_id' => $this->user->id,
            'user_role' => $this->user->role->value,
            'user_tenant_id' => $this->user->tenant_id,
            'operation' => $this->operation,
            'resource_type' => $this->resourceType,
            'resource_id' => $this->resourceId,
            'additional_data' => $this->additionalData,
        ];
    }
}