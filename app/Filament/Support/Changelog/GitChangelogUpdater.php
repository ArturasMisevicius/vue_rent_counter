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
    public function formatNameStatusLines(array $lines): array
    {
        $changes = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = explode("\t", $line);
            $status = strtoupper((string) array_shift($parts));
            $kind = substr($status, 0, 1);

            if ($kind === 'R' && count($parts) >= 2) {
                [$from, $to] = [$parts[0], $parts[1]];

                if ($from === 'CHANGELOG.md' || $to === 'CHANGELOG.md') {
                    continue;
                }

                $changes[] = sprintf('- renamed `%s` to `%s`', $from, $to);

                continue;
            }

            $path = $parts[0] ?? null;

            if ($path === null || $path === 'CHANGELOG.md') {
                continue;
            }

            $verb = match ($kind) {
                'A' => 'added',
                'D' => 'removed',
                default => 'updated',
            };

            $changes[] = sprintf('- %s `%s`', $verb, $path);
        }

        return array_values(array_unique($changes));
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

    private function insertEntry(string $markdown, string $date, string $block): string
    {
        $markdown = $this->normalizeMarkdown($markdown);
        $dateHeading = '## '.$date;

        if (str_contains($markdown, $dateHeading)) {
            $pattern = '/^'.preg_quote($dateHeading, '/')."\n/m";

            $updated = preg_replace($pattern, $dateHeading."\n\n".$block, $markdown, 1);

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
