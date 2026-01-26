<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Automatically sync bookings from Bokun API every 15 minutes
| This ensures all bookings have participant names populated
|
*/

Schedule::command('bokun:sync --limit=100')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/bokun-sync.log'));

/*
|--------------------------------------------------------------------------
| Ticket Reminder Notifications
|--------------------------------------------------------------------------
|
| Send WhatsApp reminders to admins about unsent tickets
| Runs at 07:00, 11:00, and 14:00 Florence time (Europe/Rome)
|
*/

Schedule::command('tickets:remind')
    ->dailyAt('07:00')
    ->timezone('Europe/Rome')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ticket-reminders.log'));

Schedule::command('tickets:remind')
    ->dailyAt('11:00')
    ->timezone('Europe/Rome')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ticket-reminders.log'));

Schedule::command('tickets:remind')
    ->dailyAt('14:00')
    ->timezone('Europe/Rome')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ticket-reminders.log'));
