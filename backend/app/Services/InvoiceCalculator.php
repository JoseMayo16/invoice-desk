<?php

namespace App\Services;

/**
 * All amounts are integer minor units (cents). Each line is rounded once,
 * half away from zero, and the invoice totals are plain sums of the lines,
 * so the PDF, the API and the database always agree to the cent.
 */
class InvoiceCalculator
{
    /**
     * @param  array{quantity: float|int|string, unit_price: int, tax_rate?: float|int|string|null}  $item
     * @return array{line_subtotal: int, line_tax: int}
     */
    public static function line(array $item): array
    {
        $subtotal = (int) round((float) $item['quantity'] * $item['unit_price']);
        $tax = (int) round($subtotal * (float) ($item['tax_rate'] ?? 0) / 100);

        return ['line_subtotal' => $subtotal, 'line_tax' => $tax];
    }

    /**
     * @param  iterable<array{line_subtotal: int, line_tax: int}>  $lines
     * @return array{subtotal: int, tax_total: int, total: int}
     */
    public static function totals(iterable $lines): array
    {
        $subtotal = $tax = 0;
        foreach ($lines as $line) {
            $subtotal += $line['line_subtotal'];
            $tax += $line['line_tax'];
        }

        return ['subtotal' => $subtotal, 'tax_total' => $tax, 'total' => $subtotal + $tax];
    }

    public static function toCents(float|int|string $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
