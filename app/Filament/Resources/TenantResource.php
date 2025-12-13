<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\HasTranslatedValidation;
use App\Filament\Resources\TenantResource\Pages;
use App\Filament\Resources\TenantResource\RelationManagers;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use BackedEnum;
use Filament\Forms;