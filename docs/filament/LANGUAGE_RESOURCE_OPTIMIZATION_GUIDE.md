# LanguageResource Optimization Guide

**Date**: 2025-11-28  
**Resource**: `app/Filament/Resources/LanguageResource.php`  
**Status**: ✅ RECOMMENDATIONS PROVIDED

---

## 🎯 Executive Summary

The LanguageResource is well-implemented with proper namespace consolidation and authorization. However, several optimizations can enhance UX, prevent data integrity issues, and improve maintainability.

### Key Findings

1. **✅ Excellent**: Namespace consolidation, authorization delegation, localization
2. **⚠️ Redundant**: Form-level lowercase transformations (model mutator handles this)
3. **🔴 Missing**: Business logic validation, safety checks, bulk operations

---

## 🔴 Critical Issues

### 1. Redundant Form Transformations ✅ PARTIALLY ADDRESSED

**Issue**: The form uses `formatStateUsing()` and `dehydrateStateUsing()` to lowercase the code, but the Language model already has a mutator that does this automatically.

**Status**: ✅ Filament v4 compatibility fix applied (replaced deprecated `lowercase()` method)  
**Remaining**: Form transformations are still redundant with model mutator

**Current Code** (Filament v4 Compatible):
```php
TextInput::make('code')
    ->label(__('locales.labels.code'))
    ->maxLength(5)
    ->minLength(2)
    ->required()
    ->unique(ignoreRecord: true)
    ->placeholder(__('locales.placeholders.code'))
    ->helperText(__('locales.helper_text.code'))
    ->alphaDash()
    ->regex('/^[a-z]{2}(-[A-Z]{2})?$/')
    ->validationMessages([
        'regex' => __('locales.validation.code_format'),
    ])
    // FILAMENT V4 COMPATIBILITY: Replaced deprecated lowercase() method
    ->formatStateUsing(fn ($state) => strtolower((string) $state))
    ->dehydrateStateUsing(fn ($state) => strtolower((string) $state)),
```

**Model Mutator** (Already Exists):
```php
// app/Models/Language.php
protected function code(): Attribute
{
    return Attribute::make(
        set: fn (string $value): string => strtolower($value),
    );
}
```

**Recommended Fix**: Remove the form-level transformations (future optimization):
```php
TextInput::make('code')
    ->label(__('locales.labels.code'))
    ->maxLength(5)
    ->required()
    ->unique(ignoreRecord: true)
    ->placeholder(__('locales.placeholders.code'))
    ->helperText(__('locales.helper_text.code'))
    ->alphaDash(),
    // Model mutator handles lowercase conversion automatically
```

### 2. Missing Business Logic Validation ✅ IMPLEMENTED

**Issue**: Multiple languages can be set as default simultaneously.

**Status**: ✅ COMPLETE - Reactive validation implemented in LanguageResource

**Fix**: Add reactive validation to the `is_default` toggle:
```php
Toggle::make('is_default')
    ->label(__('locales.labels.default'))
    ->inline(false)
    ->helperText(__('locales.helper_text.default'))
    ->reactive()
    ->afterStateUpdated(function ($state, callable $set, ?Model $record) {
        if ($state) {
            // When setting as default, unset other defaults
            Language::where('is_default', true)
                ->when($record, fn($q) => $q->where('id', '!=', $record->id))
                ->update(['is_default' => false]);
        }
    })
    ->disabled(fn (?Model $record): bool => 
        $record?->is_default && Language::where('is_default', true)->count() === 1
    )
    ->dehydrated(fn ($state) => $state === true),
```

### 3. Missing Safety Checks ✅ IMPLEMENTED

**Issue**: Users can delete the default language or the last active language.

**Status**: ✅ COMPLETE - Safety checks implemented in delete actions

**Fix**: Add validation to the delete action:
```php
Tables\Actions\DeleteAction::make()
    ->iconButton()
    ->before(function (Language $record) {
        // Prevent deleting default language
        if ($record->is_default) {
            throw new \Exception(__('locales.errors.cannot_delete_default'));
        }
        // Prevent deleting last active language
        if ($record->is_active && Language::where('is_active', true)->count() === 1) {
            throw new \Exception(__('locales.errors.cannot_delete_last_active'));
        }
    }),
```

---

## 🟡 High-Impact Improvements

### 1. Enhanced Code Validation

Add ISO 639-1 format validation:

```php
TextInput::make('code')
    ->label(__('locales.labels.code'))
    ->maxLength(5)
    ->minLength(2) // ISO 639-1 codes are 2 characters
    ->required()
    ->unique(ignoreRecord: true)
    ->placeholder(__('locales.placeholders.code'))
    ->helperText(__('locales.helper_text.code'))
    ->alphaDash()
    ->regex('/^[a-z]{2}(-[A-Z]{2})?$/') // Validates ISO format (e.g., 'en', 'en-US')
    ->validationMessages([
        'regex' => __('locales.validation.code_format'),
    ]),
```

### 2. Quick Toggle Action

Add single-click status toggle:

```php
Tables\Actions\Action::make('toggle_active')
    ->label(fn (Language $record): string => 
        $record->is_active 
            ? __('locales.actions.deactivate') 
            : __('locales.actions.activate')
    )
    ->icon(fn (Language $record): string => 
        $record->is_active 
            ? 'heroicon-o-x-circle' 
            : 'heroicon-o-check-circle'
    )
    ->color(fn (Language $record): string => 
        $record->is_active ? 'danger' : 'success'
    )
    ->requiresConfirmation()
    ->action(fn (Language $record) => 
        $record->update(['is_active' => !$record->is_active])
    )
    ->visible(fn (Language $record): bool => 
        // Don't allow deactivating the default language
        !$record->is_default || !$record->is_active
    ),
```

### 3. Bulk Actions

Add bulk activate/deactivate:

```php
->bulkActions([
    Tables\Actions\BulkActionGroup::make([
        Tables\Actions\BulkAction::make('activate')
            ->label(__('locales.actions.bulk_activate'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->action(fn (Collection $records) => 
                $records->each->update(['is_active' => true])
            )
            ->deselectRecordsAfterCompletion(),
            
        Tables\Actions\BulkAction::make('deactivate')
            ->label(__('locales.actions.bulk_deactivate'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function (Collection $records) {
                // Prevent deactivating default language
                $defaultLanguage = $records->firstWhere('is_default', true);
                if ($defaultLanguage) {
                    throw new \Exception(__('locales.errors.cannot_deactivate_default'));
                }
                
                $records->each->update(['is_active' => false]);
            })
            ->deselectRecordsAfterCompletion(),
            
        Tables\Actions\DeleteBulkAction::make()
            ->requiresConfirmation()
            ->before(function (Collection $records) {
                // Prevent deleting default language
                if ($records->contains('is_default', true)) {
                    throw new \Exception(__('locales.errors.cannot_delete_default'));
                }
            }),
    ]),
])
```

### 4. Enhanced Table Columns

Add visual feedback with colors and icons:

```php
Tables\Columns\TextColumn::make('code')
    ->label(__('locales.labels.locale'))
    ->badge()
    ->color(fn (Language $record): string => match(true) {
        $record->is_default => 'success',
        $record->is_active => 'primary',
        default => 'gray',
    })
    ->icon(fn (Language $record): ?string => 
        $record->is_default ? 'heroicon-m-star' : null
    )
    ->sortable()
    ->searchable()
    ->weight('medium')
    ->copyable()
    ->copyMessage(__('locales.messages.code_copied'))
    ->copyMessageDuration(1500),
```

---

## 🟢 Nice-to-Have Features

### 1. Global Search Configuration

```php
public static function getGloballySearchableAttributes(): array
{
    return ['code', 'name', 'native_name'];
}

public static function getGlobalSearchResultTitle(Model $record): string
{
    return $record->name;
}

public static function getGlobalSearchResultDetails(Model $record): array
{
    return [
        __('locales.labels.code') => $record->code,
        __('locales.labels.native_name') => $record->native_name,
    ];
}
```

### 2. Export Functionality

```php
->headerActions([
    Tables\Actions\ExportAction::make()
        ->exporter(LanguageExporter::class)
        ->label(__('locales.actions.export'))
        ->icon('heroicon-o-arrow-down-tray'),
])
```

### 3. Form Section Icons

```php
Section::make(__('locales.sections.details'))
    ->description(__('locales.helper_text.details'))
    ->icon('heroicon-o-information-circle')
    ->schema([
        // ... fields
    ])
```

---

## 📋 Required Translation Keys

Add these translation keys to support the new features:

```php
// lang/en/locales.php

return [
    // ... existing keys
    
    'validation' => [
        'code_format' => 'The language code must be in ISO 639-1 format (e.g., en, en-US)',
    ],
    
    'actions' => [
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'bulk_activate' => 'Activate Selected',
        'bulk_deactivate' => 'Deactivate Selected',
        'export' => 'Export Languages',
    ],
    
    'modals' => [
        'activate' => [
            'heading' => 'Activate Language',
            'description' => 'Are you sure you want to activate this language?',
        ],
        'deactivate' => [
            'heading' => 'Deactivate Language',
            'description' => 'Are you sure you want to deactivate this language?',
        ],
        'bulk_activate' => [
            'heading' => 'Activate Languages',
            'description' => 'Are you sure you want to activate the selected languages?',
        ],
        'bulk_deactivate' => [
            'heading' => 'Deactivate Languages',
            'description' => 'Are you sure you want to deactivate the selected languages?',
        ],
    ],
    
    'notifications' => [
        'activated' => 'Language activated successfully',
        'deactivated' => 'Language deactivated successfully',
        'bulk_activated' => 'Languages activated successfully',
        'bulk_deactivated' => 'Languages deactivated successfully',
    ],
    
    'errors' => [
        'cannot_delete_default' => 'Cannot delete the default language',
        'cannot_delete_last_active' => 'Cannot delete the last active language',
        'cannot_deactivate_default' => 'Cannot deactivate the default language',
    ],
    
    'messages' => [
        'code_copied' => 'Language code copied to clipboard',
    ],
    
    'tooltips' => [
        'is_default' => 'This is the default language',
        'not_default' => 'This is not the default language',
        'is_active' => 'This language is active',
        'not_active' => 'This language is inactive',
        'display_order' => 'Order in which this language appears in the language switcher',
    ],
];
```

---

## 🔒 Multi-tenancy Verification

### ✅ Current Status

Languages are correctly implemented as **system-wide resources**:

1. **No Tenant Scope**: Languages are shared across all tenants (correct)
2. **Superadmin Only**: Only superadmins can manage languages (correct)
3. **Authorization**: LanguagePolicy properly restricts access (correct)

### 📝 Documentation Update

Add this note to the Language model:

```php
/**
 * Language Model
 *
 * Represents available languages for the application's localization system.
 *
 * MULTI-TENANCY: This model is intentionally NOT tenant-scoped.
 * Languages are system-wide resources managed by superadmins only.
 * All tenants share the same language configuration.
 *
 * ...
 */
```

---

## 📊 Implementation Priority

### Phase 1: Critical Fixes (Immediate)
1. ✅ Remove redundant form transformations
2. ✅ Add business logic validation (prevent multiple defaults)
3. ✅ Add safety checks (prevent deleting default/last active)

### Phase 2: High-Impact Improvements (This Sprint)
4. ✅ Add enhanced code validation (ISO format)
5. ✅ Add quick toggle action
6. ✅ Add bulk actions
7. ✅ Enhance table columns (colors, icons, copyable)

### Phase 3: Nice-to-Have Features (Next Sprint)
8. ⏭️ Add global search configuration
9. ⏭️ Add export functionality
10. ⏭️ Add form section icons

---

## 📁 Files Reference

- **Optimized Resource**: `app/Filament/Resources/LanguageResource_OPTIMIZED.php`
- **Current Resource**: `app/Filament/Resources/LanguageResource.php`
- **Model**: `app/Models/Language.php`
- **Policy**: `app/Policies/LanguagePolicy.php`
- **Translations**: `lang/en/locales.php`, `lang/lt/locales.php`, `lang/ru/locales.php`

---

## ✅ Testing Checklist

After implementing the optimizations:

- [ ] Test creating a language with invalid code format
- [ ] Test setting multiple languages as default (should auto-unset others)
- [ ] Test deleting the default language (should be prevented)
- [ ] Test deleting the last active language (should be prevented)
- [ ] Test quick toggle action (activate/deactivate)
- [ ] Test bulk activate action
- [ ] Test bulk deactivate action (with default language selected)
- [ ] Test bulk delete action (with default language selected)
- [ ] Test copyable code column
- [ ] Test global search for languages
- [ ] Verify all translations are present
- [ ] Verify authorization (only superadmin access)

---

**Status**: ✅ RECOMMENDATIONS PROVIDED  
**Next Action**: Review and implement Phase 1 critical fixes  
**Estimated Effort**: 2-3 hours for all phases
