<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_then_full_payment_marks_the_invoice_paid(): void
    {
        $user = $this->owner();
        $invoice = $this->invoiceFor($user); // total 357.00
        $invoice->forceFill(['status' => InvoiceStatus::Sent])->save();
        $this->actingAs($user);

        $this->postJson("/api/invoices/{$invoice->id}/payments", [
            'amount' => '200', 'method' => 'bank_transfer', 'reference' => 'TRX-881',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'sent')
            ->assertJsonPath('data.amount_paid', 200)
            ->assertJsonPath('data.balance', 157);

        $this->postJson("/api/invoices/{$invoice->id}/payments", ['amount' => '157', 'method' => 'cash'])
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.balance', 0)
            ->assertJsonCount(2, 'data.payments');
        $this->assertNotNull($invoice->fresh()->paid_at);
    }

    public function test_payment_validation(): void
    {
        $user = $this->owner();
        $invoice = $this->invoiceFor($user);
        $invoice->forceFill(['status' => InvoiceStatus::Sent])->save();
        $this->actingAs($user);

        $this->postJson("/api/invoices/{$invoice->id}/payments", ['amount' => '357.01', 'method' => 'cash'])
            ->assertJsonValidationErrors(['amount' => 'The amount cannot be more than the balance due.']);
        // Card payments only come from Stripe, never typed in by hand.
        $this->postJson("/api/invoices/{$invoice->id}/payments", ['amount' => 10, 'method' => 'card'])
            ->assertJsonValidationErrors('method');
        $this->postJson("/api/invoices/{$invoice->id}/payments", [
            'amount' => 10, 'method' => 'cash', 'paid_at' => now()->addDay()->toDateString(),
        ])->assertJsonValidationErrors('paid_at');
    }

    public function test_drafts_cannot_receive_payments(): void
    {
        $user = $this->owner();
        $invoice = $this->invoiceFor($user);

        $this->actingAs($user)->postJson("/api/invoices/{$invoice->id}/payments", ['amount' => 10, 'method' => 'cash'])
            ->assertStatus(409);
    }
}
