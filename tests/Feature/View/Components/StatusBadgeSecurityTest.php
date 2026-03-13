<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use Illuminate\Support\Facades\Blade;

it('uses safe predefined css classes in rendered output', function (): void {
    $rendered = Blade::render('<x-status-badge :status="$status" />', [
        'status' => InvoiceStatus::PAID,
    ]);

    expect($rendered)
        ->toContain('bg-emerald-50 text-emerald-700 border-emerald-200')
        ->toContain('<span class="inline-flex items-center gap-2')
        ->toContain('aria-hidden="true"');
});

it('escapes malicious status input in output', function (): void {
    $rendered = Blade::render('<x-status-badge :status="$status" />', [
        'status' => '<script>alert("xss")</script>',
    ]);

    expect($rendered)
        ->not->toContain('<script>')
        ->toContain('&lt;script&gt;')
        ->toContain('bg-slate-100 text-slate-700 border-slate-200');
});

it('neutralizes css injection attempts', function (): void {
    $rendered = Blade::render('<x-status-badge :status="$status" />', [
        'status' => 'active"; background: url("javascript:alert(1)"); "',
    ]);

    expect($rendered)
        ->not->toContain('javascript:')
        ->toContain('bg-slate-100 text-slate-700 border-slate-200');
});

it('handles null status with safe defaults', function (): void {
    $rendered = Blade::render('<x-status-badge :status="$status" />', [
        'status' => null,
    ]);

    expect($rendered)
        ->toContain('bg-slate-100 text-slate-700 border-slate-200')
        ->toContain('Unknown');
});

it('prevents blade template injection from status values', function (): void {
    $rendered = Blade::render('<x-status-badge :status="$status" />', [
        'status' => '{{ phpinfo() }}',
    ]);

    expect($rendered)
        ->not->toContain('phpinfo()')
        ->toContain('{{ Phpinfo() }}');
});

it('handles unicode status values safely', function (): void {
    $rendered = Blade::render('<x-status-badge :status="$status" />', [
        'status' => 'статус-тест-🔒',
    ]);

    expect($rendered)
        ->toContain('Статус-Тест-🔒')
        ->toContain('bg-slate-100');
});
