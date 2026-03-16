<?php

declare(strict_types=1);

namespace App\Services\Optimized;

use App\Models\MeterReading;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator as LaravelPaginator;
use Carbon\Carbon;

/**
 * Pagination Optimization Service
 * 
 * Addresses performance issues with large datasets and provides
 * alternatives to standard OFFSET-based pagination
 */
final readonly class PaginationOptimizationService
{
    /**
     * PROBLEM: Standard OFFSET pagination becomes slow with large datasets
     * 
     * SELECT * FROM meter_readings ORDER BY id LIMIT 50 OFFSET 100000;
     * 
     * This query gets slower as the offset increases because the database
     * must scan and skip 100,000 rows before returning results.
     */

    /**
     * BAD: Standard Laravel pagination on large table
     */
    public function getReadingsStandardPagination(int $tenantId, int $page = 1, int $perPage = 50): LengthAwarePaginator
    {
        return MeterReading::where('tenant_id', $tenantId)
            ->with(['meter:id,serial_number,type', 'enteredBy:id,name'])
            ->orderBy('reading_date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * BETTER: Simple pagination (no total count)
     * 
     * Removes the expensive COUNT(*) query and uses LIMIT + 1 to check for next page
     */
    public function getReadingsSimplePagination(int $tenantId, int $page = 1, int $perPage = 50): Paginator
    {
        return MeterReading::where('tenant_id', $tenantId)
            ->with(['meter:id,serial_number,type', 'enteredBy:id,name'])
            ->orderBy('reading_date', 'desc')
            ->simplePaginate($perPage, ['*'], 'page', $page);
    }

    /**
     * BEST: Cursor-based pagination (keyset pagination)
     * 
     * Uses the last seen value as a cursor instead of OFFSET
     * Performance remains constant regardless of dataset size
     */
    public function getReadingsCursorPagination(
        int $tenantId, 
        ?string $cursor = null, 
        int $limit = 50,
        string $direction = 'desc'
    ): array {
        $query = MeterReading::where('tenant_id', $tenantId)
            ->with(['meter:id,serial_number,type', 'enteredBy:id,name'])
            ->select([
                'id', 'meter_id', 'reading_date', 'value', 'zone',
                'validation_status', 'entered_by', 'created_at'
            ]);

        // Apply cursor condition
        if ($cursor) {
            $cursorData = $this->decodeCursor($cursor);
            
            if ($direction === 'desc') {
                $query->where(function ($q) use ($cursorData) {
                    $q->where('reading_date', '<', $cursorData['reading_date'])
                      ->orWhere(function ($q2) use ($cursorData) {
                          $q2->where('reading_date', '=', $cursorData['reading_date'])
                             ->where('id', '<', $cursorData['id']);
                      });
                });
            } else {
                $query->where(function ($q) use ($cursorData) {
                    $q->where('reading_date', '>', $cursorData['reading_date'])
                      ->orWhere(function ($q2) use ($cursorData) {
                          $q2->where('reading_date', '=', $cursorData['reading_date'])
                             ->where('id', '>', $cursorData['id']);
                      });
                });
            }
        }

        // Order and limit
        $query->orderBy('reading_date', $direction)
              ->orderBy('id', $direction)
              ->limit($limit + 1); // +1 to check if there are more results

        $results = $query->get();
        $hasMore = $results->count() > $limit;
        
        if ($hasMore) {
            $results->pop(); // Remove the extra item
        }

        // Generate next cursor
        $nextCursor = null;
        if ($hasMore && $results->isNotEmpty()) {
            $lastItem = $results->last();
            $nextCursor = $this->encodeCursor([
                'reading_date' => $lastItem->reading_date->toISOString(),
                'id' => $lastItem->id,
            ]);
        }

        return [
            'data' => $results,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
            'count' => $results->count(),
        ];
    }

    /**
     * Keyset pagination for time-series data (optimized for meter readings)
     */
    public function getReadingsKeysetPagination(
        int $tenantId,
        ?Carbon $lastReadingDate = null,
        ?int $lastId = null,
        int $limit = 50
    ): array {
        $query = DB::table('meter_readings as mr')
            ->join('meters as m', 'mr.meter_id', '=', 'm.id')
            ->join('users as u', 'mr.entered_by', '=', 'u.id')
            ->select([
                'mr.id',
                'mr.reading_date',
                'mr.value',
                'mr.zone',
                'mr.validation_status',
                'mr.created_at',
                'm.serial_number as meter_serial',
                'm.type as meter_type',
                'u.name as entered_by_name',
            ])
            ->where('mr.tenant_id', $tenantId);

        // Keyset condition for consistent ordering
        if ($lastReadingDate && $lastId) {
            $query->where(function ($q) use ($lastReadingDate, $lastId) {
                $q->where('mr.reading_date', '<', $lastReadingDate)
                  ->orWhere(function ($q2) use ($lastReadingDate, $lastId) {
                      $q2->where('mr.reading_date', '=', $lastReadingDate)
                         ->where('mr.id', '<', $lastId);
                  });
            });
        }

        $results = $query->orderByDesc('mr.reading_date')
                        ->orderByDesc('mr.id')
                        ->limit($limit + 1)
                        ->get();

        $hasMore = $results->count() > $limit;
        if ($hasMore) {
            $results->pop();
        }

        return [
            'data' => $results,
            'has_more' => $hasMore,
            'last_reading_date' => $results->isNotEmpty() ? $results->last()->reading_date : null,
            'last_id' => $results->isNotEmpty() ? $results->last()->id : null,
        ];
    }

    /**
     * Load More Pattern (infinite scroll)
     */
    public function getReadingsLoadMore(
        int $tenantId,
        ?int $lastId = null,
        int $limit = 20
    ): array {
        $query = MeterReading::where('tenant_id', $tenantId)
            ->with(['meter:id,serial_number', 'enteredBy:id,name']);

        if ($lastId) {
            $query->where('id', '<', $lastId);
        }

        $results = $query->orderByDesc('id')
                        ->limit($limit + 1)
                        ->get();

        $hasMore = $results->count() > $limit;
        if ($hasMore) {
            $results->pop();
        }

        return [
            'readings' => $results,
            'has_more' => $hasMore,
            'last_id' => $results->isNotEmpty() ? $results->last()->id : null,
        ];
    }

    /**
     * Hybrid pagination for search results
     * 
     * Uses cursor pagination for performance but provides approximate counts
     */
    public function searchReadingsHybridPagination(
        int $tenantId,
        string $searchTerm,
        ?string $cursor = null,
        int $limit = 50
    ): array {
        // Get approximate count using sampling
        $approximateCount = $this->getApproximateSearchCount($tenantId, $searchTerm);

        // Build search query
        $query = DB::table('meter_readings as mr')
            ->join('meters as m', 'mr.meter_id', '=', 'm.id')
            ->where('mr.tenant_id', $tenantId)
            ->where(function ($q) use ($searchTerm) {
                $q->where('m.serial_number', 'like', "%{$searchTerm}%")
                  ->orWhere('mr.value', 'like', "%{$searchTerm}%")
                  ->orWhere('mr.zone', 'like', "%{$searchTerm}%");
            });

        // Apply cursor if provided
        if ($cursor) {
            $cursorData = $this->decodeCursor($cursor);
            $query->where('mr.id', '<', $cursorData['id']);
        }

        $results = $query->select([
                'mr.id', 'mr.reading_date', 'mr.value', 'mr.zone',
                'm.serial_number', 'm.type'
            ])
            ->orderByDesc('mr.id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $results->count() > $limit;
        if ($hasMore) {
            $results->pop();
        }

        $nextCursor = null;
        if ($hasMore && $results->isNotEmpty()) {
            $nextCursor = $this->encodeCursor(['id' => $results->last()->id]);
        }

        return [
            'data' => $results,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
            'approximate_count' => $approximateCount,
        ];
    }

    /**
     * Date-range pagination for time-series data
     */
    public function getReadingsDateRangePagination(
        int $tenantId,
        Carbon $startDate,
        Carbon $endDate,
        ?Carbon $cursorDate = null,
        int $limit = 100
    ): array {
        $query = MeterReading::where('tenant_id', $tenantId)
            ->whereBetween('reading_date', [$startDate, $endDate]);

        if ($cursorDate) {
            $query->where('reading_date', '>', $cursorDate);
        }

        $results = $query->orderBy('reading_date')
                        ->orderBy('id')
                        ->limit($limit + 1)
                        ->get();

        $hasMore = $results->count() > $limit;
        if ($hasMore) {
            $results->pop();
        }

        return [
            'data' => $results,
            'has_more' => $hasMore,
            'next_cursor_date' => $results->isNotEmpty() ? $results->last()->reading_date : null,
            'progress_percentage' => $this->calculateProgress($startDate, $endDate, $cursorDate),
        ];
    }

    /**
     * Custom paginator for complex queries
     */
    public function createCustomPaginator(
        array $items,
        int $total,
        int $perPage,
        int $currentPage,
        string $path
    ): LengthAwarePaginator {
        return new LaravelPaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $path,
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Optimized count for large tables
     */
    private function getApproximateSearchCount(int $tenantId, string $searchTerm): int
    {
        // Use table statistics for very large tables
        $tableStats = DB::select("
            SELECT table_rows 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = 'meter_readings'
        ");

        $totalRows = $tableStats[0]->table_rows ?? 0;

        // Sample-based estimation for search results
        if ($totalRows > 100000) {
            $sampleSize = min(1000, $totalRows * 0.01);
            $sampleCount = DB::table('meter_readings as mr')
                ->join('meters as m', 'mr.meter_id', '=', 'm.id')
                ->where('mr.tenant_id', $tenantId)
                ->where(function ($q) use ($searchTerm) {
                    $q->where('m.serial_number', 'like', "%{$searchTerm}%")
                      ->orWhere('mr.value', 'like', "%{$searchTerm}%");
                })
                ->limit($sampleSize)
                ->count();

            return (int) (($sampleCount / $sampleSize) * $totalRows);
        }

        // Exact count for smaller tables
        return DB::table('meter_readings as mr')
            ->join('meters as m', 'mr.meter_id', '=', 'm.id')
            ->where('mr.tenant_id', $tenantId)
            ->where(function ($q) use ($searchTerm) {
                $q->where('m.serial_number', 'like', "%{$searchTerm}%")
                  ->orWhere('mr.value', 'like', "%{$searchTerm}%");
            })
            ->count();
    }

    private function encodeCursor(array $data): string
    {
        return base64_encode(json_encode($data));
    }

    private function decodeCursor(string $cursor): array
    {
        return json_decode(base64_decode($cursor), true);
    }

    private function calculateProgress(Carbon $start, Carbon $end, ?Carbon $current): float
    {
        if (!$current) {
            return 0.0;
        }

        $totalDuration = $end->diffInSeconds($start);
        $currentProgress = $current->diffInSeconds($start);

        return min(100.0, ($currentProgress / $totalDuration) * 100);
    }
}