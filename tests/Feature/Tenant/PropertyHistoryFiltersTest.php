<?php

namespace Tests\Feature\Tenant;

use Tests\TestCase;

class PropertyHistoryFiltersTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
