<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('app:daily-task')->everyMinute();
        $schedule->command('app:plan-ebay-uploading-products')->dailyAt('02:00');
        $schedule->command('app:update-product-prices-in-ebay')->dailyAt('03:45');
        $schedule->command('app:plan-collecting-products-data')->dailyAt('04:00');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
