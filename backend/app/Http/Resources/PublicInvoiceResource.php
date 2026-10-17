<?php

namespace App\Http\Resources;

use App\Models\Invoice;
use App\Payments\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the customer sees on the "pay this invoice" page: no internal ids,
 * no notes about other invoices, only this document.
 *
 * @mixin Invoice
 */
class PublicInvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $company = $this->company;

        return [
            'number' => $this->number,
            'status' => $this->displayStatus(),
            'from' => [
                'name' => $company->name,
                'email' => $company->email,
                'address' => $company->address,
                'tax_id' => $company->tax_id,
            ],
            'to' => [
                'name' => $this->client->name,
                'address' => $this->client->address,
                'tax_id' => $this->client->tax_id,
            ],
            'issue_date' => $this->issue_date->toDateString(),
            'due_date' => $this->due_date->toDateString(),
            'currency' => $this->currency,
            'notes' => $this->notes,
            'items' => InvoiceItemResource::collection($this->items),
            'subtotal' => Money::out($this->subtotal),
            'tax_total' => Money::out($this->tax_total),
            'total' => Money::out($this->total),
            'amount_paid' => Money::out($this->amount_paid),
            'balance' => Money::out($this->balance()),
            'can_pay_online' => $this->isPayable() && app(PaymentGateway::class)->enabled(),
        ];
    }
}
