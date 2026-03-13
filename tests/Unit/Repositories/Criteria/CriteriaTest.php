<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Criteria;

use App\Enums\InvoiceStatus;
use App\Enums\PropertyType;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\User;
use App\Repositories\Criteria\ActiveUsers;
use App\Repositories\Criteria\DateRange;
use App\Repositories\Criteria\InvoicesByStatus;
use App\Repositories\Criteria\PropertiesByType;
use App\Repositories\Criteria\SearchTerm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Criteria Pattern Tests
 * 
 * Tests all criteria implementations to ensure they properly
 * filter queries and can be composed together.
 */
class CriteriaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function active_users_criteria_filters_correctly(): void
    {
        // Create test data
        User::factory()->count(3)->create(['is_active' => true]);
        User::factory()->count(2)->create(['is_active' => false]);

        $criteria = new ActiveUsers();
        $query = User::query();
        $filteredQuery = $criteria->apply($query);
        $users = $filteredQuery->get();

        $this->assertCount(3, $users);
        $users->each(function ($user) {
            $this->assertTrue($user->is_active);
        });

        // Test criteria description and parameters
        $this->assertEquals('Filter to include only active users', $criteria->getDescription());
        $this->assertEquals(['is_active' => true], $criteria->getParameters());
    }

    /** @test */
    public function date_range_criteria_filters_correctly(): void
    {
        $startDate = now()->subDays(7);
        $endDate = now()->subDays(1);

        // Create users within and outside the date range
        User::factory()->count(3)->create(['created_at' => now()->subDays(3)]);
        User::factory()->count(2)->create(['created_at' => now()->subDays(10)]);

        $criteria = DateRange::createdBetween($startDate, $endDate);
        $query = User::query();
        $filteredQuery = $criteria->apply($query);
        $users = $filteredQuery->get();

        $this->assertCount(3, $users);

        // Test static factory methods
        $createdCriteria = DateRange::createdBetween($startDate, $endDate);
        $this->assertInstanceOf(DateRange::class, $createdCriteria);

        $updatedCriteria = DateRange::updatedBetween($startDate, $endDate);
        $this->assertInstanceOf(DateRange::class, $updatedCriteria);

        $customCriteria = DateRange::between('custom_date', $startDate, $endDate);
        $this->assertInstanceOf(DateRange::class, $customCriteria);
    }

    /** @test */
    public function search_term_criteria_filters_correctly(): void
    {
        User::factory()->create(['name' => 'John Smith', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        User::factory()->create(['name' => 'Bob Johnson', 'email' => 'bob@example.com']);

        $criteria = SearchTerm::forUsers('john');
        $query = User::query();
        $filteredQuery = $criteria->apply($query);
        $users = $filteredQuery->get();

        $this->assertCount(2, $users); // John Smith and Bob Johnson

        // Test case sensitivity
        $caseSensitiveCriteria = SearchTerm::caseSensitive('John', ['name']);
        $query = User::query();
        $filteredQuery = $caseSensitiveCriteria->apply($query);
        $users = $filteredQuery->get();

        $this->assertCount(1, $users); // Only John Smith (exact case match)

        // Test exact match
        $exactCriteria = SearchTerm::exactMatch('John Smith', ['name']);
        $query = User::query();
        $filteredQuery = $exactCriteria->apply($query);
        $users = $filteredQuery->get();

        $this->assertCount(1, $users);
        $this->assertEquals('John Smith', $users->first()->name);
    }

    /** @test */
    public function invoices_by_status_criteria_filters_correctly(): void
    {
        Invoice::factory()->count(3)->create(['status' => InvoiceStatus::DRAFT]);
        Invoice::factory()->count(2)->create(['status' => InvoiceStatus::PAID]);
        Invoice::factory()->count(1)->create(['status' => InvoiceStatus::FINALIZED]);

        // Test single status
        $draftCriteria = InvoicesByStatus::drafts();
        $query = Invoice::query();
        $filteredQuery = $draftCriteria->apply($query);
        $invoices = $filteredQuery->get();

        $this->assertCount(3, $invoices);
        $invoices->each(function ($invoice) {
            $this->assertEquals(InvoiceStatus::DRAFT, $invoice->status);
        });

        // Test multiple statuses
        $unpaidCriteria = InvoicesByStatus::unpaid();
        $query = Invoice::query();
        $filteredQuery = $unpaidCriteria->apply($query);
        $invoices = $filteredQuery->get();

        $this->assertCount(4, $invoices); // 3 draft + 1 finalized

        // Test static factory methods
        $this->assertInstanceOf(InvoicesByStatus::class, InvoicesByStatus::paid());
        $this->assertInstanceOf(InvoicesByStatus::class, InvoicesByStatus::finalized());
        $this->assertInstanceOf(InvoicesByStatus::class, InvoicesByStatus::processable());
    }

    /** @test */
    public function properties_by_type_criteria_filters_correctly(): void
    {
        Property::factory()->count(3)->create(['type' => PropertyType::APARTMENT]);
        Property::factory()->count(2)->create(['type' => PropertyType::HOUSE]);
        Property::factory()->count(1)->create(['type' => PropertyType::OFFICE]);

        // Test single type
        $apartmentCriteria = PropertiesByType::apartments();
        $query = Property::query();
        $filteredQuery = $apartmentCriteria->apply($query);
        $properties = $filteredQuery->get();

        $this->assertCount(3, $properties);
        $properties->each(function ($property) {
            $this->assertEquals(PropertyType::APARTMENT, $property->type);
        });

        // Test multiple types (residential)
        $residentialCriteria = PropertiesByType::residential();
        $query = Property::query();
        $filteredQuery = $residentialCriteria->apply($query);
        $properties = $filteredQuery->get();

        $this->assertCount(5, $properties); // 3 apartments + 2 houses

        // Test static factory methods
        $this->assertInstanceOf(PropertiesByType::class, PropertiesByType::commercial());
        $this->assertInstanceOf(PropertiesByType::class, PropertiesByType::houses());
        $this->assertInstanceOf(PropertiesByType::class, PropertiesByType::offices());
    }

    /** @test */
    public function criteria_can_be_combined(): void
    {
        // Create test data
        User::factory()->create([
            'name' => 'John Active',
            'email' => 'john@example.com',
            'is_active' => true,
            'created_at' => now()->subDays(3),
        ]);
        User::factory()->create([
            'name' => 'John Inactive',
            'email' => 'john.inactive@example.com',
            'is_active' => false,
            'created_at' => now()->subDays(3),
        ]);
        User::factory()->create([
            'name' => 'Jane Active',
            'email' => 'jane@example.com',
            'is_active' => true,
            'created_at' => now()->subDays(10),
        ]);

        $query = User::query();

        // Apply multiple criteria
        $activeCriteria = new ActiveUsers();
        $query = $activeCriteria->apply($query);

        $searchCriteria = SearchTerm::forUsers('john');
        $query = $searchCriteria->apply($query);

        $dateCriteria = DateRange::createdBetween(now()->subDays(7), now());
        $query = $dateCriteria->apply($query);

        $users = $query->get();

        $this->assertCount(1, $users); // Only "John Active" matches all criteria
        $this->assertEquals('John Active', $users->first()->name);
    }

    /** @test */
    public function criteria_handles_empty_results(): void
    {
        // No users created, should return empty collection
        $criteria = new ActiveUsers();
        $query = User::query();
        $filteredQuery = $criteria->apply($query);
        $users = $filteredQuery->get();

        $this->assertCount(0, $users);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $users);
    }

    /** @test */
    public function search_term_criteria_handles_empty_search(): void
    {
        User::factory()->count(3)->create();

        // Empty search term should return all users
        $criteria = new SearchTerm('', ['name', 'email']);
        $query = User::query();
        $filteredQuery = $criteria->apply($query);
        $users = $filteredQuery->get();

        $this->assertCount(3, $users);
    }

    /** @test */
    public function search_term_criteria_handles_empty_fields(): void
    {
        User::factory()->count(3)->create();

        // Empty fields array should return all users
        $criteria = new SearchTerm('test', []);
        $query = User::query();
        $filteredQuery = $criteria->apply($query);
        $users = $filteredQuery->get();

        $this->assertCount(3, $users);
    }

    /** @test */
    public function criteria_descriptions_are_meaningful(): void
    {
        $activeCriteria = new ActiveUsers();
        $this->assertStringContainsString('active users', $activeCriteria->getDescription());

        $dateCriteria = DateRange::createdBetween(now()->subDay(), now());
        $this->assertStringContainsString('created_at', $dateCriteria->getDescription());
        $this->assertStringContainsString('between', $dateCriteria->getDescription());

        $searchCriteria = SearchTerm::forUsers('test');
        $this->assertStringContainsString('test', $searchCriteria->getDescription());
        $this->assertStringContainsString('name, email', $searchCriteria->getDescription());

        $statusCriteria = InvoicesByStatus::drafts();
        $this->assertStringContainsString('draft', $statusCriteria->getDescription());

        $typeCriteria = PropertiesByType::apartments();
        $this->assertStringContainsString('apartment', $typeCriteria->getDescription());
    }

    /** @test */
    public function criteria_parameters_are_accurate(): void
    {
        $activeCriteria = new ActiveUsers();
        $parameters = $activeCriteria->getParameters();
        $this->assertArrayHasKey('is_active', $parameters);
        $this->assertTrue($parameters['is_active']);

        $startDate = now()->subDay();
        $endDate = now();
        $dateCriteria = DateRange::createdBetween($startDate, $endDate);
        $parameters = $dateCriteria->getParameters();
        $this->assertArrayHasKey('field', $parameters);
        $this->assertArrayHasKey('start_date', $parameters);
        $this->assertArrayHasKey('end_date', $parameters);
        $this->assertEquals('created_at', $parameters['field']);

        $searchCriteria = SearchTerm::forUsers('test');
        $parameters = $searchCriteria->getParameters();
        $this->assertArrayHasKey('search_term', $parameters);
        $this->assertArrayHasKey('fields', $parameters);
        $this->assertArrayHasKey('operator', $parameters);
        $this->assertArrayHasKey('case_sensitive', $parameters);
        $this->assertEquals('test', $parameters['search_term']);
        $this->assertEquals(['name', 'email'], $parameters['fields']);
    }
}