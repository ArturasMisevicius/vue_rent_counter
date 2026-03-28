<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManagerPermission extends Model
{
    use HasFactory;
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'user_id',
        'resource',
        'can_create',
        'can_edit',
        'can_delete',
    ];

    protected function casts(): array
    {
        return [
            'can_create' => 'boolean',
            'can_edit' => 'boolean',
            'can_delete' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function for(User $user, Organization $organization, string $resource): self
    {
        return static::query()->firstOrNew(
            [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'resource' => $resource,
            ],
            [
                'can_create' => false,
                'can_edit' => false,
                'can_delete' => false,
            ],
        );
    }

    /**
     * @return Collection<string, self>
     */
    public static function allForManager(User $user, Organization $organization): Collection
    {
        /** @var Collection<string, self> $permissions */
        $permissions = static::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('resource');

        return $permissions;
    }

    /**
     * @param  array<string, array{can_create?: bool, can_edit?: bool, can_delete?: bool}>  $permissions
     */
    public static function syncForManager(User $user, Organization $organization, array $permissions): void
    {
        $existingRows = static::query()
            ->select(['id', 'resource'])
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('resource');

        $upsertRows = [];
        $resourcesToDelete = [];
        $timestamp = now();

        foreach ($permissions as $resource => $flags) {
            $canCreate = (bool) ($flags['can_create'] ?? false);
            $canEdit = (bool) ($flags['can_edit'] ?? false);
            $canDelete = (bool) ($flags['can_delete'] ?? false);

            if (! $canCreate && ! $canEdit && ! $canDelete) {
                $resourcesToDelete[] = $resource;

                continue;
            }

            $upsertRows[] = [
                'id' => $existingRows->get($resource)?->getKey() ?? Str::ulid()->toBase32(),
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'resource' => $resource,
                'can_create' => $canCreate,
                'can_edit' => $canEdit,
                'can_delete' => $canDelete,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::transaction(function () use ($organization, $resourcesToDelete, $upsertRows, $user): void {
            if ($resourcesToDelete !== []) {
                static::query()
                    ->where('organization_id', $organization->id)
                    ->where('user_id', $user->id)
                    ->whereIn('resource', $resourcesToDelete)
                    ->delete();
            }

            if ($upsertRows === []) {
                return;
            }

            static::query()->upsert(
                $upsertRows,
                ['organization_id', 'user_id', 'resource'],
                ['can_create', 'can_edit', 'can_delete', 'updated_at'],
            );
        });
    }
}
