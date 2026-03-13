# Blade Template Security Guidelines

## Overview

This document outlines security best practices for Blade templates in the Laravel 12 utilities billing platform, focusing on XSS prevention, secure data handling, and template injection protection.

## CSS Class Injection Prevention

### Secure Class Concatenation

**✅ CORRECT: Use string concatenation operator**

```blade
{{-- Safe: Pre-sanitized variables with string concatenation --}}
<span {{ $attributes->merge(['class' => 'base-classes ' . $dynamicClasses]) }}>

{{-- Safe: Using Laravel's @class directive --}}
<div @class([
    'base-class',
    'conditional-class' => $condition,
    $dynamicClasses
])>
```

**❌ AVOID: Direct variable interpolation in class strings**

```blade
{{-- Potentially unsafe: Direct interpolation --}}
<span class="base-classes {$dynamicClasses}">

{{-- Potentially unsafe: Unvalidated user input --}}
<div class="user-class-{{ $userInput }}">
```

### StatusBadge Component Example

The StatusBadge component demonstrates secure CSS class handling:

```blade
{{-- Before: Potentially unsafe interpolation --}}
<span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold border {$badgeClasses}">

{{-- After: Secure string concatenation --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold border ' . $badgeClasses]) }}>
```

**Why This Change Matters:**
- Prevents CSS injection through malformed class strings
- Ensures proper escaping of dynamic content
- Follows Laravel's recommended patterns for dynamic classes

## XSS Prevention

### Data Output Escaping

**✅ CORRECT: Automatic escaping with double braces**

```blade
{{-- Automatically escaped --}}
<h1>{{ $title }}</h1>
<p>{{ $userContent }}</p>

{{-- Escaped with additional formatting --}}
<span>{{ Str::limit($description, 100) }}</span>
```

**❌ AVOID: Unescaped output**

```blade
{{-- Dangerous: Unescaped HTML --}}
<div>{!! $userContent !!}</div>

{{-- Only use {!! !!} for trusted, pre-sanitized content --}}
<div>{!! $trustedHtmlFromMarkdown !!}</div>
```

### Component Data Sanitization

Sanitize data in component constructors, not in templates:

```php
// ✅ CORRECT: Sanitize in component class
final class StatusBadge extends Component
{
    public function __construct(
        BackedEnum|string|null $status
    ) {
        // Validate and sanitize input
        $this->statusValue = $this->normalizeStatus($status);
        $this->badgeClasses = $this->resolveColors($this->statusValue)['badge'];
    }
    
    private function normalizeStatus(BackedEnum|string $status): string
    {
        // Only allow known enum values or predefined strings
        return $status instanceof BackedEnum ? $status->value : (string) $status;
    }
}
```

```blade
{{-- ✅ CORRECT: Use pre-sanitized component properties --}}
<span class="{{ $badgeClasses }}">{{ $label }}</span>
```

## Template Injection Protection

### Avoid Dynamic Template Compilation

**❌ AVOID: Dynamic Blade compilation**

```php
// Dangerous: Never compile user input as Blade
$template = "Hello {{ \$userInput }}";
return Blade::render($template, ['userInput' => $request->input('name')]);
```

**✅ CORRECT: Use predefined templates with data binding**

```php
// Safe: Predefined template with data binding
return view('components.greeting', [
    'name' => $request->validated('name')
]);
```

### Component Slot Security

Validate slot content in component classes:

```php
final class Card extends Component
{
    public function __construct(
        public readonly string $title,
        public readonly string $slot = ''
    ) {
        // Validate title is safe for display
        $this->title = strip_tags($title);
    }
}
```

```blade
{{-- Safe: Component handles validation --}}
<x-card :title="$userTitle">
    {{ $userContent }}
</x-card>
```

## Multi-Tenant Security

### Tenant Data Isolation

Ensure templates respect tenant boundaries:

```blade
{{-- ✅ CORRECT: Tenant-scoped data --}}
@foreach($currentTenant->invoices as $invoice)
    <x-status-badge :status="$invoice->status" />
@endforeach

{{-- ❌ AVOID: Unscoped queries in templates --}}
@foreach(Invoice::all() as $invoice)
    {{-- This could leak cross-tenant data --}}
@endforeach
```

### Tenant Context Validation

Use view composers to ensure proper tenant context:

```php
// In AppServiceProvider
View::composer('tenant.*', TenantContextComposer::class);
```

```php
final class TenantContextComposer
{
    public function compose(View $view): void
    {
        // Ensure tenant context is set
        if (!TenantContext::current()) {
            throw new UnauthorizedHttpException('No tenant context');
        }
        
        $view->with('currentTenant', TenantContext::current());
    }
}
```

## Form Security

### CSRF Protection

Always include CSRF tokens in forms:

```blade
{{-- ✅ CORRECT: CSRF protection --}}
<form method="POST" action="{{ route('invoices.store') }}">
    @csrf
    <input type="text" name="amount" value="{{ old('amount') }}">
    <button type="submit">Create Invoice</button>
</form>

{{-- ✅ CORRECT: CSRF in AJAX --}}
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
</script>
```

### Input Validation Display

Display validation errors securely:

```blade
{{-- ✅ CORRECT: Escaped error messages --}}
@error('amount')
    <div class="error">{{ $message }}</div>
@enderror

{{-- ✅ CORRECT: Old input with escaping --}}
<input type="text" name="amount" value="{{ old('amount') }}">
```

## File Upload Security

### Secure File Display

When displaying uploaded files:

```blade
{{-- ✅ CORRECT: Validate file types and sanitize names --}}
@if($attachment->isImage())
    <img src="{{ $attachment->secureUrl() }}" alt="{{ $attachment->sanitizedName() }}">
@else
    <a href="{{ $attachment->downloadUrl() }}">{{ $attachment->sanitizedName() }}</a>
@endif

{{-- ❌ AVOID: Direct file path exposure --}}
<img src="/storage/{{ $attachment->path }}">
```

## Content Security Policy (CSP)

### Nonce Implementation

Use CSP nonces for inline scripts:

```blade
{{-- ✅ CORRECT: CSP nonce for inline scripts --}}
<script nonce="{{ Vite::cspNonce() }}">
    // Safe inline script
    window.appConfig = @json($config);
</script>

{{-- ✅ CORRECT: External scripts with nonce --}}
@vite(['resources/js/app.js'])
```

### CSP Headers

Configure CSP in middleware:

```php
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        return $response->withHeaders([
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'nonce-" . Vite::cspNonce() . "'; style-src 'self' 'unsafe-inline'",
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
```

## Logging and Monitoring

### Security Event Logging

Log security-relevant template events:

```php
// In component classes
if (!app()->environment('production')) {
    logger()->warning('StatusBadge: Unknown status value', [
        'status_value' => $statusValue,
        'user_id' => auth()->id(),
        'tenant_id' => TenantContext::current()?->id,
    ]);
}
```

### Template Error Monitoring

Monitor for template injection attempts:

```php
// In exception handler
if ($exception instanceof ViewException) {
    logger()->error('Template rendering error', [
        'template' => $exception->getView(),
        'user_id' => auth()->id(),
        'ip' => request()->ip(),
    ]);
}
```

## Testing Security

### Template Security Tests

Test templates for XSS vulnerabilities:

```php
test('status badge escapes malicious input', function () {
    $maliciousInput = '<script>alert("xss")</script>';
    
    $component = new StatusBadge($maliciousInput);
    $rendered = $component->render()->render();
    
    expect($rendered)->not->toContain('<script>')
        ->and($rendered)->toContain('&lt;script&gt;');
});

test('status badge rejects invalid css classes', function () {
    $maliciousStatus = 'active"; background: url("javascript:alert(1)"); "';
    
    $component = new StatusBadge($maliciousStatus);
    
    // Should fall back to safe default classes
    expect($component->badgeClasses)->toBe('bg-slate-100 text-slate-700 border-slate-200');
});
```

### CSRF Testing

Test CSRF protection in forms:

```php
test('form requires csrf token', function () {
    $response = $this->post(route('invoices.store'), [
        'amount' => '100.00'
    ]);
    
    $response->assertStatus(419); // CSRF token mismatch
});

test('form works with valid csrf token', function () {
    $response = $this->post(route('invoices.store'), [
        '_token' => csrf_token(),
        'amount' => '100.00'
    ]);
    
    $response->assertRedirect();
});
```

## Security Checklist

### Template Review Checklist

- [ ] No `@php` blocks in templates (use view composers instead)
- [ ] All dynamic CSS classes use string concatenation (`. $var`)
- [ ] User input is escaped with `{{ }}` not `{!! !!}`
- [ ] Forms include `@csrf` directive
- [ ] File uploads validate type and sanitize names
- [ ] Component constructors validate and sanitize input
- [ ] No dynamic Blade compilation of user input
- [ ] CSP nonces used for inline scripts
- [ ] Tenant context validated in multi-tenant views

### Component Security Checklist

- [ ] Constructor validates all input parameters
- [ ] Public properties are readonly where possible
- [ ] CSS classes come from predefined constants
- [ ] Labels are escaped or come from trusted sources
- [ ] Unknown/invalid input falls back to safe defaults
- [ ] Security events are logged appropriately

## Related Documentation

- [Blade Components Guide](../frontend/BLADE_COMPONENTS.md)
- [Multi-Tenant Security](../security/MULTI_TENANT_SECURITY.md)
- [CSRF Protection](../security/CSRF_PROTECTION.md)
- [Content Security Policy](../security/CSP_IMPLEMENTATION.md)

## References

- [Laravel Blade Documentation](https://laravel.com/docs/12.x/blade)
- [OWASP XSS Prevention](https://owasp.org/www-project-cheat-sheets/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [Content Security Policy Guide](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)