<?php

if (! class_exists(\Filament\Forms\Form::class) && class_exists(\Filament\Schemas\Schema::class)) {
    class_alias(\Filament\Schemas\Schema::class, \Filament\Forms\Form::class);
}

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Pest 3.x configuration API
pest()->extends(TestCase::class)->use(RefreshDatabase::class)->in('Feature', 'Unit');
