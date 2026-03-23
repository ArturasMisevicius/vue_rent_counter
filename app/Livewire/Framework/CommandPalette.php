<?php

declare(strict_types=1);

namespace App\Livewire\Framework;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

final class CommandPalette extends Component
{
    public bool $isOpen = false;

    public string $query = '';

    #[On('open-palette')]
    public function open(): void
    {
        $this->isOpen = true;
    }

    #[On('close-palette')]
    public function close(): void
    {
        $this->isOpen = false;
        $this->query = '';
    }

    /**
     * @return array<int, array{label: string, description: string, url: string}>
     */
    #[Computed]
    public function commands(): array
    {
        $query = str($this->query)->lower()->value();

        return collect([
            [
                'label' => 'Open dashboard',
                'description' => 'Jump back to the shared Filament dashboard entry.',
                'url' => route('filament.admin.pages.dashboard'),
            ],
            [
                'label' => 'Manage framework showcases',
                'description' => 'Browse the demo Filament CRUD resource.',
                'url' => route('filament.admin.resources.framework-showcases.index'),
            ],
            [
                'label' => 'Open reports',
                'description' => 'Compare the showcase patterns with a real production page.',
                'url' => route('filament.admin.pages.reports'),
            ],
        ])
            ->filter(function (array $command) use ($query): bool {
                if ($query === '') {
                    return true;
                }

                return str($command['label'].' '.$command['description'])
                    ->lower()
                    ->contains($query);
            })
            ->values()
            ->all();
    }

    public function run(string $url): mixed
    {
        $this->close();

        return redirect()->to($url);
    }

    public function render()
    {
        return view('livewire.framework.command-palette');
    }
}
