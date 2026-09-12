<?php

use App\Services\MoveService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('moves:auto-decide', function (MoveService $moveService) {
    $decided = $moveService->autoDecidePendingMoves();
    $this->info(count($decided) . ' mudança(s) decidida(s) automaticamente.');
})->purpose('Aprova ou recusa automaticamente mudanças sem decisão do síndico a menos de 24h do acontecimento');

Schedule::command('moves:auto-decide')->hourly();
