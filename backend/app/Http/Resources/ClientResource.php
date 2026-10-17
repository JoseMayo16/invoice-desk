<?php

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Client */
class ClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'tax_id' => $this->tax_id,
            'notes' => $this->notes,
            'invoices_count' => $this->whenCounted('invoices'),
            'outstanding' => $this->when(
                array_key_exists('open_total', $this->getAttributes()),
                fn () => Money::out((int) $this->open_total - (int) $this->open_paid),
            ),
            'created_at' => $this->created_at,
        ];
    }
}
