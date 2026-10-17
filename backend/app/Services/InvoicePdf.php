<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdf
{
    public static function render(Invoice $invoice): string
    {
        $invoice->loadMissing('items', 'client', 'company');

        return Pdf::loadView('pdf.invoice', ['invoice' => $invoice])->setPaper('a4')->output();
    }

    public static function filename(Invoice $invoice): string
    {
        return "{$invoice->number}.pdf";
    }
}
