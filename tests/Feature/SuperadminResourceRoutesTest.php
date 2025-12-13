<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class SuperadminResourceRoutesTest extends TestCase
{
    public function test_filament_alias_destroy_routes_exist(): void
    {
        $this->assertNotEmpty(route('filament.admin.resources.buildings.destroy', ['building' => 1]));
        $this->assertNotEmpty(route('filament.admin.resources.properties.destroy', ['property' => 1]));
        $this->assertNotEmpty(route('filament.admin.resources.invoices.destroy', ['invoice' => 1]));
        $this->assertNotEmpty(route('filament.admin.resources.tenants.destroy', ['tenant' => 1]));
        $this->assertNotEmpty(route('filament.admin.resources.users.destroy', ['user' => 1]));
    }
}
