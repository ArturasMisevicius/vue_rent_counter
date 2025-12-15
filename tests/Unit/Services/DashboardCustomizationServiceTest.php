<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\DashboardCustomization;
use App\Models\User;
use App\Services\DashboardCustomizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardCustomizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardCustomizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DashboardCustomizationService::class);
        Cache::flush();
    }

    /** @test */
    public function get_user_configuration_returns_default_for_new_user(): void
    {
        $user = User::factory()->create();

        $configuration = $this->service->getUserConfiguration($user);

        $this->assertArrayHasKey('widgets', $configuration);
        $this->assertArrayHasKey('layout', $configuration);
        $this->assertEquals(DashboardCustomization::getDefaultConfiguration(), $configuration);
    }

    /** @test */
    public function get_user_configuration_returns_saved_configuration(): void
    {
        $user = User::factory()->create();
        $customConfig = [
            'widgets' => [
                ['class' => 'TestWidget', 'position' => 1, 'size' => 'large']
            ],
            'layout' => ['columns' => 2]
        ];

        DashboardCustomization::create([
            'user_id' => $user->id,
            'widget_configuration' => $customConfig['widgets'],
            'layout_configuration' => $customConfig['layout'],
        ]);

        $configuration = $this->service->getUserConfiguration($user);

        $this->assertEquals($customConfig['widgets'], $configuration['widgets']);
        $this->assertEquals($customConfig['layout'], $configuration['layout']);
    }

    /** @test */
    public function save_user_configuration_creates_new_record(): void
    {
        $user = User::factory()->create();
        $configuration = [
            'widgets' => [
                ['class' => 'TestWidget', 'position' => 1, 'size' => 'medium']
            ],
            'layout' => ['columns' => 3]
        ];

        $result = $this->service->saveUserConfiguration($user, $configuration);

        $this->assertTrue($result);
        $this->assertDatabaseHas('dashboard_customizations', [
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function save_user_configuration_updates_existing_record(): void
    {
        $user = User::factory()->create();
        
        // Create initial customization
        DashboardCustomization::create([
            'user_id' => $user->id,
            'widget_configuration' => [['class' => 'OldWidget']],
        ]);

        $newConfiguration = [
            'widgets' => [
                ['class' => 'NewWidget', 'position' => 1, 'size' => 'large']
            ],
            'layout' => ['columns' => 2]
        ];

        $result = $this->service->saveUserConfiguration($user, $newConfiguration);

        $this->assertTrue($result);
        
        $customization = DashboardCustomization::where('user_id', $user->id)->first();
        $this->assertEquals($newConfiguration['widgets'], $customization->widget_configuration);
        $this->assertEquals($newConfiguration['layout'], $customization->layout_configuration);
    }

    /** @test */
    public function add_widget_adds_new_widget_successfully(): void
    {
        $user = User::factory()->create();

        // Start from an empty dashboard so we can add an available widget.
        $this->service->saveUserConfiguration($user, [
            'widgets' => [],
            'layout' => [],
        ]);

        $widgetClass = 'App\\Filament\\Widgets\\SubscriptionStatsWidget';

        $result = $this->service->addWidget($user, $widgetClass, [
            'size' => 'large',
            'refresh_interval' => 120
        ]);

        $this->assertTrue($result);
        
        $configuration = $this->service->getUserConfiguration($user);
        $this->assertCount(1, $configuration['widgets']);
        $this->assertEquals($widgetClass, $configuration['widgets'][0]['class']);
        $this->assertEquals('large', $configuration['widgets'][0]['size']);
        $this->assertEquals(120, $configuration['widgets'][0]['refresh_interval']);
    }

    /** @test */
    public function add_widget_fails_for_unavailable_widget(): void
    {
        $user = User::factory()->create();

        $result = $this->service->addWidget($user, 'NonExistentWidget');

        $this->assertFalse($result);
    }

    /** @test */
    public function add_widget_fails_for_duplicate_widget(): void
    {
        $user = User::factory()->create();

        $this->service->saveUserConfiguration($user, [
            'widgets' => [],
            'layout' => [],
        ]);

        $widgetClass = 'App\\Filament\\Widgets\\SubscriptionStatsWidget';

        // Add widget first time
        $this->service->addWidget($user, $widgetClass);
        
        // Try to add same widget again
        $result = $this->service->addWidget($user, $widgetClass);

        $this->assertFalse($result);
    }

    /** @test */
    public function remove_widget_removes_existing_widget(): void
    {
        $user = User::factory()->create();
        
        $configuration = [
            'widgets' => [
                ['class' => 'Widget1', 'position' => 1],
                ['class' => 'Widget2', 'position' => 2],
            ],
            'layout' => []
        ];
        
        $this->service->saveUserConfiguration($user, $configuration);

        $result = $this->service->removeWidget($user, 'Widget1');

        $this->assertTrue($result);
        
        $updatedConfiguration = $this->service->getUserConfiguration($user);
        $this->assertCount(1, $updatedConfiguration['widgets']);
        $this->assertEquals('Widget2', $updatedConfiguration['widgets'][0]['class']);
    }

    /** @test */
    public function remove_widget_fails_for_non_existent_widget(): void
    {
        $user = User::factory()->create();

        $result = $this->service->removeWidget($user, 'NonExistentWidget');

        $this->assertFalse($result);
    }

    /** @test */
    public function rearrange_widgets_updates_positions(): void
    {
        $user = User::factory()->create();
        
        $configuration = [
            'widgets' => [
                ['class' => 'Widget1', 'position' => 1],
                ['class' => 'Widget2', 'position' => 2],
                ['class' => 'Widget3', 'position' => 3],
            ],
            'layout' => []
        ];
        
        $this->service->saveUserConfiguration($user, $configuration);

        $newPositions = [
            'Widget1' => 3,
            'Widget2' => 1,
            'Widget3' => 2,
        ];

        $result = $this->service->rearrangeWidgets($user, $newPositions);

        $this->assertTrue($result);
        
        $updatedConfiguration = $this->service->getUserConfiguration($user);
        $widgets = $updatedConfiguration['widgets'];
        
        // Should be sorted by position
        $this->assertEquals('Widget2', $widgets[0]['class']);
        $this->assertEquals(1, $widgets[0]['position']);
        $this->assertEquals('Widget3', $widgets[1]['class']);
        $this->assertEquals(2, $widgets[1]['position']);
        $this->assertEquals('Widget1', $widgets[2]['class']);
        $this->assertEquals(3, $widgets[2]['position']);
    }

    /** @test */
    public function update_widget_size_updates_size_successfully(): void
    {
        $user = User::factory()->create();
        
        $configuration = [
            'widgets' => [
                ['class' => 'TestWidget', 'position' => 1, 'size' => 'small'],
            ],
            'layout' => []
        ];
        
        $this->service->saveUserConfiguration($user, $configuration);

        $result = $this->service->updateWidgetSize($user, 'TestWidget', 'large');

        $this->assertTrue($result);
        
        $updatedConfiguration = $this->service->getUserConfiguration($user);
        $this->assertEquals('large', $updatedConfiguration['widgets'][0]['size']);
    }

    /** @test */
    public function update_widget_size_fails_for_invalid_size(): void
    {
        $user = User::factory()->create();

        $result = $this->service->updateWidgetSize($user, 'TestWidget', 'invalid');

        $this->assertFalse($result);
    }

    /** @test */
    public function update_widget_refresh_interval_updates_interval(): void
    {
        $user = User::factory()->create();
        
        $configuration = [
            'widgets' => [
                ['class' => 'TestWidget', 'position' => 1, 'refresh_interval' => 60],
            ],
            'layout' => []
        ];
        
        $this->service->saveUserConfiguration($user, $configuration);

        $result = $this->service->updateWidgetRefreshInterval($user, 'TestWidget', 120);

        $this->assertTrue($result);
        
        $updatedConfiguration = $this->service->getUserConfiguration($user);
        $this->assertEquals(120, $updatedConfiguration['widgets'][0]['refresh_interval']);
    }

    /** @test */
    public function update_widget_refresh_interval_fails_for_invalid_interval(): void
    {
        $user = User::factory()->create();

        $result = $this->service->updateWidgetRefreshInterval($user, 'TestWidget', 5); // Too low
        $this->assertFalse($result);

        $result = $this->service->updateWidgetRefreshInterval($user, 'TestWidget', 4000); // Too high
        $this->assertFalse($result);
    }

    /** @test */
    public function toggle_widget_changes_enabled_state(): void
    {
        $user = User::factory()->create();
        
        $configuration = [
            'widgets' => [
                ['class' => 'TestWidget', 'position' => 1, 'enabled' => true],
            ],
            'layout' => []
        ];
        
        $this->service->saveUserConfiguration($user, $configuration);

        $result = $this->service->toggleWidget($user, 'TestWidget');

        $this->assertTrue($result);
        
        $updatedConfiguration = $this->service->getUserConfiguration($user);
        $this->assertFalse($updatedConfiguration['widgets'][0]['enabled']);

        // Toggle again
        $this->service->toggleWidget($user, 'TestWidget');
        $updatedConfiguration = $this->service->getUserConfiguration($user);
        $this->assertTrue($updatedConfiguration['widgets'][0]['enabled']);
    }

    /** @test */
    public function reset_to_default_removes_customization(): void
    {
        $user = User::factory()->create();
        
        // Create customization
        DashboardCustomization::create([
            'user_id' => $user->id,
            'widget_configuration' => [['class' => 'TestWidget']],
        ]);

        $result = $this->service->resetToDefault($user);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('dashboard_customizations', [
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function export_configuration_returns_current_configuration(): void
    {
        $user = User::factory()->create();
        
        $configuration = [
            'widgets' => [['class' => 'TestWidget']],
            'layout' => ['columns' => 2]
        ];
        
        $this->service->saveUserConfiguration($user, $configuration);

        $exported = $this->service->exportConfiguration($user);

        $this->assertEquals($configuration['widgets'], $exported['widgets']);
        $this->assertEquals($configuration['layout'], $exported['layout']);
    }

    /** @test */
    public function get_enabled_widgets_returns_only_enabled_widgets_in_order(): void
    {
        $user = User::factory()->create();
        
        $configuration = [
            'widgets' => [
                ['class' => 'Widget1', 'position' => 3, 'enabled' => true],
                ['class' => 'Widget2', 'position' => 1, 'enabled' => false],
                ['class' => 'Widget3', 'position' => 2, 'enabled' => true],
            ],
            'layout' => []
        ];
        
        $this->service->saveUserConfiguration($user, $configuration);

        $enabledWidgets = $this->service->getEnabledWidgets($user);

        $this->assertEquals(['Widget3', 'Widget1'], $enabledWidgets);
    }
}
