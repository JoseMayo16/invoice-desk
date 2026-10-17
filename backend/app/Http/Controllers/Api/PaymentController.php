<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceCalculator;
use App\Services\PaymentRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class PaymentController extends Controller
{
    /**
     * Record a payment received outside the app (bank transfer, cash).
     */
    public function store(PaymentRequest $request, Invoice $invoice, PaymentRecorder $recorder): JsonResponse
    {
        if (! $invoice->isPayable()) {
            return response()->json(['message' => 'Only sent invoices with a balance can receive payments.'], 409);
        }

        $recorder->record(
            $invoice,
            InvoiceCalculator::toCents($request->validated('amount')),
            PaymentMethod::from($request->validated('method')),
            $request->validated('paid_at') ? Carbon::parse($request->validated('paid_at')) : null,
            $request->validated('reference'),
        );

        return (new InvoiceResource($invoice->fresh(['client', 'items', 'payments'])))
            ->response()->setStatusCode(201);
    }
}
