<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\FaqResource;
use App\Models\Faq;
use App\Models\User;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Tests for FaqResource Filament 4 namespace consolidation.
 *
 * Validates that the resource correctly uses the consolidated
 * `use Filament\Tables;` pattern instead of individual imports.
 *
 * @see \App\Filament\Resources\FaqResource
 * @see docs/upgrades/FILAMENT_NAMESPACE_CONSOLIDATION.md
 */

beforeEach(function () {
    $this->superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
    $this->actingAs($this->superadmin);
});

describe('Namespace Consolidation', function () {
    test('resource uses consolidated Tables namespace for actions', function () {
        $table = FaqResource::table(Table::make(FaqResource::class));
        
        // Verify table is properly configured
        expect($table)->toBeInstanceOf(Table::class);
        
        // Actions should be configured (EditAction, DeleteAction)
        $actions = $table->getActions();
        expect($actions)->toHaveCount(2);
    });

    test('resource uses consolidated Tables namespace for columns', function () {
        $table = FaqResource::table(Table::make(FaqResource::class));
        
        // Verify columns are properly configured
        $columns = $table->getColumns();
        expect($columns)->toHaveCount(5)
            ->and($columns)->toHaveKeys([
                'question',
                'category',
                'is_published',
                'display_order',
                'updated_at',
            ]);
    });

    test('resource uses consolidated Tables namespace for filters', function () {
        $table = FaqResource::table(Table::make(FaqResource::class));
        
        // Verify filters are properly configured
        $filters = $table->getFilters();
        expect($filters)->toHaveCount(2)
            ->and($filters)->toHaveKeys([
                'is_published',
                'category',
            ]);
    });

    test('resource uses consolidated Tables namespace for bulk actions', function () {
        $table = FaqResource::table(Table::make(FaqResource::class));
        
        // Verify bulk actions are configured
        $bulkActions = $table->getBulkActions();
        expect($bulkActions)->not->toBeEmpty();
    });

    test('resource uses consolidated Tables namespace for empty state actions', function () {
        $table = FaqResource::table(Table::make(FaqResource::class));
        
        // Verify empty state is configured
        $emptyStateActions = $table->getEmptyStateActions();
        expect($emptyStateActions)->toHaveCount(1);
    });
});

describe('Table Actions', function () {
    test('edit action works with namespace consolidation', function () {
        $faq = Faq::factory()->create();
        
        $table = FaqResource::table(Table::make(FaqResource::class));
        $actions = $table->getActions();
        
        expect($actions)->toHaveKey('edit');
    });

    test('delete action works with namespace consolidation', function () {
        $faq = Faq::factory()->create();
        
        $table = FaqResource::table(Table::make(FaqResource::class));
        $actions = $table->getActions();
        
        expect($actions)->toHaveKey('delete');
    });

    test('bulk delete action works with namespace consolidation', function () {
        Faq::factory()->count(3)->create();
        
        $table = FaqResource::table(Table::make(FaqResource::class));
        $bulkActions = $table->getBulkActions();
        
        expect($bulkActions)->not->toBeEmpty();
    });

    test('create action in empty state works with namespace consolidation', function () {
        $table = FaqResource::table(Table::make(FaqResource::class));
        $emptyStateActions = $table->getEmptyStateActions();
        
        expect($emptyStateActions)->toHaveCount(1);
    });
});

describe('Table Columns', function () {
    test('TextColumn for question works with namespace consolidation', function () {
        $table = FaqResource::table(Table::make(FaqResource::class));
        $columns = $table->getColumns();
        
        expect($columns)->toHaveKey('question')
            ->and($columns['question'])->toBeInstanceOf(\Filament\Tables\Columns\TextColumn::class);
    });

    test('TextColumn for category works with namespace consolidation', function () {
        $table = FaqResource::table(Table::make(FaqResource::class));
        $columns = $table->getColumns();
        
        expect($columns)->toHaveKey('category')
            ->and($columns['category'])->toBeInstanceOf(\Filament\Tables\Columns\TextColumn::class);
    });

    test('IconColumn for is_published works with namespace consolidation', function () {
        $table = FaqResource::table(Table::make(FaqResource::class));
        $columns = $table->getColumns();
        
        expect($columns)->toHaveKey('is_published')
            ->and($columns['is_published'])->toBeInstanceOf(\Filament\Tables\Columns\IconColumn::class);
    });

    test('TextColumn for display_order works with namespace consolidation', function () {
        $table = FaqResource::table(Table::make(FaqResource::class));
        $columns = $table->getColumns();
        
        expect($columns)->toHaveKey('display_order')
            ->and($columns['display_order'])->toBeInstanceOf(\Filament\Tables\Columns\TextColumn::class);
    });

    test('TextColumn for updated_at works with namespace consolidation', function () {
        $table = FaqResource::table(Table::make(FaqResource::class));
        $columns = $table->getColumns();
        
        expect($columns)->toHaveKey('updated_at')
            ->and($columns['updated_at'])->toBeInstanceOf(\Filament\Tables\Columns\TextColumn::class);
    });
});

describe('Table Filters', function () {
    test('SelectFilter for is_published works with namespace consolidation', function () {
        $table = FaqResource::table(Table::make(FaqResource::class));
        $filters = $table->getFilters();
        
        expect($filters)->toHaveKey('is_published')
            ->and($filters['is_published'])->toBeInstanceOf(\Filament\Tables\Filters\SelectFilter::class);
    });

    test('SelectFilter for category works with namespace consolidation', function () {
        $table = FaqResource::table(Table::make(FaqResource::class));
        $filters = $table->getFilters();
        
        expect($filters)->toHaveKey('category')
            ->and($filters['category'])->toBeInstanceOf(\Filament\Tables\Filters\SelectFilter::class);
    });

    test('category filter options are populated correctly', function () {
        Faq::factory()->create(['category' => 'General']);
        Faq::factory()->create(['category' => 'Billing']);
        
        $table = FaqResource::table(Table::make(FaqResource::class));
        $filters = $table->getFilters();
        
        expect($filters)->toHaveKey('category');
    });
});

describe('Backward Compatibility', function () {
    test('resource maintains same functionality after namespace consolidation', function () {
        $faq = Faq::factory()->create([
            'question' => 'Test Question',
            'answer' => 'Test Answer',
            'category' => 'General',
            'is_published' => true,
            'display_order' => 1,
        ]);
        
        $table = FaqResource::table(Table::make(FaqResource::class));
        
        // Verify all components are working
        expect($table->getColumns())->toHaveCount(5)
            ->and($table->getFilters())->toHaveCount(2)
            ->and($table->getActions())->toHaveCount(2)
            ->and($table->getBulkActions())->not->toBeEmpty();
    });

    test('form schema still works after namespace consolidation', function () {
        $schema = FaqResource::form(Schema::make());
        
        expect($schema)->toBeInstanceOf(Schema::class);
    });

    test('pages are still registered after namespace consolidation', function () {
        $pages = FaqResource::getPages();
        
        expect($pages)->toHaveCount(3)
            ->and($pages)->toHaveKeys(['index', 'create', 'edit']);
    });

    test('authorization still works after namespace consolidation', function () {
        expect(FaqResource::canViewAny())->toBeTrue()
            ->and(FaqResource::canCreate())->toBeTrue();
        
        $faq = Faq::factory()->create();
        expect(FaqResource::canEdit($faq))->toBeTrue()
            ->and(FaqResource::canDelete($faq))->toBeTrue();
    });

    test('navigation registration still works after namespace consolidation', function () {
        expect(FaqResource::shouldRegisterNavigation())->toBeTrue();
        
        // Test with non-admin user
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $this->actingAs($manager);
        
        expect(FaqResource::shouldRegisterNavigation())->toBeFalse();
    });
});

describe('Performance', function () {
    test('namespace consolidation does not impact table render performance', function () {
        Faq::factory()->count(50)->create();
        
        $start = microtime(true);
        $table = FaqResource::table(Table::make(FaqResource::class));
        $duration = (microtime(true) - $start) * 1000;
        
        // Should render in under 100ms
        expect($duration)->toBeLessThan(100);
    });

    test('namespace consolidation does not impact memory usage', function () {
        Faq::factory()->count(50)->create();
        
        $memoryBefore = memory_get_usage(true);
        $table = FaqResource::table(Table::make(FaqResource::class));
        $memoryAfter = memory_get_usage(true);
        
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB
        
        // Should use less than 5MB
        expect($memoryUsed)->toBeLessThan(5);
    });
});

describe('Regression Prevention', function () {
    test('no individual action imports remain in resource', function () {
        $reflection = new ReflectionClass(FaqResource::class);
        $fileContent = file_get_contents($reflection->getFileName());
        
        // Verify no individual imports
        expect($fileContent)->not->toContain('use Filament\Tables\Actions\EditAction;')
            ->and($fileContent)->not->toContain('use Filament\Tables\Actions\DeleteAction;')
            ->and($fileContent)->not->toContain('use Filament\Tables\Actions\CreateAction;')
            ->and($fileContent)->not->toContain('use Filament\Tables\Actions\BulkActionGroup;')
            ->and($fileContent)->not->toContain('use Filament\Tables\Actions\DeleteBulkAction;');
    });

    test('no individual column imports remain in resource', function () {
        $reflection = new ReflectionClass(FaqResource::class);
        $fileContent = file_get_contents($reflection->getFileName());
        
        // Verify no individual imports
        expect($fileContent)->not->toContain('use Filament\Tables\Columns\TextColumn;')
            ->and($fileContent)->not->toContain('use Filament\Tables\Columns\IconColumn;');
    });

    test('no individual filter imports remain in resource', function () {
        $reflection = new ReflectionClass(FaqResource::class);
        $fileContent = file_get_contents($reflection->getFileName());
        
        // Verify no individual imports
        expect($fileContent)->not->toContain('use Filament\Tables\Filters\SelectFilter;');
    });

    test('consolidated Tables namespace is present', function () {
        $reflection = new ReflectionClass(FaqResource::class);
        $fileContent = file_get_contents($reflection->getFileName());
        
        // Verify consolidated import exists
        expect($fileContent)->toContain('use Filament\Tables;');
    });

    test('all table components use namespace prefix', function () {
        $reflection = new ReflectionClass(FaqResource::class);
        $fileContent = file_get_contents($reflection->getFileName());
        
        // Verify namespace prefix usage
        expect($fileContent)->toContain('Tables\Actions\EditAction')
            ->and($fileContent)->toContain('Tables\Actions\DeleteAction')
            ->and($fileContent)->toContain('Tables\Columns\TextColumn')
            ->and($fileContent)->toContain('Tables\Columns\IconColumn')
            ->and($fileContent)->toContain('Tables\Filters\SelectFilter');
    });
});
