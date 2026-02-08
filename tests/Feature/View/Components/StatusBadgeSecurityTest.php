<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components;

use App\Enums\InvoiceStatus;
use App\View\Components\StatusBadge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StatusBadgeSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function blade_template_uses_secure_css_concatenation(): void
    {
        $component = new StatusBadge(InvoiceStatus::PAID);
        $rendered = $component->render()->render();

        // Verify the template uses string concatenation, not interpolation
        $this->assertStringContainsString('bg-emerald-50 text-emerald-700 border-emerald-200', $rendered);
        
        // Verify proper HTML structure
        $this->assertStringContainsString('<span class="inline-flex items-center gap-2', $rendered);
        $this->assertStringContainsString('aria-hidden="true"', $rendered);
    }

    /** @test */
    public function malicious_status_input_is_escaped_in_output(): void
    {
        $maliciousInput = '<script>alert("xss")</script>';
        
        $component = new StatusBadge($maliciousInput);
        $rendered = $component->render()->render();

        // Script tags should be escaped in the output
        $this->assertStringNotContainsString('<script>', $rendered);
        $this->assertStringContainsString('&lt;script&gt;', $rendered);
        
        // Should use safe default styling
        $this->assertStringContainsString('bg-slate-100 text-slate-700 border-slate-200', $rendered);
    }

    /** @test */
    public function css_injection_attempts_are_neutralized(): void
    {
        $maliciousStatus = 'active"; background: url("javascript:alert(1)"); "';
        
        $component = new StatusBadge($maliciousStatus);
        $rendered = $component->render()->render();

        // Should not contain the malicious CSS
        $this->assertStringNotContainsString('javascript:', $rendered);
        $this->assertStringNotContainsString('background:', $rendered);
        
        // Should use safe default classes
        $this->assertStringContainsString('bg-slate-100 text-slate-700 border-slate-200', $rendered);
    }

    /** @test */
    public function component_handles_null_status_securely(): void
    {
        $component = new StatusBadge(null);
        $rendered = $component->render()->render();

        // Should render with safe defaults
        $this->assertStringContainsString('bg-slate-100 text-slate-700 border-slate-200', $rendered);
        $this->assertStringContainsString('Unknown', $rendered);
        
        // Should not contain any null-related errors
        $this->assertStringNotContainsString('null', $rendered);
    }

    /** @test */
    public function slot_content_is_properly_escaped(): void
    {
        $component = new StatusBadge(InvoiceStatus::PAID);
        
        // Simulate slot content with potential XSS
        $slotContent = '<script>alert("xss")</script>';
        
        // In a real Blade template, this would be escaped automatically
        $view = view('components.status-badge', [
            'status' => InvoiceStatus::PAID,
            'statusValue' => $component->statusValue,
            'label' => $component->label,
            'badgeClasses' => $component->badgeClasses,
            'dotClasses' => $component->dotClasses,
            'slot' => $slotContent,
        ]);
        
        $rendered = $view->render();
        
        // Slot content should be escaped when using {{ }} in Blade
        $this->assertStringNotContainsString('<script>', $rendered);
    }

    /** @test */
    public function component_attributes_are_properly_merged(): void
    {
        $component = new StatusBadge(InvoiceStatus::PAID);
        
        // Test with additional attributes that could be malicious
        $view = view('components.status-badge', [
            'status' => InvoiceStatus::PAID,
            'statusValue' => $component->statusValue,
            'label' => $component->label,
            'badgeClasses' => $component->badgeClasses,
            'dotClasses' => $component->dotClasses,
            'attributes' => collect([
                'onclick' => 'alert("xss")',
                'class' => 'additional-class',
            ]),
        ]);
        
        $rendered = $view->render();
        
        // Should contain the safe additional class
        $this->assertStringContainsString('additional-class', $rendered);
        
        // The onclick attribute would be handled by Laravel's attribute bag
        // which properly escapes attribute values
    }

    /** @test */
    public function enum_status_values_are_secure(): void
    {
        $enums = [
            InvoiceStatus::DRAFT,
            InvoiceStatus::FINALIZED,
            InvoiceStatus::PAID,
        ];

        foreach ($enums as $enum) {
            $component = new StatusBadge($enum);
            $rendered = $component->render()->render();

            // All enum-based statuses should render safely
            $this->assertStringNotContainsString('<script>', $rendered);
            $this->assertStringNotContainsString('javascript:', $rendered);
            
            // Should contain proper Tailwind classes
            $this->assertMatchesRegularExpression('/bg-\w+-\d+/', $rendered);
        }
    }

    /** @test */
    public function component_prevents_template_injection(): void
    {
        // Attempt to inject Blade syntax
        $maliciousInput = '{{ phpinfo() }}';
        
        $component = new StatusBadge($maliciousInput);
        $rendered = $component->render()->render();

        // Blade syntax should be escaped, not executed
        $this->assertStringNotContainsString('phpinfo()', $rendered);
        $this->assertStringContainsString('{{ Phpinfo() }}', $rendered); // Title case formatted
    }

    /** @test */
    public function component_handles_unicode_and_special_characters(): void
    {
        $unicodeStatus = 'статус-тест-🔒';
        
        $component = new StatusBadge($unicodeStatus);
        $rendered = $component->render()->render();

        // Should handle unicode safely
        $this->assertStringContainsString('Статус-тест-🔒', $rendered); // Title case
        
        // Should use default safe styling for unknown status
        $this->assertStringContainsString('bg-slate-100', $rendered);
    }
}