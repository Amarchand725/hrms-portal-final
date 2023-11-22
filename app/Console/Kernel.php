<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
     
    protected $commands = [  
        'App\Console\Commands\SendBirthdayEmails',
        'App\Console\Commands\SendNoticePeriodEndNotifications',
        'App\Console\Commands\MonthlySalaryReport',
    ];
     
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('birthday:send')
         ->timezone('Asia/Karachi')
         ->daily();
         
        $schedule->command('notice:end')
        ->timezone('Asia/Karachi')
        ->daily();
        
        $schedule->command('monthly-salary-report')
        ->timezone('Asia/Karachi')
        ->lastDayOfMonth('01:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
