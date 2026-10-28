<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SLA CCT : détection quotidienne des dépassements de délai
Schedule::command('app:check-delais')->dailyAt('07:30')
    ->timezone(config('operations.schedule_timezone'))->withoutOverlapping(30)
    ->appendOutputTo(storage_path('logs/scheduler-'.date('Y-m-d').'.log'));

// Le témoin vérifie aussi qu'un travail est effectivement consommé dans la file.
Schedule::command('app:operations-heartbeat')->everyFiveMinutes()->withoutOverlapping(5);

// Sauvegardes automatisées (Spatie Backup)
// Les alertes de sauvegarde par e-mail seront configurées lors de leur recette.
// Ne pas envoyer vers l'adresse d'exemple héritée de la configuration Spatie.
Schedule::command('backup:run --disable-notifications')->dailyAt('02:00')
    ->timezone(config('operations.schedule_timezone'))->withoutOverlapping(180)
    ->runInBackground()->appendOutputTo(storage_path('logs/scheduler-'.date('Y-m-d').'.log'));
Schedule::command('backup:clean --disable-notifications')->dailyAt('03:00')
    ->timezone(config('operations.schedule_timezone'))->withoutOverlapping(120)
    ->runInBackground()->appendOutputTo(storage_path('logs/scheduler-'.date('Y-m-d').'.log'));
