<?php

namespace App\Http\Resources;

use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InvoiceItem */
class InvoiceItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'description' => $this->description,
            'quantity' => (float) $this->quantity,
            'unit_price' => Money::out($this->unit_price),
            'tax_rate' => (float) $this->tax_rate,
            'line_subtotal' => Money::out($this->line_subtotal),
            'line_tax' => Money::out($this->line_tax),
        ];
    }
}
