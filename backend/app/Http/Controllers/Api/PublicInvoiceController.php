<?php

namespace App\Http\Controllers\Api;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicInvoiceResource;
use App\Models\Invoice;
use App\Payments\PaymentGateway;
use App\Services\InvoicePdf;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The customer's side: anyone with the unguessable link can view, download
 * and pay that one invoice. Drafts and void invoices are never exposed.
 */
class PublicInvoiceController extends Controller
{
    public function show(string $token): PublicInvoiceResource
    {
        return new PublicInvoiceResource($this->find($token));
    }

    public function pdf(string $token): Response
    {
        $invoice = $this->find($token);

        return response(InvoicePdf::render($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.InvoicePdf::filename($invoice).'"',
        ]);
    }

    public function checkout(string $token, PaymentGateway $gateway): JsonResponse
    {
        $invoice = $this->find($token);

        if (! $gateway->enabled()) {
            return response()->json(['message' => 'Online payments are not set up for this account.'], 503);
        }
        if (! $invoice->isPayable()) {
            return response()->json(['message' => 'This invoice has nothing left to pay.'], 409);
        }

        $page = rtrim(config('app.frontend_url'), '/')."/pay/{$token}";

        return response()->json(['url' => $gateway->checkoutUrl($invoice, "{$page}?paid=1", $page)]);
    }

    private function find(string $token): Invoice
    {
        return Invoice::query()
            ->where('public_token', $token)
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Paid])
            ->with('company', 'client', 'items')
            ->firstOrFail();
    }
}
