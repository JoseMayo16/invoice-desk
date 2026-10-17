<?php

namespace App\Payments;

use App\Models\Invoice;
use RuntimeException;
use Stripe\StripeClient;

class StripeGateway implements PaymentGateway
{
    public function __construct(private readonly ?StripeClient $stripe) {}

    public function enabled(): bool
    {
        return $this->stripe !== null;
    }

    public function checkoutUrl(Invoice $invoice, string $successUrl, string $cancelUrl): string
    {
        if (! $this->stripe) {
            throw new RuntimeException('Stripe is not configured.');
        }

        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'client_reference_id' => (string) $invoice->id,
            'metadata' => ['invoice_id' => (string) $invoice->id],
            'customer_email' => $invoice->client->email ?: null,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($invoice->currency),
                    'unit_amount' => $invoice->balance(),
                    'product_data' => ['name' => "Invoice {$invoice->number}"],
                ],
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        return $session->url;
    }
}
