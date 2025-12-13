<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class BoostMcpCommand extends Command
{
    /**
     * Start the Laravel Boost Model Context Protocol server.
     *
     * The server speaks JSON-RPC 2.0 over stdio with Content-Length framing
     * (same framing as LSP). Only protocol responses are written to stdout;
     * any internal logging should go to stderr or the application log.
     */
    protected $signature = 'boost:mcp';

    protected $description = 'Start the Laravel Boost MCP server (stdio)';

    public function handle(): int
    {
        $stdin = fopen('php://stdin', 'r');

        if ($stdin === false) {
            $this->output->getErrorOutput()->writeln('Failed to open STDIN.');

            return self::FAILURE;
        }

        stream_set_blocking($stdin, true);

        while (!feof($stdin)) {
            $message = $this->readMessage($stdin);

            if ($message === null) {
                continue;
            }

            $response = $this->dispatch($message);

            if ($response !== null) {
                $this->writeMessage($response);
            }

            if (($message['method'] ?? null) === 'shutdown') {
                break;
            }
        }

        return self::SUCCESS;
    }

    private function dispatch(array $message): ?array
    {
        $id = $message['id'] ?? null;
        $method = $message['method'] ?? null;

        try {
            return match ($method) {
                'initialize' => $this->initializeResponse($id),
                'ping' => $this->textResult($id, 'pong'),
                'shutdown' => $this->textResult($id, 'goodbye'),
                'tools/list' => $this->listToolsResponse($id),
                'tools/call' => $this->callToolResponse($id, $message['params'] ?? []),
                default => $this->errorResponse($id, 'Unknown method: '.$method),
            };
        } catch (\Throwable $e) {
            return $this->errorResponse(
                $id,
                'Server error: '.$e->getMessage(),
                data: ['trace' => collect($e->getTrace())->take(3)->all()]
            );
        }
    }

    private function initializeResponse($id): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'protocolVersion' => '1.0',
                'capabilities' => [
                    'tools' => ['listChanged' => false],
                ],
                'serverInfo' => [
                    'name' => 'Laravel Boost MCP',
                    'version' => app()->version(),
                ],
            ],
        ];
    }

    private function listToolsResponse($id): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'tools' => $this->toolDefinitions(),
            ],
        ];
    }

    private function callToolResponse($id, array $params): array
    {
        $name = $params['name'] ?? $params['tool'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if (!is_string($name)) {
            return $this->errorResponse($id, 'Tool name is required.');
        }

        return match ($name) {
            'search-docs' => $this->textResult($id, $this->handleSearchDocs($arguments)),
            'database-schema' => $this->textResult($id, $this->handleDatabaseSchema($arguments)),
            'database-query' => $this->textResult($id, $this->handleDatabaseQuery($arguments)),
            'application-info' => $this->textResult($id, $this->handleApplicationInfo()),
            'list-routes' => $this->textResult($id, $this->handleListRoutes($arguments)),
            'last-error' => $this->textResult($id, $this->handleLastError($arguments)),
            'read-log-entries' => $this->textResult($id, $this->handleReadLogs($arguments)),
            'tinker' => $this->textResult($id, 'Tinker is disabled for safety in this MCP server.', true),
            'get-config' => $this->textResult($id, $this->handleConfigLookup($arguments)),
            default => $this->errorResponse($id, "Unknown tool: {$name}"),
        };
    }

    private function toolDefinitions(): array
    {
        return [
            [
                'name' => 'search-docs',
                'description' => 'Search project documentation and source for a text query.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Text to search for (case-insensitive).',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 20,
                            'default' => 5,
                        ],
                    ],
                    'required' => ['query'],
                ],
            ],
            [
                'name' => 'database-schema',
                'description' => 'Summarize database tables and columns for the current connection.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'table' => [
                            'type' => 'string',
                            'description' => 'Optional table name to inspect (defaults to all tables).',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 100,
                            'default' => 50,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'database-query',
                'description' => 'Run a read-only SELECT query with optional limit.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'sql' => [
                            'type' => 'string',
                            'description' => 'SQL SELECT statement.',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 200,
                            'default' => 50,
                        ],
                    ],
                    'required' => ['sql'],
                ],
            ],
            [
                'name' => 'application-info',
                'description' => 'Get environment, framework, and app configuration highlights.',
                'inputSchema' => ['type' => 'object'],
            ],
            [
                'name' => 'list-routes',
                'description' => 'List registered routes with method, URI, and middleware.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 500,
                            'default' => 100,
                        ],
                        'method' => [
                            'type' => 'string',
                            'description' => 'Optional HTTP method filter (e.g., GET).',
                        ],
                        'uri' => [
                            'type' => 'string',
                            'description' => 'Substring filter for URI.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'last-error',
                'description' => 'Return the most recent error log entry.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'lines' => [
                            'type' => 'integer',
                            'minimum' => 10,
                            'maximum' => 400,
                            'default' => 120,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'read-log-entries',
                'description' => 'Read recent log entries with optional level filter.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'level' => [
                            'type' => 'string',
                            'description' => 'Optional level filter (e.g., error, warning, info).',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 400,
                            'default' => 120,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'tinker',
                'description' => 'Execute arbitrary PHP. Disabled here for safety, responds with a notice.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'code' => [
                            'type' => 'string',
                            'description' => 'PHP code to evaluate (disabled).',
                        ],
                    ],
                    'required' => ['code'],
                ],
            ],
            [
                'name' => 'get-config',
                'description' => 'Read a configuration value (dot notation).',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'key' => [
                            'type' => 'string',
                            'description' => 'Config key, e.g. app.name or database.default.',
                        ],
                    ],
                    'required' => ['key'],
                ],
            ],
        ];
    }

    private function handleSearchDocs(array $arguments): string
    {
        $query = trim((string) ($arguments['query'] ?? ''));

        if ($query === '') {
            return 'No query provided.';
        }

        $limit = (int) ($arguments['limit'] ?? 5);
        $limit = max(1, min(20, $limit));

        $paths = [
            base_path('docs'),
            base_path('resources'),
            base_path('app'),
            base_path('routes'),
            base_path('config'),
            base_path('README.md'),
        ];

        $finder = (new Finder())
            ->files()
            ->in($paths)
            ->ignoreDotFiles(true)
            ->ignoreUnreadableDirs()
            ->name(['*.md', '*.php', '*.blade.php', '*.txt'])
            ->notPath('node_modules')
            ->notPath('vendor')
            ->notPath('storage');

        $matches = [];

        foreach ($finder as $file) {
            if (count($matches) >= $limit) {
                break;
            }

            $content = $file->getContents();
            $pos = stripos($content, $query);

            if ($pos === false) {
                continue;
            }

            [$line, $snippet] = $this->extractSnippet($content, $pos, strlen($query));

            $matches[] = sprintf(
                '%s:%d => %s',
                $file->getRelativePathname(),
                $line,
                $snippet
            );
        }

        if (empty($matches)) {
            return "No matches found for '{$query}'.";
        }

        return "Results for '{$query}':\n- ".implode("\n- ", $matches);
    }

    private function handleDatabaseSchema(array $arguments): string
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $tableFilter = $arguments['table'] ?? null;
        $limit = max(1, min(100, (int) ($arguments['limit'] ?? 50)));

        $tables = [];

        if ($driver === 'sqlite') {
            $tables = array_map(
                fn ($row) => $row->name,
                DB::select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%' order by name")
            );
        } elseif ($driver === 'mysql') {
            $database = $connection->getDatabaseName();

            foreach (DB::select('show tables') as $row) {
                $property = 'Tables_in_'.$database;
                $tables[] = $row->$property ?? null;
            }
        } else {
            $rows = DB::select(
                'select table_name from information_schema.tables where table_schema = ? order by table_name',
                [$connection->getDatabaseName()]
            );

            foreach ($rows as $row) {
                $tables[] = $row->table_name ?? $row->TABLE_NAME ?? null;
            }
        }

        $tables = array_values(array_filter($tables, fn ($table) => is_string($table) && $table !== ''));

        $tables = array_values(array_filter($tables, function ($table) use ($tableFilter) {
            if (!$tableFilter) {
                return true;
            }

            return Str::contains(Str::lower($table), Str::lower($tableFilter));
        }));

        if (empty($tables)) {
            return 'No tables found for the current connection.';
        }

        $tables = array_slice($tables, 0, $limit);
        $lines = [];

        foreach ($tables as $table) {
            $columns = $this->describeTable($driver, (string) $table);
            $columnLines = collect($columns)->map(function ($col) {
                return sprintf(
                    '%s (%s)%s%s',
                    $col['name'],
                    $col['type'] ?? 'unknown',
                    ($col['nullable'] ?? false) ? '' : ' not null',
                    ($col['default'] ?? null) !== null ? ' default '.$col['default'] : ''
                );
            })->implode(', ');

            $lines[] = "{$table}: {$columnLines}";
        }

        return "Connection: {$driver}\n".implode("\n", $lines);
    }

    private function describeTable(string $driver, string $table): array
    {
        if ($driver === 'sqlite') {
            $rows = DB::select("pragma table_info('{$table}')");

            return collect($rows)->map(fn ($row) => [
                'name' => $row->name,
                'type' => $row->type,
                'nullable' => !$row->notnull,
                'default' => $row->dflt_value,
            ])->all();
        }

        if ($driver === 'mysql') {
            $rows = DB::select("show columns from `{$table}`");

            return collect($rows)->map(fn ($row) => [
                'name' => $row->Field,
                'type' => $row->Type,
                'nullable' => Str::lower($row->Null) === 'yes',
                'default' => $row->Default,
            ])->all();
        }

        return collect(DB::select("select column_name, data_type, is_nullable, column_default from information_schema.columns where table_name = ? order by ordinal_position", [$table]))
            ->map(fn ($row) => [
                'name' => $row->column_name ?? $row->COLUMN_NAME ?? 'unknown',
                'type' => $row->data_type ?? $row->DATA_TYPE ?? 'unknown',
                'nullable' => ($row->is_nullable ?? $row->IS_NULLABLE ?? '') === 'YES',
                'default' => $row->column_default ?? $row->COLUMN_DEFAULT ?? null,
            ])
            ->all();
    }

    private function handleDatabaseQuery(array $arguments): string
    {
        $sql = trim((string) ($arguments['sql'] ?? ''));

        if ($sql === '') {
            return 'SQL is required.';
        }

        if (!Str::startsWith(Str::lower($sql), 'select')) {
            return 'Only SELECT statements are permitted.';
        }

        $limit = max(1, min(200, (int) ($arguments['limit'] ?? 50)));

        if (!Str::contains(Str::lower($sql), ' limit ')) {
            $sql .= " limit {$limit}";
        }

        $rows = DB::select($sql);

        if (empty($rows)) {
            return 'Query executed successfully, no rows returned.';
        }

        $preview = collect($rows)->take($limit)->map(fn ($row) => (array) $row)->all();

        return "Rows: ".count($rows).", showing up to {$limit}:\n".json_encode($preview, JSON_PRETTY_PRINT);
    }

    private function handleApplicationInfo(): string
    {
        $connection = DB::connection();

        return json_encode([
            'app' => [
                'name' => Config::get('app.name'),
                'env' => app()->environment(),
                'debug' => Config::get('app.debug'),
                'url' => Config::get('app.url'),
                'locale' => Config::get('app.locale'),
                'timezone' => Config::get('app.timezone'),
            ],
            'framework' => [
                'laravel' => app()->version(),
                'php' => PHP_VERSION,
            ],
            'infrastructure' => [
                'database' => [
                    'driver' => $connection->getDriverName(),
                    'database' => $connection->getDatabaseName(),
                ],
                'cache' => Config::get('cache.default'),
                'queue' => Config::get('queue.default'),
            ],
        ], JSON_PRETTY_PRINT);
    }

    private function handleListRoutes(array $arguments): string
    {
        $limit = max(1, min(500, (int) ($arguments['limit'] ?? 100)));
        $methodFilter = Str::upper((string) ($arguments['method'] ?? ''));
        $uriFilter = Str::lower((string) ($arguments['uri'] ?? ''));

        $routes = collect(Route::getRoutes())->filter(function ($route) use ($methodFilter, $uriFilter) {
            if ($methodFilter && !in_array($methodFilter, $route->methods(), true)) {
                return false;
            }

            if ($uriFilter && !Str::contains(Str::lower($route->uri()), $uriFilter)) {
                return false;
            }

            return true;
        })->take($limit);

        if ($routes->isEmpty()) {
            return 'No routes matched the provided filters.';
        }

        $lines = $routes->map(function ($route) {
            $methods = implode('|', $route->methods());
            $uri = $route->uri();
            $name = $route->getName() ?? '-';
            $middleware = implode(',', $route->gatherMiddleware());

            return "{$methods} {$uri} [name: {$name}] [middleware: {$middleware}]";
        })->implode("\n");

        return $lines;
    }

    private function handleLastError(array $arguments): string
    {
        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            return 'Log file not found.';
        }

        $linesToRead = max(10, min(400, (int) ($arguments['lines'] ?? 120)));
        $lines = $this->tailFile($logPath, $linesToRead);

        foreach (array_reverse($lines) as $line) {
            if (Str::contains(Str::lower($line), ['error', 'exception', 'critical'])) {
                return "Most recent error line:\n{$line}\n\nContext:\n".implode("\n", $lines);
            }
        }

        return "No error lines found. Recent log:\n".implode("\n", $lines);
    }

    private function handleReadLogs(array $arguments): string
    {
        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            return 'Log file not found.';
        }

        $limit = max(1, min(400, (int) ($arguments['limit'] ?? 120)));
        $level = Str::lower((string) ($arguments['level'] ?? ''));
        $lines = $this->tailFile($logPath, $limit);

        if ($level) {
            $lines = array_values(array_filter($lines, function ($line) use ($level) {
                return Str::contains(Str::lower($line), $level);
            }));
        }

        if (empty($lines)) {
            return 'No log entries matched the provided filters.';
        }

        return implode("\n", $lines);
    }

    private function handleConfigLookup(array $arguments): string
    {
        $key = trim((string) ($arguments['key'] ?? ''));

        if ($key === '') {
            return 'Config key is required.';
        }

        $value = Config::get($key, '__missing__');

        if ($value === '__missing__') {
            return "Config key '{$key}' not found.";
        }

        return "{$key}: ".json_encode($value, JSON_PRETTY_PRINT);
    }

    private function extractSnippet(string $content, int $position, int $length): array
    {
        $context = 120;
        $start = max(0, $position - $context);
        $end = min(strlen($content), $position + $length + $context);

        $snippet = substr($content, $start, $end - $start);
        $snippet = preg_replace('/\s+/', ' ', $snippet);

        $line = substr_count(substr($content, 0, $position), "\n") + 1;

        return [$line, trim($snippet)];
    }

    private function tailFile(string $path, int $lines): array
    {
        $data = File::lines($path)->toArray();

        return array_slice($data, -$lines);
    }

    private function readMessage($stream): ?array
    {
        $headers = [];

        while (($line = fgets($stream)) !== false) {
            $line = trim($line);

            if ($line === '') {
                break;
            }

            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }

        if (!isset($headers['content-length'])) {
            return null;
        }

        $length = (int) $headers['content-length'];
        $body = '';

        while (strlen($body) < $length && ($chunk = fread($stream, $length - strlen($body))) !== false) {
            $body .= $chunk;
        }

        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            $this->writeMessage([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => [
                    'code' => -32700,
                    'message' => 'Parse error',
                ],
            ]);

            return null;
        }

        return $decoded;
    }

    private function writeMessage(array $payload): void
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $contentLength = strlen($json);

        fwrite(STDOUT, "Content-Length: {$contentLength}\r\n\r\n{$json}");
    }

    private function textResult($id, string $text, bool $isError = false): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $text,
                    ],
                ],
                'isError' => $isError,
            ],
        ];
    }

    private function errorResponse($id, string $message, int $code = -32000, array $data = []): ?array
    {
        if ($id === null) {
            return null;
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
                'data' => $data,
            ],
        ];
    }
}
