<?php

namespace App\Filament\Resources\Pages\Concerns;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;

trait HasContainedSuperadminSurface
{
    public function getFormContentComponent(): Component
    {
        return $this->wrapSuperadminSurface(parent::getFormContentComponent());
    }

    public function getInfolistContentComponent(): Component
    {
        return $this->wrapSuperadminSurface(parent::getInfolistContentComponent());
    }

    protected function wrapSuperadminSurface(Component $component): Component
    {
        if (! $this->shouldWrapSuperadminSurface()) {
            return $component;
        }

        return Section::make()
            ->schema([$component])
            ->extraAttributes([
                'data-superadmin-surface' => 'true',
            ]);
    }

    protected function shouldWrapSuperadminSurface(): bool
    {
        return true;
    }
}
