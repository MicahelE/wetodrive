<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test if logging is working';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Laravel logging...');
        
        Log::debug('This is a debug message');
        Log::info('This is an info message');
        Log::warning('This is a warning message');
        Log::error('This is an error message');
        
        $this->info('Log messages have been written. Check storage/logs/laravel.log');
        
        // Try to read the log file
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $this->info('Log file exists at: ' . $logFile);
            $this->info('Last 10 lines of log:');
            $lines = file($logFile);
            $lastLines = array_slice($lines, -10);
            foreach ($lastLines as $line) {
                $this->line($line);
            }
        } else {
            $this->error('Log file does not exist at: ' . $logFile);
        }
    }
}
