<?php

namespace App\Http\Resources;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Invoice */
class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->displayStatus(),
            'client' => new ClientResource($this->whenLoaded('client')),
            'issue_date' => $this->issue_date->toDateString(),
            'due_date' => $this->due_date->toDateString(),
            'currency' => $this->currency,
            'notes' => $this->notes,
            'subtotal' => Money::out($this->subtotal),
            'tax_total' => Money::out($this->tax_total),
            'total' => Money::out($this->total),
            'amount_paid' => Money::out($this->amount_paid),
            'balance' => Money::out($this->balance()),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'public_url' => $this->when(
                $this->status->value !== 'draft',
                fn () => rtrim(config('app.frontend_url'), '/')."/pay/{$this->public_token}",
            ),
            'sent_at' => $this->sent_at,
            'paid_at' => $this->paid_at,
            'can' => $this->when($user !== null, fn () => [
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
                'record_payment' => $this->isPayable(),
            ]),
        ];
    }
}
