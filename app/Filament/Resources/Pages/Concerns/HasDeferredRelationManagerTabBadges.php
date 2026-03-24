<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages\Concerns;

use Filament\Resources\Pages\Enums\ContentTabPosition;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Arr;

trait HasDeferredRelationManagerTabBadges
{
    public function getRelationManagersContentComponent(): Component
    {
        $managers = $this->getRelationManagers();
        $hasCombinedRelationManagerTabsWithContent = $this->hasCombinedRelationManagerTabsWithContent();
        $ownerRecord = $this->getRecord();

        $managerLivewireData = ['ownerRecord' => $ownerRecord, 'pageClass' => static::class];

        if ($activeLocale = (property_exists($this, 'activeLocale') ? $this->activeLocale : null)) {
            $managerLivewireData['activeLocale'] = $activeLocale;
        }

        if ((count($managers) > 1) || $hasCombinedRelationManagerTabsWithContent) {
            $tabs = $this->getDeferredRelationManagerTabs(
                $managers,
                $hasCombinedRelationManagerTabsWithContent,
                $managerLivewireData,
                $ownerRecord,
            );

            return Tabs::make()
                ->key('relationManagerTabs')
                ->livewireProperty('activeRelationManager')
                ->contained(false)
                ->tabs($tabs);
        }

        if (empty($managers)) {
            return Group::make()->hidden();
        }

        $manager = Arr::first($managers);

        if ($manager instanceof RelationGroup) {
            $manager->ownerRecord($ownerRecord);
            $manager->pageClass(static::class);

            return Group::make(collect($manager->ownerRecord($ownerRecord)->pageClass(static::class)->getManagers())
                ->map(fn ($groupedManager, $groupedManagerKey): Livewire => Livewire::make(
                    $normalizedGroupedManagerClass = $this->normalizeRelationManagerClass($groupedManager),
                    [...$managerLivewireData, ...(($groupedManager instanceof RelationManagerConfiguration) ? [...$groupedManager->relationManager::getDefaultProperties(), ...$groupedManager->getProperties()] : $groupedManager::getDefaultProperties())],
                )->key("{$normalizedGroupedManagerClass}-{$groupedManagerKey}"))
                ->all());
        }

        return Livewire::make(
            $normalizedManagerClass = $this->normalizeRelationManagerClass($manager),
            [...$managerLivewireData, ...(($manager instanceof RelationManagerConfiguration) ? [...$manager->relationManager::getDefaultProperties(), ...$manager->getProperties()] : $manager::getDefaultProperties())],
        )->key($normalizedManagerClass);
    }

    protected function deferRelationManagerTabBadge(Tab $tab): Tab
    {
        return $tab->deferBadge();
    }

    /**
     * @param  array<string | int, string | RelationGroup | RelationManagerConfiguration>  $managers
     * @param  array<string, mixed>  $managerLivewireData
     * @return array<string | int, Tab>
     */
    protected function getDeferredRelationManagerTabs(
        array $managers,
        bool $hasCombinedRelationManagerTabsWithContent,
        array $managerLivewireData,
        mixed $ownerRecord,
    ): array {
        $tabs = $managers;

        if ($hasCombinedRelationManagerTabsWithContent) {
            match ($this->getContentTabPosition()) {
                ContentTabPosition::After => $tabs = array_merge($tabs, ['' => null]),
                default => $tabs = array_replace(['' => null], $tabs),
            };
        }

        return collect($tabs)
            ->map(function ($manager, string|int $tabKey) use ($hasCombinedRelationManagerTabsWithContent, $managerLivewireData, $ownerRecord): Tab {
                $tabKey = strval($tabKey);

                if (blank($tabKey) && $hasCombinedRelationManagerTabsWithContent) {
                    return $this->getContentTabComponent();
                }

                if ($manager instanceof RelationGroup) {
                    $manager->ownerRecord($ownerRecord);
                    $manager->pageClass(static::class);

                    return $this->deferRelationManagerTabBadge($manager->getTabComponent())
                        ->schema(fn (): array => collect($manager->getManagers())
                            ->map(fn ($groupedManager, $groupedManagerKey): Livewire => Livewire::make(
                                $normalizedGroupedManagerClass = $this->normalizeRelationManagerClass($groupedManager),
                                [...$managerLivewireData, ...(($groupedManager instanceof RelationManagerConfiguration) ? [...$groupedManager->relationManager::getDefaultProperties(), ...$groupedManager->getProperties()] : $groupedManager::getDefaultProperties())],
                            )->key("{$normalizedGroupedManagerClass}-{$groupedManagerKey}"))
                            ->all());
                }

                $normalizedManagerClass = $this->normalizeRelationManagerClass($manager);

                return $this->deferRelationManagerTabBadge($normalizedManagerClass::getTabComponent($ownerRecord, static::class))
                    ->schema(fn (): array => [
                        Livewire::make(
                            $normalizedManagerClass,
                            [...$managerLivewireData, ...(($manager instanceof RelationManagerConfiguration) ? [...$manager->relationManager::getDefaultProperties(), ...$manager->getProperties()] : $manager::getDefaultProperties())],
                        )->key($normalizedManagerClass),
                    ]);
            })
            ->all();
    }
}
