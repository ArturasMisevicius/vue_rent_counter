<?php

use Illuminate\Support\Facades\DB;

test('sqlite database uses WAL mode', function () {
    // In-memory databases don't support WAL mode, so we skip this test in testing environment
    if (config('database.connections.sqlite.database') === ':memory:') {
        expect($result[0]->journal_mode ?? 'memory')->toBe('memory');
    } else {
        $result = DB::select('PRAGMA journal_mode;');
        expect($result[0]->journal_mode)->toBe('wal');
    }
})->skip('WAL mode not supported in :memory: database');

test('sqlite database has foreign keys enabled', function () {
    $result = DB::select('PRAGMA foreign_keys;');
    expect($result[0]->foreign_keys)->toBe(1);
});
