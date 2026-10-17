<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\WebhookSignature;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.webhook_secret' => self::SECRET]);
    }

    private function completed(Invoice $invoice, string $session = 'cs_test_1'): string
    {
        return json_encode([
            'id' => 'evt_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => $session,
                'object' => 'checkout.session',
                'payment_status' => 'paid',
                'amount_total' => $invoice->total,
                'metadata' => ['invoice_id' => (string) $invoice->id],
            ]],
        ]);
    }

    private function deliver(string $payload, ?string $secret = self::SECRET)
    {
        $header = WebhookSignature::generateSignatureHeader($payload, $secret);

        return $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $header,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    }

    public function test_signed_checkout_marks_the_invoice_paid_once(): void
    {
        $invoice = $this->invoiceFor($this->owner());
        $invoice->forceFill(['status' => InvoiceStatus::Sent])->save();
        $payload = $this->completed($invoice);

        $this->deliver($payload)->assertOk()->assertJson(['received' => true]);
        $this->deliver($payload)->assertOk(); // Stripe retries deliveries

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame($invoice->total, $invoice->amount_paid);
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame('cs_test_1', $invoice->payments()->first()->stripe_session_id);
    }

    public function test_bad_signatures_are_rejected(): void
    {
        $invoice = $this->invoiceFor($this->owner());
        $payload = $this->completed($invoice);

        $this->deliver($payload, 'whsec_wrong')->assertStatus(400);
        $this->call('POST', '/api/stripe/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload)
            ->assertStatus(400);
        $this->assertSame(0, $invoice->payments()->count());
    }

    public function test_other_events_are_acknowledged_and_ignored(): void
    {
        $this->deliver(json_encode(['id' => 'evt_2', 'object' => 'event', 'type' => 'customer.created', 'data' => ['object' => []]]))
            ->assertOk();
    }
}
