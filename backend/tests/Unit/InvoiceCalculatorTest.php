<?php

namespace Tests\Unit;

use App\Services\InvoiceCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InvoiceCalculatorTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>, int, int}>
     */
    public static function lines(): array
    {
        return [
            'whole units' => [['quantity' => 2, 'unit_price' => 15000, 'tax_rate' => 19], 30000, 5700],
            'no tax' => [['quantity' => 3, 'unit_price' => 999, 'tax_rate' => 0], 2997, 0],
            'fractional quantity' => [['quantity' => '1.5', 'unit_price' => 3333, 'tax_rate' => 0], 5000, 0],
            'tax rounds half up' => [['quantity' => 1, 'unit_price' => 1050, 'tax_rate' => 10], 1050, 105],
            'tax rounds per line' => [['quantity' => 1, 'unit_price' => 1, 'tax_rate' => 50], 1, 1],
            'decimal tax rate' => [['quantity' => 1, 'unit_price' => 10000, 'tax_rate' => '8.5'], 10000, 850],
            'missing tax rate' => [['quantity' => 1, 'unit_price' => 500], 500, 0],
        ];
    }

    #[DataProvider('lines')]
    public function test_line_amounts(array $item, int $subtotal, int $tax): void
    {
        $this->assertSame(['line_subtotal' => $subtotal, 'line_tax' => $tax], InvoiceCalculator::line($item));
    }

    public function test_totals_are_sums_of_the_rounded_lines(): void
    {
        $lines = [
            InvoiceCalculator::line(['quantity' => 1, 'unit_price' => 1, 'tax_rate' => 50]),
            InvoiceCalculator::line(['quantity' => 1, 'unit_price' => 1, 'tax_rate' => 50]),
        ];

        // Rounding each line (1 + 1) instead of the sum (0.5 + 0.5 = 1) is deliberate:
        // the printed lines must add up to the printed total.
        $this->assertSame(['subtotal' => 2, 'tax_total' => 2, 'total' => 4], InvoiceCalculator::totals($lines));
    }

    public function test_to_cents(): void
    {
        $this->assertSame(12050, InvoiceCalculator::toCents('120.50'));
        $this->assertSame(1999, InvoiceCalculator::toCents(19.99));
        $this->assertSame(0, InvoiceCalculator::toCents(0));
    }
}
