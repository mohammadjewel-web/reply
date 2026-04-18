<?php

namespace App\Support;

use Illuminate\Support\Carbon;

final class InboundMessageTimestamp
{
    /**
     * Parse UNIX epoch from webhooks that may send seconds or milliseconds.
     */
    public static function parse(int|string|null $raw): ?Carbon
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $n = (int) $raw;
        if ($n <= 0) {
            return null;
        }

        if ($n >= 1_000_000_000_000) {
            return Carbon::createFromTimestampMs($n)->timezone(config('app.timezone'));
        }

        return Carbon::createFromTimestamp($n, 'UTC')->timezone(config('app.timezone'));
    }
}
