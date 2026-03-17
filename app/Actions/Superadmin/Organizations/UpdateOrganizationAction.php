<?php

namespace App\Actions\Superadmin\Organizations;

use App\Http\Requests\Superadmin\Organizations\UpdateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UpdateOrganizationAction
{
    public function __invoke(Organization $organization, array $attributes): Organization
    {
        $data = Validator::make($attributes, UpdateOrganizationRequest::ruleset($organization))->validate();

        $organization->update([
            'name' => $data['name'],
            'slug' => $this->resolveSlug($organization, $data['slug'] ?? null, $data['name']),
            'status' => $data['status'],
        ]);

        return $organization->refresh();
    }

    private function resolveSlug(Organization $organization, ?string $slug, string $name): string
    {
        $baseSlug = Str::slug(filled($slug) ? $slug : $name);
        $resolvedSlug = $baseSlug;
        $suffix = 2;

        while (Organization::query()
            ->whereKeyNot($organization->id)
            ->where('slug', $resolvedSlug)
            ->exists()) {
            $resolvedSlug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $resolvedSlug;
    }
}
