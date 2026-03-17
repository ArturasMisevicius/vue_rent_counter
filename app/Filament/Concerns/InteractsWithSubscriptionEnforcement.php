<?php

namespace App\Filament\Concerns;

use App\Enums\SubscriptionAccessMode;
use App\Support\Admin\OrganizationContext;
use App\Support\Admin\SubscriptionEnforcement\OrganizationSubscriptionAccess;
use App\Support\Admin\SubscriptionEnforcement\SubscriptionAccessState;
use App\Support\Admin\SubscriptionEnforcement\SubscriptionEnforcementMessage;
use Filament\Actions\Action;

trait InteractsWithSubscriptionEnforcement
{
    public static function getSubscriptionAccessState(): SubscriptionAccessState
    {
        return app(OrganizationSubscriptionAccess::class)->forOrganization(
            app(OrganizationContext::class)->currentOrganizationId(),
        );
    }

    public static function hidesSubscriptionWriteActions(): bool
    {
        return static::getSubscriptionAccessState()->hidesWriteActions();
    }

    public static function canMutateSubscriptionScopedRecords(): bool
    {
        return ! static::getSubscriptionAccessState()->isReadOnly();
    }

    public static function shouldShowBlockedCreateAction(string $resource): bool
    {
        $state = static::getSubscriptionAccessState();

        return ! $state->hidesWriteActions() && $state->blocksCreation($resource);
    }

    public static function shouldInterceptGraceEditAction(): bool
    {
        return static::getSubscriptionAccessState()->mode === SubscriptionAccessMode::GRACE_READ_ONLY;
    }

    public static function makeSubscriptionInfoAction(string $name, string $resource, string $label): Action
    {
        $message = app(SubscriptionEnforcementMessage::class)->forResource(
            $resource,
            static::getSubscriptionAccessState(),
        );

        return Action::make($name)
            ->label($label)
            ->requiresConfirmation()
            ->modalHeading($message['title'])
            ->modalDescription($message['body'])
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (Action $action): Action => $action->label(
                __('filament-actions::view.single.modal.actions.close.label'),
            ))
            ->extraModalFooterActions([
                Action::make("{$name}ManageSubscription")
                    ->label($message['action_label'])
                    ->url($message['action_url']),
            ])
            ->action(static fn (): null => null);
    }
}
