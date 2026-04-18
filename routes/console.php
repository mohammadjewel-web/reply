<?php

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('baileys:verify', function () {
    if (! config('services.baileys.enabled')) {
        $this->warn('BAILEYS_SERVICE_ENABLED is false in config. Set it true in .env if you use Baileys.');

        return Command::FAILURE;
    }

    $base = rtrim((string) config('services.baileys.url'), '/');
    $raw = config('services.baileys.secret');
    $secret = is_string($raw) ? trim($raw) : '';

    if ($base === '' || $secret === '') {
        $this->error('BAILEYS_SERVICE_URL or BAILEYS_SERVICE_SECRET is empty. Check .env and run php artisan config:clear.');

        return Command::FAILURE;
    }

    $this->info('Using BAILEYS_SERVICE_URL: '.$base);
    $this->info('Laravel secret length: '.strlen($secret).' (value is not shown).');

    try {
        $health = Http::timeout(8)->get($base.'/health');
        $this->line('GET /health → HTTP '.$health->status());
        if ($health->successful()) {
            $this->line($health->body());
        }

        $ping = Http::timeout(8)
            ->withHeaders(['X-Baileys-Secret' => $secret])
            ->post($base.'/session/ping');

        $this->line('POST /session/ping → HTTP '.$ping->status());
        $this->line($ping->body());

        if ($ping->status() === 404) {
            $this->warn('Route missing: pull latest baileys-service (rev 7+), restart Node, then retry.');

            return Command::FAILURE;
        }

        if ($ping->successful()) {
            $this->info('OK — Laravel config matches the running Baileys process.');

            return Command::SUCCESS;
        }

        if ($ping->status() === 401) {
            $this->error('401: Put the exact same BAILEYS_SERVICE_SECRET in Laravel .env and baileys-service/.env, then:');
            $this->line('  php artisan config:clear');
            $this->line('  sudo systemctl restart php*-fpm   # if applicable');
            $this->line('  pkill -f \'node server.mjs\'; cd baileys-service && source ~/.nvm/nvm.sh && nvm use 20 && nohup npm start >> /tmp/baileys.log 2>&1 &');
            $this->line('If you use php artisan config:cache in deploy, rebuild the cache after editing .env.');
        } else {
            $this->error('Ping did not succeed. Fix Baileys URL, firewall, or Node process.');
        }

        return Command::FAILURE;
    } catch (Throwable $e) {
        $this->error('Request failed: '.$e->getMessage());

        return Command::FAILURE;
    }
})->purpose('Check Baileys Node /health and shared secret via POST /session/ping');
