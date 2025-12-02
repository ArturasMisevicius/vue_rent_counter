<?php

declare(strict_types=1);

namespace Tests\Unit\View\Components;

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\View\Components\StatusBadge;
use Tests\TestCase;

final class StatusBadgeTest extends TestCase
{
    /** @test */
    public function it_handles_enum_status(): void
    {
        $component = new StatusBadge(InvoiceStatus::DRAFT);

        $this->assertSame('draft', $component->statusValue);
        $this->assertNotEmpty($component->label);
        $this->assertStringContainsString('amber', $component->badgeClasses);
        $this->assertStringContainsString('amber', $component->dotClasses);
    }

    /** @test */
    public function it_handles_string_status(): void
    {
        $component = new StatusBadge('active');

        $this->assertSame('active', $component->statusValue);
        $this->assertNotEmpty($component->label);
        $this->assertStringContainsString('emerald', $component->badgeClasses);
        $this->assertStringContainsString('emerald', $component->dotClasses);
    }

    /** @test */
    public function it_applies_correct_colors_for_draft_status(): void
    {
        $component = new StatusBadge('draft');

        $this->assertStringContainsString('bg-amber-50', $component->badgeClasses);
        $this->assertStringContainsString('text-amber-700', $component->badgeClasses);
        $this->assertStringContainsString('border-amber-200', $component->badgeClasses);
        $this->assertStringContainsString('bg-amber-400', $component->dotClasses);
    }

    /** @test */
    public function it_applies_correct_colors_for_paid_status(): void
    {
        $component = new StatusBadge(InvoiceStatus::PAID);

        $this->assertStringContainsString('bg-emerald-50', $component->badgeClasses);
        $this->assertStringContainsString('text-emerald-700', $component->badgeClasses);
        $this->assertStringContainsString('border-emerald-200', $component->badgeClasses);
        $this->assertStringContainsString('bg-emerald-500', $component->dotClasses);
    }

    /** @test */
    public function it_applies_correct_colors_for_expired_status(): void
    {
        $component = new StatusBadge(SubscriptionStatus::EXPIRED);

        $this->assertStringContainsString('bg-rose-50', $component->badgeClasses);
        $this->assertStringContainsString('text-rose-700', $component->badgeClasses);
        $this->assertStringContainsString('border-rose-200', $component->badgeClasses);
        $this->assertStringContainsString('bg-rose-400', $component->dotClasses);
    }

    /** @test */
    public function it_applies_default_colors_for_unknown_status(): void
    {
        $component = new StatusBadge('unknown_status');

        $this->assertStringContainsString('bg-slate-100', $component->badgeClasses);
        $this->assertStringContainsString('text-slate-700', $component->badgeClasses);
        $this->assertStringContainsString('border-slate-200', $component->badgeClasses);
        $this->assertStringContainsString('bg-slate-400', $component->dotClasses);
    }

    /** @test */
    public function it_resolves_label_from_enum(): void
    {
        $component = new StatusBadge(InvoiceStatus::FINALIZED);

        // Label should be resolved from enum's label() method
        $this->assertNotEmpty($component->label);
        $this->assertIsString($component->label);
    }

    /** @test */
    public function it_formats_label_for_string_status(): void
    {
        $component = new StatusBadge('test_status');

        // Should format as "Test Status"
        $this->assertSame('Test Status', $component->label);
    }

    /** @test */
    public function it_handles_all_invoice_statuses(): void
    {
        foreach (InvoiceStatus::cases() as $status) {
            $component = new StatusBadge($status);

            $this->assertNotEmpty($component->statusValue);
            $this->assertNotEmpty($component->label);
            $this->assertNotEmpty($component->badgeClasses);
            $this->assertNotEmpty($component->dotClasses);
        }
    }

    /** @test */
    public function it_handles_all_subscription_statuses(): void
    {
        foreach (SubscriptionStatus::cases() as $status) {
            $component = new StatusBadge($status);

            $this->assertNotEmpty($component->statusValue);
            $this->assertNotEmpty($component->label);
            $this->assertNotEmpty($component->badgeClasses);
            $this->assertNotEmpty($component->dotClasses);
        }
    }

    /** @test */
    public function it_handles_all_user_roles(): void
    {
        foreach (UserRole::cases() as $role) {
            $component = new StatusBadge($role);

            $this->assertNotEmpty($component->statusValue);
            $this->assertNotEmpty($component->label);
            $this->assertNotEmpty($component->badgeClasses);
            $this->assertNotEmpty($component->dotClasses);
        }
    }

    /** @test */
    public function it_renders_view(): void
    {
        $component = new StatusBadge(InvoiceStatus::DRAFT);
        $view = $component->render();

        $this->assertSame('components.status-badge', $view->name());
    }

    /** @test */
    public function it_caches_translations(): void
    {
        // First call should populate cache
        $component1 = new StatusBadge('draft');
        $label1 = $component1->label;

        // Second call should use cached translations
        $component2 = new StatusBadge('draft');
        $label2 = $component2->label;

        $this->assertSame($label1, $label2);
    }
}
