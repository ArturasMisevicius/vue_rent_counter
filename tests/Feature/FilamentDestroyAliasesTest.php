<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class FilamentDestroyAliasesTest extends TestCase
{
    public function test_superadmin_tenant_destroy_alias_is_registered(): void
    {
        $this->assertNotEmpty(route('filament.admin.resources.tenants.destroy', ['tenant' => 1]));
    }

    public function test_superadmin_user_destroy_alias_is_registered(): void
    {
        $this->assertNotEmpty(route('filament.admin.resources.users.destroy', ['user' => 1]));
    }
}
