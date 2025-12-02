# Design System Architecture

## Overview

This document provides architectural guidance for the daisyUI 4.x design system integration into the Vilnius Utilities Billing platform.

## 1. HIGH-LEVEL ASSESSMENT

### Impact Analysis

**Layers Affected:**
- **Presentation Layer**: Major impact - new daisyUI component system
- **View Layer**: Blade components with no `@php` blocks (blade-guardrails compliant)
- **Asset Pipeline**: Vite + Tailwind configuration changes
- **Testing Layer**: New visual regression and accessibility testing needs

**Boundaries:**
- ✅ **Clean Separation**: daisyUI for tenant-facing views, Filament 4.x remains independent
- ✅ **No Backend Impact**: Pure frontend change, no service/model/policy modifications
- ⚠️ **Potential Coupling**: CDN-based Tailwind/Alpine creates external dependency

**Coupling Considerations:**
- daisyUI 4.x version lock required
- Tailwind 4.x compatibility must be maintained
- Alpine.js CDN dependency (consider bundling for production)

## 2. RECOMMENDED PATTERNS

### View Component Pattern

All UI components follow the Blade Component pattern:

```php
// app/View/Components/Ui/Button.php
namespace App\View\Components\Ui;

use Illuminate\View\Component;

class Button extends Component
{
    public function __construct(
        public string $variant = 'default',
        public string $size = 'md',
        public bool $outline = false,
        public bool $loading = false,
        public bool $disabled = false,
        public string $type = 'button',
    ) {}

    public function render()
    {
        return view('components.ui.button');
    }
    
    public function classes(): string
    {
        // Logic moved to component class (blade-guardrails compliant)
        $classes = ['btn'];
        
        $variants = [
            'primary' => 'btn-primary',
            'secondary' => 'btn-secondary',
            // ...
        ];
        
        if (isset($variants[$this->variant])) {
            $classes[] = $variants[$this->variant];
        }
        
        return implode(' ', $classes);
    }
}
```

### View Composer Pattern

Theme management uses View Composers:

```php
// app/View/Composers/ThemeComposer.php
namespace App\View\Composers;

use Illuminate\View\View;

class ThemeComposer
{
    public function compose(View $view): void
    {
        $theme = session('theme', 'light');
        $view->with('currentTheme', $theme);
    }
}
```

### Service Provider Registration

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    View::composer('*', ThemeComposer::class);
}
```

## 3. SCALABILITY & PERFORMANCE

### Asset Optimization

**Bundle Size Management:**
- daisyUI adds ~30KB gzipped
- Use Tailwind purging to remove unused styles
- Consider code splitting for admin vs tenant views

**Caching Strategy:**
```php
// config/view.php
'compiled' => env(
    'VIEW_COMPILED_PATH',
    realpath(storage_path('framework/views'))
),
```

**CDN vs Bundled:**
- **Development**: CDN for faster iteration
- **Production**: Bundle with Vite for reliability

### N+1 Query Prevention

No database queries introduced by design system. All styling is CSS-based.

### Performance Monitoring

```php
// Add to monitoring
'design_system' => [
    'css_load_time' => 'metric',
    'component_render_time' => 'metric',
    'theme_switch_time' => 'metric',
],
```

## 4. SECURITY, ACCESSIBILITY & LOCALIZATION

### Security

**CSP Headers:**
```php
// config/security.php
'csp' => [
    'style-src' => [
        "'self'",
        "'unsafe-inline'", // Required for daisyUI
        'https://cdn.jsdelivr.net', // If using CDN
    ],
    'script-src' => [
        "'self'",
        'https://cdn.jsdelivr.net', // Alpine.js CDN
    ],
],
```

**XSS Prevention:**
- All user input escaped via Blade `{{ }}` syntax
- No `{!! !!}` raw output in components
- Component props validated in constructor

### Accessibility (WCAG 2.1 AA)

**Built-in Features:**
- Keyboard navigation (all interactive elements)
- Screen reader support (ARIA attributes)
- Color contrast ratios (4.5:1 minimum)
- Focus indicators (visible on all elements)

**Testing Requirements:**
```php
// tests/Feature/Accessibility/DesignSystemTest.php
it('maintains WCAG AA color contrast', function () {
    $this->get(route('dashboard'))
        ->assertOk();
    
    // Verify color contrast ratios
    // Use axe-core or similar tool
});

it('supports keyboard navigation', function () {
    $this->get(route('dashboard'))
        ->assertSee('tabindex', false);
});
```

### Localization

**Translation Keys:**
```php
// All component text must use translations
<x-ui.button>
    {{ __('actions.save') }}
</x-ui.button>
```

**RTL Support:**
```javascript
// tailwind.config.js
module.exports = {
    plugins: [
        require('daisyui'),
        require('@tailwindcss/rtl'),
    ],
}
```

## 5. DATA MODEL IMPLICATIONS

### User Preferences

**Migration Required:**
```php
// database/migrations/YYYY_MM_DD_add_theme_to_users.php
Schema::table('users', function (Blueprint $table) {
    $table->json('preferences')->nullable()->after('remember_token');
});

// Add index for performance
$table->index('preferences->theme');
```

**Model Update:**
```php
// app/Models/User.php
protected $casts = [
    'preferences' => 'array',
];

public function getThemeAttribute(): string
{
    return $this->preferences['theme'] ?? 'light';
}
```

### No Other Database Changes

Design system is purely presentational - no other schema changes required.

## 6. TESTING PLAN

### Unit Tests

```php
// tests/Unit/View/Components/Ui/ButtonTest.php
it('generates correct CSS classes for primary variant', function () {
    $button = new Button(variant: 'primary');
    
    expect($button->classes())->toContain('btn-primary');
});

it('handles loading state', function () {
    $button = new Button(loading: true);
    
    expect($button->loading)->toBeTrue();
});
```

### Feature Tests

```php
// tests/Feature/DesignSystem/ComponentRenderingTest.php
it('renders button component correctly', function () {
    $view = $this->blade('<x-ui.button variant="primary">Test</x-ui.button>');
    
    $view->assertSee('Test');
    $view->assertSee('btn btn-primary', false);
});

it('applies theme correctly', function () {
    session(['theme' => 'dark']);
    
    $this->get(route('dashboard'))
        ->assertSee('data-theme="dark"', false);
});
```

### Visual Regression Tests

```bash
# Use Percy or Chromatic
npm run percy:snapshot

# Compare after changes
npm run percy:compare
```

### Accessibility Tests

```php
// tests/Feature/Accessibility/DesignSystemAccessibilityTest.php
it('passes axe accessibility audit', function () {
    $this->get(route('dashboard'))
        ->assertOk();
    
    // Run axe-core audit
    // Assert no violations
});
```

### Property Tests

```php
// tests/Feature/PropertyTests/DesignSystemPropertyTest.php
it('maintains tenant isolation with new design system', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    TenantContext::set($tenant1);
    
    $response = $this->get(route('dashboard'));
    
    // Verify no cross-tenant data leakage
    $response->assertDontSee($tenant2->name);
});
```

## 7. OBSERVABILITY

### Metrics to Track

```php
// config/monitoring.php
'design_system' => [
    'component_render_time' => [
        'threshold' => 50, // ms
        'alert' => true,
    ],
    'css_load_time' => [
        'threshold' => 200, // ms
        'alert' => true,
    ],
    'theme_switch_time' => [
        'threshold' => 100, // ms
        'alert' => false,
    ],
],
```

### Logging

```php
// Log theme switches for analytics
Log::info('Theme switched', [
    'user_id' => auth()->id(),
    'from_theme' => session('previous_theme'),
    'to_theme' => $theme,
    'timestamp' => now(),
]);
```

## 8. RISKS & TECHNICAL DEBT

### Identified Risks

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| CDN Dependency | High | Low | Bundle assets for production |
| daisyUI Breaking Changes | Medium | Medium | Version lock, test before upgrading |
| Filament Style Conflicts | Medium | Low | Separate styling, thorough testing |
| Performance Degradation | Medium | Low | Monitor bundle size, lazy load |
| Browser Compatibility | Low | Low | Define support matrix, test |

### Technical Debt

1. **CDN Dependencies**: Move to bundled assets for production
2. **Theme Persistence**: Implement user preference storage
3. **Component Library**: Create comprehensive component library
4. **Documentation**: Maintain component usage documentation
5. **Testing**: Expand visual regression test coverage

## 9. PRIORITIZED NEXT STEPS

### Immediate (Week 1)
1. ✅ Install daisyUI: `npm install -D daisyUI@latest`
2. ✅ Update Tailwind config
3. ✅ Create base components (Button, Card, Input)
4. ✅ Update layout files
5. ✅ Test with all user roles

### Short-term (Weeks 2-3)
6. Migrate existing components to daisyUI
7. Create comprehensive component library
8. Implement theme switcher
9. Add visual regression tests
10. Update documentation

### Medium-term (Weeks 4-5)
11. Optimize bundle size
12. Implement lazy loading
13. Add accessibility tests
14. Performance optimization
15. Cross-browser testing

### Long-term (Post-Launch)
16. Monitor performance metrics
17. Gather user feedback
18. Iterate on component library
19. Expand theme options
20. Continuous improvement

## 10. MIGRATION CHECKLIST

### Pre-Migration
- [ ] Review design system documentation
- [ ] Install dependencies
- [ ] Update configuration files
- [ ] Create base components
- [ ] Test in development

### Migration
- [ ] Phase 1: Foundation components (Buttons, Alerts, Badges)
- [ ] Phase 2: Layout components (Navigation, Breadcrumbs, Footer)
- [ ] Phase 3: Data display (Cards, Stats, Tables)
- [ ] Phase 4: Form components (Inputs, Selects, Textareas)
- [ ] Phase 5: Interactive (Modals, Dropdowns, Tabs)

### Post-Migration
- [ ] Visual regression testing
- [ ] Accessibility audit
- [ ] Performance testing
- [ ] Cross-browser testing
- [ ] Documentation update
- [ ] Team training
- [ ] Production deployment

## 11. ROLLBACK STRATEGY

### Rollback Triggers
- Critical functionality broken
- Performance degradation >20%
- Accessibility compliance failure
- Security vulnerability

### Rollback Procedure
1. Revert to previous Git commit
2. Deploy previous version
3. Clear CDN cache
4. Notify stakeholders
5. Investigate root cause

## 12. SUCCESS CRITERIA

### Technical
- ✅ All components migrated
- ✅ Zero accessibility regressions
- ✅ <5% performance impact
- ✅ All tests passing
- ✅ No critical bugs

### User Experience
- ✅ Consistent design across all interfaces
- ✅ Improved mobile experience
- ✅ Better accessibility
- ✅ Positive user feedback

### Business
- ✅ Reduced development time
- ✅ Improved maintainability
- ✅ Better quality
- ✅ Future-proof solution

---

**Document Version**: 1.0  
**Last Updated**: 2024-01-29  
**Next Review**: 2024-02-29
