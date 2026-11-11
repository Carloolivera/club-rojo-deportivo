<?php

namespace App\Support;

/**
 * Lookup fingerprint for a DNI.
 *
 * The DNI itself is stored encrypted, so the database cannot search it.
 * We keep a keyed fingerprint (HMAC) next to it for exact-match lookups.
 * A plain SHA-256 would not be enough: there are only ~10^8 possible DNIs,
 * so anyone with a copy of the table could rebuild every number in seconds.
 * With HMAC they would also need the server-side key.
 */
final class Dni
{
    public static function normalize(string $dni): string
    {
        return preg_replace('/\D/', '', $dni) ?? '';
    }

    public static function hash(string $dni): string
    {
        return hash_hmac('sha256', self::normalize($dni), self::key());
    }

    private static function key(): string
    {
        // Dedicated key if configured; otherwise fall back to the app key.
        return (string) (config('club.dni_key') ?: config('app.key'));
    }
}
