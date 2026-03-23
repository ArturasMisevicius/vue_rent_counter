<?php

declare(strict_types=1);

use App\Services\FrameworkShowcaseMetricsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public bool $isOpen = false;

    public string $title = 'Framework preview modal';

    #[On('open-preview-modal')]
    public function open(): void
    {
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function confirm(): void
    {
        $this->dispatch('framework-preview-confirmed');

        $this->close();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function highlights(): array
    {
        $showcaseCount = app(FrameworkShowcaseMetricsService::class)->counts()['showcases'];

        return [
            'Directory multi-file component using a dedicated folder source.',
            'Computed preview content sourced from the demo model layer.',
            sprintf('%d framework showcases currently exist in the demo resource.', $showcaseCount),
        ];
    }
};
