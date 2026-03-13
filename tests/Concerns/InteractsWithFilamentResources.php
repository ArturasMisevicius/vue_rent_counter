<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;

/**
 * Trait for testing Filament resources
 */
trait InteractsWithFilamentResources
{
    /**
     * Assert that records can be listed in table
     */
    protected function assertCanListRecords(string $resource, array $records): void
    {
        $listPage = $resource::getPages()['index'];

        Livewire::test($listPage)
            ->assertCanSeeTableRecords($records);
    }

    /**
     * Assert tenant isolation in Filament table
     */
    protected function assertTenantIsolationInTable(string $resource): void
    {
        $modelClass = $resource::getModel();
        
        $ownRecord = $this->createTenantRecord($modelClass);
        $otherRecord = $this->createOtherTenantRecord($modelClass);

        $listPage = $resource::getPages()['index'];

        Livewire::test($listPage)
            ->assertCanSeeTableRecords([$ownRecord])
            ->assertCanNotSeeTableRecords([$otherRecord]);
    }

    /**
     * Assert that form can be filled and submitted
     */
    protected function assertCanFillForm(string $resource, array $data): void
    {
        $createPage = $resource::getPages()['create'];

        Livewire::test($createPage)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();
    }

    /**
     * Assert that form validation works
     */
    protected function assertFormValidation(string $resource, array $invalidData, array $expectedErrors): void
    {
        $createPage = $resource::getPages()['create'];

        Livewire::test($createPage)
            ->fillForm($invalidData)
            ->call('create')
            ->assertHasFormErrors($expectedErrors);
    }

    /**
     * Assert that record can be edited
     */
    protected function assertCanEditRecord(string $resource, Model $record, array $newData): void
    {
        $editPage = $resource::getPages()['edit'];

        Livewire::test($editPage, ['record' => $record->getRouteKey()])
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoFormErrors();

        foreach ($newData as $key => $value) {
            expect($record->fresh()->$key)->toBe($value);
        }
    }

    /**
     * Assert that record can be deleted
     */
    protected function assertCanDeleteRecord(string $resource, Model $record): void
    {
        $editPage = $resource::getPages()['edit'];

        Livewire::test($editPage, ['record' => $record->getRouteKey()])
            ->callAction('delete');

        $this->assertSoftDeleted($record);
    }

    /**
     * Assert that bulk actions work
     */
    protected function assertCanBulkDelete(string $resource, array $records): void
    {
        $listPage = $resource::getPages()['index'];

        Livewire::test($listPage)
            ->callTableBulkAction('delete', $records);

        foreach ($records as $record) {
            $this->assertSoftDeleted($record);
        }
    }

    /**
     * Assert that table can be searched
     */
    protected function assertCanSearchTable(string $resource, string $searchTerm, array $expectedRecords): void
    {
        $listPage = $resource::getPages()['index'];

        Livewire::test($listPage)
            ->searchTable($searchTerm)
            ->assertCanSeeTableRecords($expectedRecords);
    }

    /**
     * Assert that table can be sorted
     */
    protected function assertCanSortTable(string $resource, string $column, array $expectedOrder): void
    {
        $listPage = $resource::getPages()['index'];

        Livewire::test($listPage)
            ->sortTable($column)
            ->assertCanSeeTableRecords($expectedOrder, inOrder: true);
    }

    /**
     * Assert that table can be filtered
     */
    protected function assertCanFilterTable(string $resource, string $filter, $value, array $expectedRecords): void
    {
        $listPage = $resource::getPages()['index'];

        Livewire::test($listPage)
            ->filterTable($filter, $value)
            ->assertCanSeeTableRecords($expectedRecords);
    }

    /**
     * Assert that navigation is visible for authorized users
     */
    protected function assertNavigationVisible(string $resource): void
    {
        expect($resource::shouldRegisterNavigation())->toBeTrue();
    }

    /**
     * Assert that navigation is hidden for unauthorized users
     */
    protected function assertNavigationHidden(string $resource): void
    {
        expect($resource::shouldRegisterNavigation())->toBeFalse();
    }

    /**
     * Assert that tenant_id is automatically set on create
     */
    protected function assertTenantIdAutoSet(string $resource, array $data): void
    {
        $createPage = $resource::getPages()['create'];
        $modelClass = $resource::getModel();

        Livewire::test($createPage)
            ->fillForm($data)
            ->call('create');

        $record = $modelClass::latest()->first();

        expect($record->tenant_id)->toBe(auth()->user()->tenant_id);
    }

    /**
     * Assert that tenant_id cannot be changed on update
     */
    protected function assertTenantIdImmutableInForm(string $resource, Model $record): void
    {
        $editPage = $resource::getPages()['edit'];
        $originalTenantId = $record->tenant_id;

        Livewire::test($editPage, ['record' => $record->getRouteKey()])
            ->fillForm(['name' => 'Updated Name'])
            ->call('save');

        expect($record->fresh()->tenant_id)->toBe($originalTenantId);
    }
}
