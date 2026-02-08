<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Database Health Section --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-circle-stack class="w-5 h-5" />
                    Database Health
                </div>
            </x-slot>

            @php
                $db = $this->getDatabaseHealth();
            @endphp

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                @if($db['color'] === 'success') bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20
                                @elseif($db['color'] === 'warning') bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-400 dark:ring-yellow-500/20
                                @else bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20
                                @endif">
                                {{ $db['status'] }}
                            </span>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Connection</div>
                        <div class="mt-1 text-lg font-semibold">{{ $db['connection'] ?? 'Unknown' }}</div>
                    </div>

                    @if(isset($db['tableCount']))
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Tables</div>
                            <div class="mt-1 text-lg font-semibold">{{ $db['tableCount'] }}</div>
                        </div>
                    @endif
                </div>

                @if(isset($db['dbSize']))
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm font-medium mb-2">Database Size</div>
                        <div class="text-2xl font-bold">{{ $db['dbSize'] }} MB</div>
                    </div>
                @endif

                @if(isset($db['tableSizes']) && count($db['tableSizes']) > 0)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm font-medium mb-3">Top Tables by Row Count</div>
                        <div class="space-y-2">
                            @foreach($db['tableSizes'] as $table)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm">{{ $table['name'] }}</span>
                                    <span class="text-sm font-medium">{{ number_format($table['rows']) }} rows</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(isset($db['error']))
                    <div class="rounded-lg bg-red-50 dark:bg-red-500/10 p-4">
                        <div class="text-sm text-red-700 dark:text-red-400">{{ $db['error'] }}</div>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Backup Status Section --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-archive-box class="w-5 h-5" />
                    Backup Status
                </div>
            </x-slot>

            @php
                $backup = $this->getBackupStatus();
            @endphp

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                @if($backup['color'] === 'success') bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20
                                @elseif($backup['color'] === 'warning') bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-400 dark:ring-yellow-500/20
                                @else bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20
                                @endif">
                                {{ $backup['status'] }}
                            </span>
                        </div>
                    </div>

                    @if(isset($backup['lastBackup']))
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Last Backup</div>
                            <div class="mt-1 text-sm font-medium">{{ $backup['lastBackup'] }}</div>
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Backup Size</div>
                            <div class="mt-1 text-lg font-semibold">{{ $backup['backupSize'] }} MB</div>
                        </div>
                    @endif
                </div>

                @if(isset($backup['location']))
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Location</div>
                        <div class="text-sm font-mono">{{ $backup['location'] }}</div>
                    </div>
                @endif

                @if(isset($backup['backupCount']))
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Backups</div>
                        <div class="mt-1 text-lg font-semibold">{{ $backup['backupCount'] }}</div>
                    </div>
                @endif

                @if(isset($backup['error']))
                    <div class="rounded-lg bg-red-50 dark:bg-red-500/10 p-4">
                        <div class="text-sm text-red-700 dark:text-red-400">{{ $backup['error'] }}</div>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Queue Status Section --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-queue-list class="w-5 h-5" />
                    Queue Status
                </div>
            </x-slot>

            @php
                $queue = $this->getQueueStatus();
            @endphp

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                @if($queue['color'] === 'success') bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20
                                @elseif($queue['color'] === 'warning') bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-400 dark:ring-yellow-500/20
                                @else bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20
                                @endif">
                                {{ $queue['status'] }}
                            </span>
                        </div>
                    </div>

                    @if(isset($queue['pendingJobs']))
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Pending Jobs</div>
                            <div class="mt-1 text-lg font-semibold">{{ number_format($queue['pendingJobs']) }}</div>
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Failed Jobs</div>
                            <div class="mt-1 text-lg font-semibold">{{ number_format($queue['failedJobs']) }}</div>
                        </div>
                    @endif
                </div>

                @if(isset($queue['error']))
                    <div class="rounded-lg bg-red-50 dark:bg-red-500/10 p-4">
                        <div class="text-sm text-red-700 dark:text-red-400">{{ $queue['error'] }}</div>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Storage Metrics Section --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-server class="w-5 h-5" />
                    Storage Metrics
                </div>
            </x-slot>

            @php
                $storage = $this->getStorageMetrics();
            @endphp

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                @if($storage['color'] === 'success') bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20
                                @elseif($storage['color'] === 'warning') bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-400 dark:ring-yellow-500/20
                                @else bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20
                                @endif">
                                {{ $storage['status'] }}
                            </span>
                        </div>
                    </div>

                    @if(isset($storage['diskUsagePercent']))
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Disk Usage</div>
                            <div class="mt-1 text-lg font-semibold">{{ $storage['diskUsagePercent'] }}%</div>
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Disk Free</div>
                            <div class="mt-1 text-lg font-semibold">{{ $storage['diskFree'] }} GB</div>
                        </div>
                    @endif
                </div>

                @if(isset($storage['diskTotal']))
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm font-medium mb-3">Disk Space</div>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Total</span>
                                <span class="text-sm font-medium">{{ $storage['diskTotal'] }} GB</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Used</span>
                                <span class="text-sm font-medium">{{ $storage['diskUsed'] }} GB</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Free</span>
                                <span class="text-sm font-medium">{{ $storage['diskFree'] }} GB</span>
                            </div>
                        </div>
                        
                        {{-- Progress bar --}}
                        <div class="mt-3">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full
                                    @if($storage['diskUsagePercent'] > 90) bg-red-600
                                    @elseif($storage['diskUsagePercent'] > 80) bg-yellow-600
                                    @else bg-green-600
                                    @endif"
                                    style="width: {{ $storage['diskUsagePercent'] }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(isset($storage['dbSize']))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Database Size</div>
                            <div class="mt-1 text-lg font-semibold">{{ $storage['dbSize'] }} MB</div>
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Log Files Size</div>
                            <div class="mt-1 text-lg font-semibold">{{ $storage['logSize'] }} MB</div>
                        </div>
                    </div>
                @endif

                @if(isset($storage['error']))
                    <div class="rounded-lg bg-red-50 dark:bg-red-500/10 p-4">
                        <div class="text-sm text-red-700 dark:text-red-400">{{ $storage['error'] }}</div>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Cache Status Section --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-bolt class="w-5 h-5" />
                    Cache Status
                </div>
            </x-slot>

            @php
                $cache = $this->getCacheStatus();
            @endphp

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                @if($cache['color'] === 'success') bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20
                                @elseif($cache['color'] === 'warning') bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-400 dark:ring-yellow-500/20
                                @else bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20
                                @endif">
                                {{ $cache['status'] }}
                            </span>
                        </div>
                    </div>

                    @if(isset($cache['connection']))
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Connection</div>
                            <div class="mt-1 text-lg font-semibold">{{ $cache['connection'] }}</div>
                        </div>
                    @endif

                    @if(isset($cache['driver']))
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Driver</div>
                            <div class="mt-1 text-lg font-semibold">{{ $cache['driver'] }}</div>
                        </div>
                    @endif
                </div>

                @if(isset($cache['error']))
                    <div class="rounded-lg bg-red-50 dark:bg-red-500/10 p-4">
                        <div class="text-sm text-red-700 dark:text-red-400">{{ $cache['error'] }}</div>
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
