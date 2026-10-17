<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'email', 'address', 'tax_id', 'currency', 'invoice_prefix'])]
class Company extends Model
{
    use HasFactory;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Reserve the next invoice number. The row lock keeps two requests from
     * getting the same number when invoices are created at the same time.
     */
    public function takeInvoiceNumber(): string
    {
        return DB::transaction(function () {
            $company = self::query()->lockForUpdate()->findOrFail($this->id);
            $number = $company->next_invoice_number;
            $company->increment('next_invoice_number');
            $this->next_invoice_number = $number + 1;

            return sprintf('%s-%04d', $company->invoice_prefix, $number);
        });
    }
}
