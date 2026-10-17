<?php

namespace App\Http\Resources;

final class Money
{
    /** Cents to major units for JSON: 12050 -> 120.5. */
    public static function out(?int $cents): ?float
    {
        return $cents === null ? null : round($cents / 100, 2);
    }
}
