<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\UserRole;
use Illuminate\Http\Request;

/**
 * Create User DTO
 * 
 * Data transfer object for user creation.
 * Provides type safety and validation for user data.
 * 
 * @package App\DTOs
 */
final readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public UserRole $role,
        public int $tenantId,
        public ?int $propertyId = null,
        public ?int $parentUserId = null,
        public ?string $organizationName = null,
        public bool $isActive = true
    ) {}

    /**
     * Create DTO from HTTP request.
     *
     * @param Request $request
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            email: $request->input('email'),
            password: $request->input('password'),
            role: UserRole::from($request->input('role')),
            tenantId: (int) $request->input('tenant_id'),
            propertyId: $request->has('property_id') ? (int) $request->input('property_id') : null,
            parentUserId: $request->has('parent_user_id') ? (int) $request->input('parent_user_id') : null,
            organizationName: $request->input('organization_name'),
            isActive: $request->boolean('is_active', true)
        );
    }

    /**
     * Create DTO from array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            role: $data['role'] instanceof UserRole ? $data['role'] : UserRole::from($data['role']),
            tenantId: (int) $data['tenant_id'],
            propertyId: isset($data['property_id']) ? (int) $data['property_id'] : null,
            parentUserId: isset($data['parent_user_id']) ? (int) $data['parent_user_id'] : null,
            organizationName: $data['organization_name'] ?? null,
            isActive: $data['is_active'] ?? true
        );
    }

    /**
     * Convert to array for model creation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $this->role,
            'tenant_id' => $this->tenantId,
            'property_id' => $this->propertyId,
            'parent_user_id' => $this->parentUserId,
            'organization_name' => $this->organizationName,
            'is_active' => $this->isActive,
        ];
    }
}
