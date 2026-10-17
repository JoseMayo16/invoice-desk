<?php

namespace App\Payments;

use App\Models\Invoice;

interface PaymentGateway
{
    /** False when no Stripe key is configured: the app works, card payments are hidden. */
    public function enabled(): bool;

    /** Create a hosted checkout for the invoice balance and return its URL. */
    public function checkoutUrl(Invoice $invoice, string $successUrl, string $cancelUrl): string;
}
