<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\PaymentRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    /**
     * Stripe calls this after a checkout. The signature proves the request
     * comes from Stripe; anything unsigned or tampered with gets a 400.
     */
    public function __invoke(Request $request, PaymentRecorder $recorder): JsonResponse
    {
        $secret = config('services.stripe.webhook_secret');
        if (! $secret) {
            return response()->json(['message' => 'Webhooks are not configured.'], 400);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secret,
            );
        } catch (SignatureVerificationException|UnexpectedValueException) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        if ($event->type === 'checkout.session.completed' && $event->data->object->payment_status === 'paid') {
            $session = $event->data->object;
            $invoice = Invoice::find($session->metadata->invoice_id ?? null);

            if (! $invoice) {
                Log::warning('Stripe checkout for an unknown invoice', ['session' => $session->id]);
            } else {
                $recorder->record(
                    $invoice,
                    (int) $session->amount_total,
                    PaymentMethod::Card,
                    stripeSessionId: $session->id,
                );
            }
        }

        return response()->json(['received' => true]);
    }
}
