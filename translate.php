<?php
declare(strict_types=1);

// Machine-translate lang/en/*.php into lang/{ru,lt} using Lingva (no JSON files).
// Preserves :placeholders.

set_time_limit(0);

$locales = ['ru', 'lt'];
$sourceDir = __DIR__ . '/lang/en';
$targetBase = __DIR__ . '/lang';
$cacheDir = __DIR__ . '/lang/.translation-cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

function load_data(string $file): array
{
    $data = include $file;
    if (!is_array($data)) {
        throw new RuntimeException("File {$file} did not return array");
    }
    return $data;
}

function gather_strings(mixed $data, array &$list): void
{
    if (is_string($data)) {
        $list[] = $data;
        return;
    }
    if (is_array($data)) {
        foreach ($data as $value) {
            gather_strings($value, $list);
        }
    }
}

function mask_placeholders(string $text, array &$placeholders): string
{
    $i = 0;
    $placeholders = [];
    return preg_replace_callback('/:([A-Za-z0-9_]+)/', function ($m) use (&$placeholders, &$i) {
        $key = "__PH_{$i}__";
        $placeholders[$key] = $m[0];
        $i++;
        return $key;
    }, $text);
}

function unmask_placeholders(string $text, array $placeholders): string
{
    return $placeholders ? strtr($text, $placeholders) : $text;
}

function load_cache(string $locale, string $cacheDir): array
{
    $file = $cacheDir . "/{$locale}.php";
    if (is_file($file)) {
        $data = include $file;
        if (is_array($data)) {
            return $data;
        }
    }
    return [];
}

function save_cache(string $locale, string $cacheDir, array $data): void
{
    $file = $cacheDir . "/{$locale}.php";
    $content = "<?php\nreturn " . var_export($data, true) . ";\n";
    file_put_contents($file, $content);
}

function translate_single(string $text, string $target): string
{
    $ph = [];
    $masked = mask_placeholders($text, $ph);
    $url = 'https://lingva.ml/api/v1/en/' . rawurlencode($target) . '/' . rawurlencode($masked);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ],
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('curl error: ' . $err);
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 300) {
        throw new RuntimeException("HTTP {$code}: {$resp}");
    }
    $json = json_decode($resp, true);
    if (!is_array($json) || !isset($json['translation'])) {
        throw new RuntimeException('Unexpected translation response');
    }

    return unmask_placeholders((string) $json['translation'], $ph);
}

function translate_all(array $strings, string $locale, array &$cache): array
{
    $unique = array_values(array_unique($strings));
    $result = [];
    $counter = 0;
    foreach ($unique as $s) {
        if ($s === '') {
            $result[$s] = $s;
            continue;
        }
        if (isset($cache[$s])) {
            $result[$s] = $cache[$s];
            continue;
        }
        $attempts = 0;
        while (true) {
            try {
                $translated = translate_single($s, $locale);
                $cache[$s] = $translated;
                $result[$s] = $translated;
                break;
            } catch (Throwable $e) {
                $attempts++;
                if ($attempts >= 3) {
                    // Give up and keep English to avoid data loss
                    $cache[$s] = $s;
                    $result[$s] = $s;
                    break;
                }
                sleep(2);
            }
        }
        $counter++;
        if ($counter % 25 === 0) {
            save_cache($locale, $GLOBALS['cacheDir'], $cache);
        }
        usleep(200000); // throttle
    }
    save_cache($locale, $GLOBALS['cacheDir'], $cache);
    return $result;
}

function apply_translation(mixed $data, array $map): mixed
{
    if (is_string($data)) {
        $ph = [];
        $masked = mask_placeholders($data, $ph);
        $translated = $map[$data] ?? $map[$masked] ?? $data;
        return unmask_placeholders($translated, $ph);
    }
    if (is_array($data)) {
        $out = [];
        foreach ($data as $k => $v) {
            $out[$k] = apply_translation($v, $map);
        }
        return $out;
    }
    return $data;
}

function php_export(mixed $data, int $indent = 0): string
{
    $pad = str_repeat('    ', $indent);
    if (is_array($data)) {
        if ($data === []) {
            return '[]';
        }
        $lines = [];
        foreach ($data as $k => $v) {
            $key = is_int($k) ? $k : "'" . addslashes((string) $k) . "'";
            $val = php_export($v, $indent + 1);
            $lines[] = $pad . '    ' . $key . ' => ' . $val . ',';
        }
        return "[\n" . implode("\n", $lines) . "\n" . $pad . ']';
    }
    if (is_string($data)) {
        return "'" . addslashes($data) . "'";
    }
    if (is_bool($data)) {
        return $data ? 'true' : 'false';
    }
    if ($data === null) {
        return 'null';
    }
    return (string) $data;
}

$files = glob($sourceDir . '/*.php');
foreach ($files as $file) {
    $data = load_data($file);
    $strings = [];
    gather_strings($data, $strings);

    foreach ($locales as $locale) {
        echo "Translating " . basename($file) . " -> {$locale}\n";
        $cache = load_cache($locale, $cacheDir);
        $map = translate_all($strings, $locale, $cache);
        save_cache($locale, $cacheDir, $cache);
        $translated = apply_translation($data, $map);

        $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . php_export($translated) . ";\n";
        $targetPath = $targetBase . '/' . $locale . '/' . basename($file);
        file_put_contents($targetPath, $content);
    }
}

echo "Done\n";
