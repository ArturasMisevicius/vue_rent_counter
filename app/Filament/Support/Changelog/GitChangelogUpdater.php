<?php

declare(strict_types=1);

namespace App\Filament\Support\Changelog;

final class GitChangelogUpdater
{
    /**
     * @param  array<int, string>  $changes
     */
    public function sync(
        string $markdown,
        string $date,
        string $entryId,
        string $title,
        array $changes,
        ?string $replaceEntryId = null,
    ): string {
        $changes = array_values(array_unique(array_filter($changes)));

        if ($changes === []) {
            return $this->normalizeMarkdown($markdown);
        }

        if ($replaceEntryId !== null && $replaceEntryId !== $entryId) {
            $markdown = $this->removeEntry($markdown, $replaceEntryId);
        }

        $block = $this->buildEntryBlock($entryId, $title, $changes);

        if ($this->hasEntry($markdown, $entryId)) {
            return $this->replaceEntry($markdown, $entryId, $block);
        }

        return $this->insertEntry($markdown, $date, $block);
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, string>
     */
    public function formatNameStatusLines(array $lines, string $language = 'en'): array
    {
        $groups = [
            'A' => [],
            'M' => [],
            'D' => [],
            'R' => [],
            'C' => [],
        ];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = explode("\t", $line);
            $status = strtoupper((string) array_shift($parts));
            $kind = substr($status, 0, 1);

            if ($kind === 'R' && count($parts) >= 2) {
                $path = $parts[1];

                if ($this->isChangelogPath($parts[0]) || $this->isChangelogPath($path)) {
                    continue;
                }

                $groups['R'][] = $this->intentLabel($path, $language);

                continue;
            }

            if ($kind === 'C' && count($parts) >= 2) {
                $path = $parts[1];

                if ($this->isChangelogPath($path)) {
                    continue;
                }

                $groups['C'][] = $this->intentLabel($path, $language);

                continue;
            }

            $path = $parts[0] ?? null;

            if ($path === null || $this->isChangelogPath($path)) {
                continue;
            }

            $groups[$this->normalizeChangeKind($kind)][] = $this->intentLabel($path, $language);
        }

        return $this->formatIntentGroups($groups, $language);
    }

    public function nextFinalEntryId(string $timestamp): string
    {
        return 'commit-'.preg_replace('/[^0-9]/', '', $timestamp);
    }

    private function buildEntryBlock(string $entryId, string $title, array $changes): string
    {
        return sprintf(
            "<!-- changelog:auto:start:%s -->\n### %s\n\n%s\n<!-- changelog:auto:end:%s -->\n",
            $entryId,
            trim($title),
            implode("\n", $changes),
            $entryId,
        );
    }

    /**
     * @param  array{A: array<int, string>, M: array<int, string>, D: array<int, string>, R: array<int, string>, C: array<int, string>}  $groups
     * @return array<int, string>
     */
    private function formatIntentGroups(array $groups, string $language): array
    {
        $changes = [];

        foreach ($groups as $kind => $labels) {
            $labels = array_values(array_unique($labels));

            if ($labels === []) {
                continue;
            }

            $changes[] = sprintf(
                '- %s: %s.',
                $this->verbFor($kind, $language),
                $this->joinLabels($labels, $language),
            );
        }

        return $changes;
    }

    private function normalizeChangeKind(string $kind): string
    {
        return match ($kind) {
            'A', 'D', 'R', 'C' => $kind,
            default => 'M',
        };
    }

    private function verbFor(string $kind, string $language): string
    {
        if ($language === 'ru') {
            return match ($kind) {
                'A' => 'Добавлено',
                'D' => 'Удалено',
                'R' => 'Перестроено',
                'C' => 'Переиспользовано',
                default => 'Обновлено',
            };
        }

        return match ($kind) {
            'A' => 'Added',
            'D' => 'Removed',
            'R' => 'Reorganized',
            'C' => 'Reused',
            default => 'Updated',
        };
    }

    /**
     * @param  array<int, string>  $labels
     */
    private function joinLabels(array $labels, string $language): string
    {
        if (count($labels) === 1) {
            return $labels[0];
        }

        $last = (string) array_pop($labels);
        $glue = $language === 'ru' ? ' и ' : ' and ';

        if (count($labels) === 1) {
            return $labels[0].$glue.$last;
        }

        return implode(', ', $labels).($language === 'ru' ? ' и ' : ', and ').$last;
    }

    private function intentLabel(string $path, string $language): string
    {
        $label = $this->englishIntentLabel($path);

        if ($language !== 'ru') {
            return $label;
        }

        return $this->russianIntentLabel($label);
    }

    private function englishIntentLabel(string $path): string
    {
        $rules = [
            'commit-message generation' => static fn (string $path): bool => str_contains($path, 'GenerateCommitMessage')
                || str_contains($path, 'generate_commit_message'),
            'commit-message enforcement' => static fn (string $path): bool => str_contains($path, 'commit-msg'),
            'pre-commit quality gates' => static fn (string $path): bool => str_contains($path, 'pre-commit'),
            'changelog automation' => static fn (string $path): bool => str_contains($path, 'update_changelog'),
            'git hook installation' => static fn (string $path): bool => str_contains($path, 'install-git-hooks'),
            'view hygiene guard' => static fn (string $path): bool => str_contains($path, 'ViewHygiene')
                || str_contains($path, 'check_view_hygiene'),
            'agent hook automation' => static fn (string $path): bool => str_starts_with($path, '.codex/hooks/'),
            'agent configuration' => static fn (string $path): bool => str_contains($path, 'hooks.json')
                || str_starts_with($path, '.agent/')
                || str_starts_with($path, '.agents/')
                || str_starts_with($path, '.ai/'),
            'documentation' => fn (string $path): bool => $this->isDocumentationPath($path),
            'localization behavior' => static fn (string $path): bool => str_starts_with($path, 'lang/')
                || preg_match('/Translation|Locale|Language/', $path) === 1,
            'billing period workflow' => static fn (string $path): bool => str_contains($path, 'BillingPeriod'),
            'billing workflow' => static fn (string $path): bool => preg_match('/Billing|Invoice|MeterReading|Payment/', $path) === 1,
            'tenant KYC workflow' => static fn (string $path): bool => preg_match('/TenantKyc|Kyc/', $path) === 1,
            'tenant document workflow' => static fn (string $path): bool => preg_match('/TenantDocument|Document/', $path) === 1,
            'move-out workflow' => static fn (string $path): bool => str_contains($path, 'MoveOut'),
            'lead workflow' => static fn (string $path): bool => preg_match('/ListingLead|Lead/', $path) === 1,
            'project collaboration' => static fn (string $path): bool => str_contains($path, 'Project'),
            'authorization behavior' => static fn (string $path): bool => preg_match('/Policy|Permission|Authorization|Security/', $path) === 1,
            'dashboard experience' => static fn (string $path): bool => str_contains($path, 'Dashboard'),
            'Filament admin workflow' => static fn (string $path): bool => str_starts_with($path, 'app/Filament/'),
            'Livewire UI workflow' => static fn (string $path): bool => str_starts_with($path, 'app/Livewire/'),
            'Blade interface templates' => static fn (string $path): bool => str_starts_with($path, 'resources/views/'),
            'frontend styling' => static fn (string $path): bool => str_starts_with($path, 'resources/css/')
                || str_ends_with($path, '.css')
                || str_contains($path, 'tailwind'),
            'database schema or seed data' => static fn (string $path): bool => str_starts_with($path, 'database/'),
            'route definitions' => static fn (string $path): bool => str_starts_with($path, 'routes/'),
            'application configuration' => static fn (string $path): bool => str_starts_with($path, 'config/'),
            'frontend build configuration' => static fn (string $path): bool => str_contains($path, 'package')
                || str_contains($path, 'vite')
                || str_contains($path, 'postcss'),
        ];

        foreach ($rules as $label => $matches) {
            if ($matches($path)) {
                return $label;
            }
        }

        return str_starts_with($path, 'app/')
            || str_starts_with($path, 'database/')
            || str_starts_with($path, 'resources/')
            || str_starts_with($path, 'routes/')
            || str_starts_with($path, 'config/')
                ? 'application behavior'
                : 'project automation';
    }

    private function russianIntentLabel(string $label): string
    {
        return [
            'commit-message generation' => 'генерация commit message',
            'commit-message enforcement' => 'проверка commit message',
            'pre-commit quality gates' => 'pre-commit проверки качества',
            'changelog automation' => 'автоматизация changelog',
            'git hook installation' => 'установка git hooks',
            'view hygiene guard' => 'проверка Blade и CSS правил',
            'agent hook automation' => 'автоматизация agent hooks',
            'agent configuration' => 'настройки агентов',
            'documentation' => 'документация',
            'localization behavior' => 'локализация',
            'billing period workflow' => 'процесс billing period',
            'billing workflow' => 'billing workflow',
            'tenant KYC workflow' => 'tenant KYC workflow',
            'tenant document workflow' => 'tenant document workflow',
            'move-out workflow' => 'move-out workflow',
            'lead workflow' => 'lead workflow',
            'project collaboration' => 'project collaboration',
            'authorization behavior' => 'авторизация и безопасность',
            'dashboard experience' => 'dashboard experience',
            'Filament admin workflow' => 'Filament admin workflow',
            'Livewire UI workflow' => 'Livewire UI workflow',
            'Blade interface templates' => 'Blade templates',
            'frontend styling' => 'frontend styling',
            'database schema or seed data' => 'схема базы или seed data',
            'route definitions' => 'routes',
            'application configuration' => 'конфигурация приложения',
            'frontend build configuration' => 'frontend build configuration',
            'application behavior' => 'поведение приложения',
            'project automation' => 'автоматизация проекта',
        ][$label] ?? $label;
    }

    private function isDocumentationPath(string $path): bool
    {
        return $path === 'README.md'
            || $path === 'CHANGELOG.md'
            || $path === 'changelog.md'
            || str_starts_with($path, 'docs/')
            || str_ends_with($path, '.md');
    }

    private function isChangelogPath(string $path): bool
    {
        return $path === 'CHANGELOG.md' || $path === 'changelog.md';
    }

    private function insertEntry(string $markdown, string $date, string $block): string
    {
        $markdown = $this->normalizeMarkdown($markdown);
        $dateHeading = '## '.$date;
        $dateHeadingPattern = '/^'.preg_quote($dateHeading, '/').'$/m';

        if (preg_match($dateHeadingPattern, $markdown) === 1) {
            $updated = preg_replace($dateHeadingPattern, $dateHeading."\n\n".$block, $markdown, 1);

            return $this->normalizeMarkdown($updated ?? $markdown);
        }

        $heading = '# Changelog';

        if (str_starts_with($markdown, $heading)) {
            $suffix = ltrim(substr($markdown, strlen($heading)), "\n");
            $updated = $heading."\n\n".$dateHeading."\n\n".$block;

            if ($suffix !== '') {
                $updated .= "\n".$suffix;
            }

            return $this->normalizeMarkdown($updated);
        }

        return $this->normalizeMarkdown($heading."\n\n".$dateHeading."\n\n".$block.$markdown);
    }

    private function hasEntry(string $markdown, string $entryId): bool
    {
        return str_contains($markdown, sprintf('<!-- changelog:auto:start:%s -->', $entryId));
    }

    private function replaceEntry(string $markdown, string $entryId, string $block): string
    {
        $pattern = $this->entryPattern($entryId);
        $updated = preg_replace($pattern, $block, $markdown, 1);

        return $this->normalizeMarkdown($updated ?? $markdown);
    }

    private function removeEntry(string $markdown, string $entryId): string
    {
        $updated = preg_replace($this->entryPattern($entryId), '', $markdown, 1);

        return $this->normalizeMarkdown($updated ?? $markdown);
    }

    private function entryPattern(string $entryId): string
    {
        $quoted = preg_quote($entryId, '/');

        return '/<!-- changelog:auto:start:'.$quoted.' -->\n.*?\n<!-- changelog:auto:end:'.$quoted.' -->\n?/s';
    }

    private function normalizeMarkdown(string $markdown): string
    {
        $markdown = preg_replace("/\n{3,}/", "\n\n", $markdown) ?? $markdown;

        return rtrim($markdown)."\n";
    }
}
