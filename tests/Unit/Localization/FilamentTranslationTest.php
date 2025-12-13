<?php

declare(strict_types=1);

namespace Tests\Unit\Localization;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

final class FilamentTranslationTest extends TestCase
{
    private array $missingKeys = [];

    public function test_filament_resources_have_lithuanian_translations(): void
    {
        $this->scanFilamentFiles();
        
        if (!empty($this->missingKeys)) {
            $this->fail(
                "Missing Lithuanian translations:\n" . 
                implode("\n", array_map(fn($key) => "- {$key}", $this->missingKeys))
            );
        }

        expect($this->missingKeys)->toBeEmpty();
    }

    private function scanFilamentFiles(): void
    {
        $filamentPaths = [
            app_path('Filament'),
        ];

        foreach ($filamentPaths as $path) {
            if (File::exists($path)) {
                $this->scanDirectory($path);
            }
        }
    }

    private function scanDirectory(string $directory): void
    {
        $files = File::allFiles($directory);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $this->scanFile($file->getPathname());
            }
        }
    }

    private function scanFile(string $filePath): void
    {
        $content = File::get($filePath);
        
        // Find translation keys using __() function
        preg_match_all('/__(\'([^\']+)\'|\\"([^\\"]+)\\")/', $content, $matches);
        
        foreach ($matches[2] as $key) {
            if (!empty($key)) {
                $this->checkTranslationKey($key);
            }
        }
        
        foreach ($matches[3] as $key) {
            if (!empty($key)) {
                $this->checkTranslationKey($key);
            }
        }

        // Find translation keys using trans() function
        preg_match_all('/trans\(\'([^\']+)\'|\\"([^\\"]+)\\"\)/', $content, $transMatches);
        
        foreach ($transMatches[1] as $key) {
            if (!empty($key)) {
                $this->checkTranslationKey($key);
            }
        }
        
        foreach ($transMatches[2] as $key) {
            if (!empty($key)) {
                $this->checkTranslationKey($key);
            }
        }
    }

    private function checkTranslationKey(string $key): void
    {
        // Skip dynamic keys or keys with variables
        if (Str::contains($key, ['$', '{', '}', '::'])) {
            return;
        }

        // Check if translation exists in Lithuanian
        app()->setLocale('lt');
        $translation = __($key);
        
        // If translation equals the key, it means translation is missing
        if ($translation === $key && !in_array($key, $this->missingKeys)) {
            $this->missingKeys[] = $key;
        }
    }
}