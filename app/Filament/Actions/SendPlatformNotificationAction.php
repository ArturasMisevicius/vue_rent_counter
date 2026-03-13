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
            ->label(__('platform_notifications.actions.send_platform_notification'))
            ->icon('heroicon-o-megaphone')
            ->color('primary')
            ->form([
                TextInput::make('title')
                    ->label(__('platform_notifications.labels.notification_title'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('platform_notifications.placeholders.notification_title')),

                RichEditor::make('message')
                    ->label(__('platform_notifications.labels.message'))
                    ->required()
                    ->placeholder(__('platform_notifications.placeholders.message'))
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
                    ->label(__('platform_notifications.labels.target_audience'))
                    ->required()
                    ->options([
                        'all' => __('platform_notifications.values.target_type.all'),
                        'plan' => __('platform_notifications.values.target_type.plan'),
                        'organization' => __('platform_notifications.values.target_type.organization'),
                    ])
                    ->default('all')
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('target_criteria', null)),

                Select::make('target_criteria')
                    ->label(__('platform_notifications.labels.target_selection'))
                    ->multiple()
                    ->searchable()
                    ->options(function (callable $get) {
                        $targetType = $get('target_type');

                        return match ($targetType) {
                            'plan' => [
                                'basic' => __('platform_notifications.values.plan.basic'),
                                'professional' => __('platform_notifications.values.plan.professional'),
                                'enterprise' => __('platform_notifications.values.plan.enterprise'),
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
                            'plan' => __('platform_notifications.placeholders.select_subscription_plans'),
                            'organization' => __('platform_notifications.placeholders.select_organizations'),
                            default => '',
                        };
                    }),

                Toggle::make('schedule_later')
                    ->label(__('platform_notifications.actions.schedule_for_later'))
                    ->default(false)
                    ->live(),

                DateTimePicker::make('scheduled_at')
                    ->label(__('platform_notifications.labels.schedule_date_time'))
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
                            ->title(__('platform_notifications.notifications.scheduled'))
                            ->body(__('platform_notifications.messages.notification_scheduled_body', [
                                'title' => $notification->title,
                                'scheduled_at' => $notification->scheduled_at?->format('M j, Y g:i A') ?? '',
                            ]))
                            ->success()
                            ->send();
                    } else {
                        $service->sendNotification($notification);

                        Notification::make()
                            ->title(__('platform_notifications.notifications.sent'))
                            ->body(__('platform_notifications.messages.notification_sent_body', [
                                'title' => $notification->title,
                                'recipients' => $notification->getTotalRecipients(),
                            ]))
                            ->success()
                            ->send();
                    }

                } catch (\Exception $e) {
                    Notification::make()
                        ->title(__('platform_notifications.notifications.error'))
                        ->body(__('platform_notifications.messages.failed_to_send', [
                            'error' => $e->getMessage(),
                        ]))
                        ->danger()
                        ->send();
                }
            })
            ->modalHeading(__('platform_notifications.headings.send_platform_notification'))
            ->modalDescription(__('platform_notifications.descriptions.send_platform_notification'))
            ->modalSubmitActionLabel(__('platform_notifications.actions.send_notification'))
            ->modalWidth('2xl');
    }
}
