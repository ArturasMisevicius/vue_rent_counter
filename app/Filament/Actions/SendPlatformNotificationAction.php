<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Services\PlatformNotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class SendPlatformNotificationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'send_platform_notification';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Send Platform Notification')
            ->icon('heroicon-o-megaphone')
            ->color('primary')
            ->form([
                TextInput::make('title')
                    ->label('Notification Title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter notification title'),

                RichEditor::make('message')
                    ->label('Message')
                    ->required()
                    ->placeholder('Enter your notification message...')
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'link',
                        'bulletList',
                        'orderedList',
                        'h2',
                        'h3',
                    ]),

                Select::make('target_type')
                    ->label('Target Audience')
                    ->required()
                    ->options([
                        'all' => 'All Organizations',
                        'plan' => 'Specific Plans',
                        'organization' => 'Individual Organizations',
                    ])
                    ->default('all')
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('target_criteria', null)),

                Select::make('target_criteria')
                    ->label('Target Selection')
                    ->multiple()
                    ->searchable()
                    ->options(function (callable $get) {
                        $targetType = $get('target_type');
                        
                        return match ($targetType) {
                            'plan' => [
                                'basic' => 'Basic Plan',
                                'professional' => 'Professional Plan',
                                'enterprise' => 'Enterprise Plan',
                            ],
                            'organization' => Organization::active()
                                ->pluck('name', 'id')
                                ->toArray(),
                            default => [],
                        };
                    })
                    ->visible(fn (callable $get) => in_array($get('target_type'), ['plan', 'organization']))
                    ->required(fn (callable $get) => in_array($get('target_type'), ['plan', 'organization']))
                    ->placeholder(function (callable $get) {
                        $targetType = $get('target_type');
                        return match ($targetType) {
                            'plan' => 'Select subscription plans...',
                            'organization' => 'Select organizations...',
                            default => '',
                        };
                    }),

                Toggle::make('schedule_later')
                    ->label('Schedule for Later')
                    ->default(false)
                    ->live(),

                DateTimePicker::make('scheduled_at')
                    ->label('Schedule Date & Time')
                    ->visible(fn (callable $get) => $get('schedule_later'))
                    ->required(fn (callable $get) => $get('schedule_later'))
                    ->minDate(now())
                    ->seconds(false)
                    ->timezone(config('app.timezone')),
            ])
            ->action(function (array $data) {
                try {
                    $notification = PlatformNotification::create([
                        'title' => $data['title'],
                        'message' => $data['message'],
                        'target_type' => $data['target_type'],
                        'target_criteria' => $data['target_criteria'] ?? null,
                        'status' => $data['schedule_later'] ?? false ? 'scheduled' : 'draft',
                        'scheduled_at' => $data['scheduled_at'] ?? null,
                        'created_by' => Auth::id(),
                    ]);

                    $service = app(PlatformNotificationService::class);

                    if ($data['schedule_later'] ?? false) {
                        $service->scheduleNotification($notification, $data['scheduled_at']);
                        
                        Notification::make()
                            ->title('Notification Scheduled')
                            ->body("Notification '{$notification->title}' has been scheduled for " . 
                                   $notification->scheduled_at->format('M j, Y g:i A'))
                            ->success()
                            ->send();
                    } else {
                        $service->sendNotification($notification);
                        
                        Notification::make()
                            ->title('Notification Sent')
                            ->body("Notification '{$notification->title}' has been sent to " . 
                                   $notification->getTotalRecipients() . ' recipients')
                            ->success()
                            ->send();
                    }

                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error')
                        ->body('Failed to send notification: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->modalHeading('Send Platform Notification')
            ->modalDescription('Send notifications to organizations across the platform')
            ->modalSubmitActionLabel('Send Notification')
            ->modalWidth('2xl');
    }
}