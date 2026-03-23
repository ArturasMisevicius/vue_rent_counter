<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Concerns\AuthorizesSuperadminAccess;
use App\Filament\Widgets\Framework\FrameworkShowcaseStatusChart;
use App\Filament\Widgets\Framework\FrameworkStackStatsOverview;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

final class FrameworkStudio extends Page
{
    use AuthorizesSuperadminAccess;

    protected static ?string $navigationLabel = 'Framework Studio';

    protected static ?int $navigationSort = 90;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected string $view = 'filament.pages.framework-studio';

    public function getTitle(): string
    {
        return 'Framework Studio';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openShowcase')
                ->label('Open Livewire showcase')
                ->icon(Heroicon::OutlinedBolt)
                ->url(route('framework.livewire.showcase'))
                ->openUrlInNewTab(),
            Action::make('broadcastNotification')
                ->label('Broadcast demo notification')
                ->icon(Heroicon::OutlinedBellAlert)
                ->slideOver()
                ->schema([
                    TextInput::make('title')
                        ->default('Framework showcase updated')
                        ->required(),
                    Textarea::make('body')
                        ->default('The framework studio demo surfaces are ready for review.')
                        ->rows(4)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $user = auth()->user();

                    if ($user === null) {
                        return;
                    }

                    Notification::make()
                        ->title($data['title'])
                        ->body($data['body'])
                        ->success()
                        ->icon(Heroicon::OutlinedSparkles)
                        ->actions([
                            NotificationAction::make('showcase')
                                ->button()
                                ->url(route('framework.livewire.showcase')),
                        ])
                        ->sendToDatabase($user, isEventDispatched: true)
                        ->broadcast($user)
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FrameworkStackStatsOverview::class,
            FrameworkShowcaseStatusChart::class,
        ];
    }
}
