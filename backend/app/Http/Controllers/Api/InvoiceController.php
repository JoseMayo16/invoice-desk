<?php

namespace App\Http\Controllers\Api;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Mail\InvoiceSent;
use App\Models\Invoice;
use App\Services\InvoicePdf;
use App\Services\InvoiceWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceWriter $writer) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'status' => ['nullable', Rule::in(Invoice::DISPLAY_STATUSES)],
            'client_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $invoices = Invoice::ownedBy($request->user())
            ->with('client')
            ->when($request->input('status'), fn ($q, $status) => $q->withDisplayStatus($status))
            ->when($request->integer('client_id'), fn ($q, $id) => $q->where('client_id', $id))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(
                fn ($q) => $q->whereLike('number', "%{$term}%")
                    ->orWhereHas('client', fn ($q) => $q->whereLike('name', "%{$term}%")),
            ))
            ->latest('issue_date')
            ->latest('id')
            ->paginate($request->integer('per_page', 25));

        return InvoiceResource::collection($invoices);
    }

    public function store(InvoiceRequest $request): JsonResponse
    {
        $invoice = $this->writer->create($request->user()->company, $request->invoiceData(), $request->items());

        return (new InvoiceResource($invoice))->response()->setStatusCode(201);
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        return new InvoiceResource($invoice->load('client', 'items', 'payments'));
    }

    public function update(InvoiceRequest $request, Invoice $invoice): InvoiceResource
    {
        Gate::authorize('update', $invoice);

        return new InvoiceResource($this->writer->update($invoice, $request->invoiceData(), $request->items()));
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        Gate::authorize('delete', $invoice);
        $invoice->delete();

        return response()->json(status: 204);
    }

    /**
     * Email the invoice (PDF attached, queued) and move a draft to "sent".
     */
    public function send(Invoice $invoice): InvoiceResource|JsonResponse
    {
        if (! $invoice->isEditable()) {
            return response()->json(['message' => 'Paid or void invoices cannot be sent.'], 409);
        }
        if (! $invoice->client->email) {
            return response()->json(['message' => 'Add an email address to this client first.'], 422);
        }

        Mail::to($invoice->client->email, $invoice->client->name)->queue(new InvoiceSent($invoice));
        $invoice->forceFill(['status' => InvoiceStatus::Sent, 'sent_at' => now()])->save();

        return new InvoiceResource($invoice->load('client', 'items', 'payments'));
    }

    public function duplicate(Invoice $invoice): JsonResponse
    {
        $copy = $this->writer->duplicate($invoice->load('items', 'company'));

        return (new InvoiceResource($copy))->response()->setStatusCode(201);
    }

    public function void(Invoice $invoice): InvoiceResource|JsonResponse
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            return response()->json(['message' => 'A paid invoice cannot be voided.'], 409);
        }
        $invoice->forceFill(['status' => InvoiceStatus::Void])->save();

        return new InvoiceResource($invoice->load('client', 'items', 'payments'));
    }

    public function pdf(Invoice $invoice): Response
    {
        return response(InvoicePdf::render($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.InvoicePdf::filename($invoice).'"',
        ]);
    }
}
