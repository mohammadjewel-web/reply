<?php

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Throwable;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('baileys:verify {--dotenv= : Absolute path to baileys-service/.env (default: baileys-service/.env under project root)}', function () {
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

    $dotenvPath = $this->option('dotenv') ?: base_path('baileys-service/.env');
    $this->info('Using BAILEYS_SERVICE_URL: '.$base);
    $this->info('Laravel loaded secret length: '.strlen($secret).' (value is not shown).');

    if (is_readable($dotenvPath)) {
        $fileSecret = null;
        $lines = @file($dotenvPath, FILE_IGNORE_NEW_LINES);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $t = trim($line);
                if ($t === '' || str_starts_with($t, '#')) {
                    continue;
                }
                if (preg_match('/^BAILEYS_SERVICE_SECRET=(.*)$/', $t, $m)) {
                    $v = trim($m[1]);
                    if (
                        (str_starts_with($v, '"') && str_ends_with($v, '"'))
                        || (str_starts_with($v, "'") && str_ends_with($v, "'"))
                    ) {
                        $v = substr($v, 1, -1);
                    }
                    $fileSecret = trim($v);
                    break;
                }
            }
        }
        $this->line('Peer file: '.$dotenvPath);
        if ($fileSecret !== null && $fileSecret !== '') {
            $this->info('Peer file secret length: '.strlen($fileSecret).' (value is not shown).');
            if (hash_equals($secret, $fileSecret)) {
                $this->info('Laravel config and peer .env agree on the secret.');
            } else {
                $this->error('MISMATCH: Laravel .env and '.$dotenvPath.' have different BAILEYS_SERVICE_SECRET. Copy the same value into both files, then php artisan config:clear and restart Baileys.');
            }
        } else {
            $this->warn('No BAILEYS_SERVICE_SECRET= line found in '.$dotenvPath.'. Add it or pass --dotenv=/correct/path/.env');
        }
    } else {
        $this->warn('Cannot read '.$dotenvPath.' — create it or pass --dotenv=/path/to/baileys-service/.env');
    }

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
            $this->info('OK — running Node accepted the same secret Laravel uses for HTTP calls.');

            return Command::SUCCESS;
        }

        if ($ping->status() === 401) {
            $this->error('401 from Node: the RUNNING process does not use the same secret as Laravel.');
            $this->line('Fix: 1) Align BAILEYS_SERVICE_SECRET in Laravel .env and baileys-service/.env');
            $this->line('       2) php artisan config:clear && (restart php-fpm if production)');
            $this->line('       3) pkill -f \'node server.mjs\'; cd baileys-service && nvm use 20 && nohup npm start >> /tmp/baileys.log 2>&1 &');
            $this->line('If Laravel and peer .env MATCH above but ping still 401, Node was not restarted after editing .env.');
        } else {
            $this->error('Ping did not succeed. Fix Baileys URL, firewall, or Node process.');
        }

        return Command::FAILURE;
    } catch (Throwable $e) {
        $this->error('Request failed: '.$e->getMessage());

        return Command::FAILURE;
    }
})->purpose('Check Baileys Node /health, compare secrets to baileys-service/.env, POST /session/ping');
