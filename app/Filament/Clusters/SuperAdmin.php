<?php

declare(strict_types=1);

namespace App\Filament\Clusters;

use App\Filament\Clusters\SuperAdmin\Pages;
use Filament\Clusters\Cluster;
use Illuminate\Support\Facades\Gate;

final class SuperAdmin extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationLabel = 'Super Admin';
    
    protected static ?int $navigationSort = 1000;
    
    protected static ?string $slug = 'super-admin';
    
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
    
    public static function getNavigationLabel(): string
    {
        return __('superadmin.navigation.cluster');
    }
    
    public static function getNavigationGroup(): ?string
    {
        return __('superadmin.navigation.group');
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
    
    public static function getPages(): array
    {
        return [
            'dashboard' => Pages\Dashboard::class,
        ];
    }
}