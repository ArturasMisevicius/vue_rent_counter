<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\SubscriptionAccessMode;
use App\Filament\Support\Admin\SubscriptionEnforcement\SubscriptionAccessState;
use App\Models\User;
use App\Services\SubscriptionChecker;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionStatus
{
    public function __construct(
        private readonly SubscriptionChecker $subscriptionChecker,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || (! $user->isAdmin() && ! $user->isManager())) {
            return $next($request);
        }

        $state = $this->subscriptionChecker->accessState($user);

        return match ($state->mode) {
            SubscriptionAccessMode::ACTIVE, SubscriptionAccessMode::LIMIT_BLOCKED => $next($request),
            SubscriptionAccessMode::GRACE_READ_ONLY => $this->handleGracePeriod($request, $next, $state),
            SubscriptionAccessMode::POST_GRACE_READ_ONLY => $this->handlePostGracePeriod($request, $next, $state),
            SubscriptionAccessMode::SUSPENDED => response()->view('errors.subscription-suspended', status: 423),
        };
    }

    private function handleGracePeriod(
        Request $request,
        Closure $next,
        SubscriptionAccessState $state,
    ): Response {
        if ($this->isReadRequest($request)) {
            return $next($request);
        }

        $this->notifyBlockedWrite(
            __('behavior.subscription.grace_read_only.title'),
            __('behavior.subscription.grace_read_only.body', [
                'grace_ends_at' => $state->graceEndsAt?->toDateString(),
            ]),
        );

        return redirect()->to($this->subscriptionSettingsUrl());
    }

    private function handlePostGracePeriod(
        Request $request,
        Closure $next,
        SubscriptionAccessState $state,
    ): Response {
        if ($this->isAllowedPostGraceRequest($request)) {
            return $next($request);
        }

        $this->notifyBlockedWrite(
            __('behavior.subscription.post_grace_read_only.title'),
            __('behavior.subscription.post_grace_read_only.body', [
                'grace_ends_at' => $state->graceEndsAt?->toDateString(),
            ]),
        );

        return redirect()->to($this->subscriptionSettingsUrl());
    }

    private function notifyBlockedWrite(string $title, string $body): void
    {
        Notification::make()
            ->danger()
            ->persistent()
            ->title($title)
            ->body($body)
            ->actions([
                Action::make('manageSubscription')
                    ->label(__('behavior.subscription.actions.manage'))
                    ->button()
                    ->url($this->subscriptionSettingsUrl()),
            ])
            ->send();
    }

    private function isReadRequest(Request $request): bool
    {
        return in_array($request->method(), ['GET', 'HEAD'], true);
    }

    private function isAllowedPostGraceRequest(Request $request): bool
    {
        return $this->isReadRequest($request)
            && $request->routeIs(
                'filament.admin.pages.profile',
                'filament.admin.pages.settings',
            );
    }

    private function subscriptionSettingsUrl(): string
    {
        return route('filament.admin.pages.settings').'#subscription';
    }
}
