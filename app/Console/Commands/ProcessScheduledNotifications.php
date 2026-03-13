<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PlatformNotificationService;
use Illuminate\Console\Command;

class ProcessScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled platform notifications and send them';

    public function __construct(
        private PlatformNotificationService $notificationService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing scheduled notifications...');

        try {
            $this->notificationService->processScheduledNotifications();
            
            $this->info('Scheduled notifications processed successfully.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to process scheduled notifications: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
