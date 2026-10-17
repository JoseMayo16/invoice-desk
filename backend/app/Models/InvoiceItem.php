<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['position', 'description', 'quantity', 'unit_price', 'tax_rate', 'line_subtotal', 'line_tax'])]
class InvoiceItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'unit_price' => 'integer',
            'line_subtotal' => 'integer',
            'line_tax' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
