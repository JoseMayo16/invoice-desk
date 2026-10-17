<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentRecorder
{
    /**
     * Record a payment and mark the invoice paid once it is covered.
     * A Stripe session can only ever pay once, so webhook retries are harmless.
     */
    public function record(
        Invoice $invoice,
        int $amount,
        PaymentMethod $method,
        ?Carbon $paidAt = null,
        ?string $reference = null,
        ?string $stripeSessionId = null,
    ): Payment {
        return DB::transaction(function () use ($invoice, $amount, $method, $paidAt, $reference, $stripeSessionId) {
            if ($stripeSessionId && $existing = Payment::where('stripe_session_id', $stripeSessionId)->first()) {
                return $existing;
            }

            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $payment = $invoice->payments()->create([
                'amount' => $amount,
                'method' => $method,
                'reference' => $reference,
                'stripe_session_id' => $stripeSessionId,
                'paid_at' => $paidAt ?? now(),
            ]);

            $invoice->amount_paid += $amount;
            if ($invoice->amount_paid >= $invoice->total) {
                $invoice->status = InvoiceStatus::Paid;
                $invoice->paid_at = $payment->paid_at;
            }
            $invoice->save();

            return $payment;
        });
    }
}
