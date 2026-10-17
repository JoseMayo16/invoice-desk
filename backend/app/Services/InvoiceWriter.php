<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

/**
 * Creates and updates invoices together with their lines, recomputing every
 * amount on the server. Clients send quantities and prices, never totals.
 */
class InvoiceWriter
{
    /**
     * @param  array<string, mixed>  $data  validated invoice fields
     * @param  list<array<string, mixed>>  $items  validated lines, prices in major units
     */
    public function create(Company $company, array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($company, $data, $items) {
            $invoice = new Invoice($data);
            $invoice->company()->associate($company);
            $invoice->currency ??= $company->currency;
            $invoice->number = $company->takeInvoiceNumber();
            $invoice->status = InvoiceStatus::Draft;

            return $this->save($invoice, $items);
        });
    }

    public function update(Invoice $invoice, array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $items) {
            $invoice->fill($data);

            return $this->save($invoice, $items);
        });
    }

    public function duplicate(Invoice $source): Invoice
    {
        $days = $source->issue_date->diffInDays($source->due_date);
        $items = $source->items->map(fn ($item) => [
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price / 100,
            'tax_rate' => $item->tax_rate,
        ])->all();

        return $this->create($source->company, [
            'client_id' => $source->client_id,
            'currency' => $source->currency,
            'notes' => $source->notes,
            'issue_date' => today(),
            'due_date' => today()->addDays($days),
        ], $items);
    }

    private function save(Invoice $invoice, array $items): Invoice
    {
        $lines = [];
        foreach (array_values($items) as $position => $item) {
            $unitPrice = InvoiceCalculator::toCents($item['unit_price']);
            $lines[] = [
                'position' => $position + 1,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'tax_rate' => $item['tax_rate'] ?? 0,
                ...InvoiceCalculator::line([...$item, 'unit_price' => $unitPrice]),
            ];
        }

        $invoice->forceFill(InvoiceCalculator::totals($lines));
        $invoice->save();
        $invoice->items()->delete();
        $invoice->items()->createMany($lines);

        return $invoice->load('items', 'client');
    }
}
