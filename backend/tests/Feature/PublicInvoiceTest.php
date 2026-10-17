<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function sentInvoice(): Invoice
    {
        $invoice = $this->invoiceFor($this->owner());
        $invoice->forceFill(['status' => InvoiceStatus::Sent])->save();

        return $invoice;
    }

    private function fakeGateway(bool $enabled = true): object
    {
        $fake = new class($enabled) implements PaymentGateway
        {
            public array $calls = [];

            public function __construct(private bool $on) {}

            public function enabled(): bool
            {
                return $this->on;
            }

            public function checkoutUrl(Invoice $invoice, string $successUrl, string $cancelUrl): string
            {
                $this->calls[] = compact('successUrl', 'cancelUrl') + ['amount' => $invoice->balance()];

                return 'https://checkout.stripe.test/session_123';
            }
        };
        $this->app->instance(PaymentGateway::class, $fake);

        return $fake;
    }

    public function test_customers_see_sent_invoices_without_internal_data(): void
    {
        $invoice = $this->sentInvoice();

        $response = $this->getJson("/api/public/invoices/{$invoice->public_token}")->assertOk()
            ->assertJsonPath('data.number', $invoice->number)
            ->assertJsonPath('data.to.name', $invoice->client->name)
            ->assertJsonPath('data.total', 357)
            ->assertJsonPath('data.can_pay_online', false);

        $this->assertArrayNotHasKey('id', $response->json('data'));
        $this->assertArrayNotHasKey('email', $response->json('data.to'));
    }

    public function test_drafts_void_and_unknown_tokens_are_not_found(): void
    {
        $draft = $this->invoiceFor($this->owner());
        $this->getJson("/api/public/invoices/{$draft->public_token}")->assertNotFound();

        $void = $this->sentInvoice();
        $void->forceFill(['status' => InvoiceStatus::Void])->save();
        $this->getJson("/api/public/invoices/{$void->public_token}")->assertNotFound();

        $this->getJson('/api/public/invoices/00000000-0000-4000-8000-000000000000')->assertNotFound();
        $this->getJson('/api/public/invoices/not-a-uuid')->assertNotFound();
    }

    public function test_checkout_without_stripe_configured(): void
    {
        $this->fakeGateway(enabled: false);
        $invoice = $this->sentInvoice();

        $this->postJson("/api/public/invoices/{$invoice->public_token}/checkout")->assertStatus(503);
    }

    public function test_checkout_returns_the_stripe_url_for_the_balance(): void
    {
        $gateway = $this->fakeGateway();
        $invoice = $this->sentInvoice();

        $this->getJson("/api/public/invoices/{$invoice->public_token}")->assertJsonPath('data.can_pay_online', true);
        $this->postJson("/api/public/invoices/{$invoice->public_token}/checkout")
            ->assertOk()->assertJsonPath('url', 'https://checkout.stripe.test/session_123');

        $page = "http://localhost:5173/pay/{$invoice->public_token}";
        $this->assertSame([['successUrl' => "{$page}?paid=1", 'cancelUrl' => $page, 'amount' => 35700]], $gateway->calls);
    }

    public function test_paid_invoices_cannot_be_paid_again(): void
    {
        $this->fakeGateway();
        $invoice = $this->sentInvoice();
        $invoice->forceFill(['status' => InvoiceStatus::Paid, 'amount_paid' => $invoice->total])->save();

        $this->postJson("/api/public/invoices/{$invoice->public_token}/checkout")->assertStatus(409);
    }
}
