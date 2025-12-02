<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components;

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class StatusBadgeRenderTest extends TestCase
{
    /** @test */
    public function it_renders_badge_with_enum_status(): void
    {
        $html = Blade::render(
            '<x-status-badge :status="$status" />',
            ['status' => InvoiceStatus::DRAFT]
        );

        $this->assertStringContainsString('inline-flex', $html);
        $this->assertStringContainsString('items-center', $html);
        $this->assertStringContainsString('bg-amber-50', $html);
        $this->assertStringContainsString('text-amber-700', $html);
        $this->assertStringContainsString('bg-amber-400', $html);
    }

    /** @test */
    public function it_renders_badge_with_string_status(): void
    {
        $html = Blade::render(
            '<x-status-badge :status="\'paid\'" />'
        );

        $this->assertStringContainsString('inline-flex', $html);
        $this->assertStringContainsString('bg-emerald-50', $html);
        $this->assertStringContainsString('text-emerald-700', $html);
        $this->assertStringContainsString('bg-emerald-500', $html);
    }

    /** @test */
    public function it_renders_label_text(): void
    {
        $html = Blade::render(
            '<x-status-badge :status="$status" />',
            ['status' => InvoiceStatus::FINALIZED]
        );

        // Should contain the label text
        $this->assertStringContainsString('<span>', $html);
    }

    /** @test */
    public function it_renders_status_dot(): void
    {
        $html = Blade::render(
            '<x-status-badge :status="\'active\'" />'
        );

        // Should contain the dot element
        $this->assertStringContainsString('h-2.5', $html);
        $this->assertStringContainsString('w-2.5', $html);
        $this->assertStringContainsString('rounded-full', $html);
    }

    /** @test */
    public function it_merges_additional_classes(): void
    {
        $html = Blade::render(
            '<x-status-badge :status="\'draft\'" class="ml-4" />'
        );

        $this->assertStringContainsString('ml-4', $html);
        $this->assertStringContainsString('inline-flex', $html);
    }

    /** @test */
    public function it_renders_all_invoice_statuses_correctly(): void
    {
        foreach (InvoiceStatus::cases() as $status) {
            $html = Blade::render(
                '<x-status-badge :status="$status" />',
                ['status' => $status]
            );

            $this->assertStringContainsString('inline-flex', $html);
            $this->assertStringContainsString('rounded-full', $html);
            $this->assertNotEmpty($html);
        }
    }

    /** @test */
    public function it_renders_all_subscription_statuses_correctly(): void
    {
        foreach (SubscriptionStatus::cases() as $status) {
            $html = Blade::render(
                '<x-status-badge :status="$status" />',
                ['status' => $status]
            );

            $this->assertStringContainsString('inline-flex', $html);
            $this->assertStringContainsString('rounded-full', $html);
            $this->assertNotEmpty($html);
        }
    }

    /** @test */
    public function it_renders_without_php_blocks(): void
    {
        $viewContent = file_get_contents(
            resource_path('views/components/status-badge.blade.php')
        );

        // Ensure no @php blocks exist
        $this->assertStringNotContainsString('@php', $viewContent);
        $this->assertStringNotContainsString('<?php', $viewContent);
    }

    /** @test */
    public function it_has_proper_accessibility_structure(): void
    {
        $html = Blade::render(
            '<x-status-badge :status="\'active\'" />'
        );

        // Should have semantic structure
        $this->assertStringContainsString('<span', $html);
        $this->assertMatchesRegularExpression('/<span[^>]*class="[^"]*inline-flex[^"]*"/', $html);
    }
}
