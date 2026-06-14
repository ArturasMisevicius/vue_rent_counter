<?php

declare(strict_types=1);

namespace App\Filament\Actions\Help;

use App\Filament\Support\Help\HelpRepository;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

final class ContextualHelpAction
{
    public static function make(string $pageKey): Action
    {
        return Action::make('help')
            ->label(__('help.actions.help'))
            ->icon('heroicon-m-question-mark-circle')
            ->color('gray')
            ->slideOver()
            ->modalHeading(__('help.context.heading'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('admin.actions.close'))
            ->modalContent(fn (): View => view('filament.components.contextual-help-panel', [
                'articles' => self::articles($pageKey),
                'helpCenterUrl' => self::helpCenterUrl($pageKey),
                'pageKey' => $pageKey,
            ]));
    }

    /**
     * @return array<int, array{title: string, body: string, slug: string}>
     */
    private static function articles(string $pageKey): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        return app(HelpRepository::class)
            ->contextFor($user, $pageKey)
            ->map(fn ($article): array => [
                'title' => (string) $article->title,
                'body' => (string) $article->body,
                'slug' => (string) $article->slug,
            ])
            ->all();
    }

    private static function helpCenterUrl(string $pageKey): ?string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        $routeName = $user->isTenant()
            ? 'filament.admin.pages.tenant-help'
            : 'filament.admin.pages.help';

        return Route::has($routeName) ? route($routeName, ['page' => $pageKey]) : null;
    }
}
