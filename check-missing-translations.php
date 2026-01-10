<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

app()->setLocale('lt');

$missingKeys = [];
$filamentPath = app_path('Filament');
$files = \Illuminate\Support\Facades\File::allFiles($filamentPath);

foreach ($files as $file) {
    if ($file->getExtension() === 'php') {
        $content = \Illuminate\Support\Facades\File::get($file->getPathname());
        
        // Find __() calls
        preg_match_all('/\b__\(\s*([\'"])([^\'"]+)\1\s*(?:,|\))/m', $content, $matches);
        
        foreach ($matches[2] as $key) {
            if ($key !== '' && !str_contains($key, '{') && !str_contains($key, '}') && !str_contains($key, '::')) {
                $translation = __($key);
                if ($translation === $key && !in_array($key, $missingKeys)) {
                    $missingKeys[] = $key;
                }
            }
        }
    }
}

echo "Missing translations (" . count($missingKeys) . "):\n";
foreach ($missingKeys as $key) {
    echo "- $key\n";
}
