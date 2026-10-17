<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['client_id', 'issue_date', 'due_date', 'currency', 'notes'])]
class Invoice extends Model
{
    use HasFactory;

    /** "overdue" is not stored: it is a sent invoice whose due date has passed. */
    public const DISPLAY_STATUSES = ['draft', 'sent', 'overdue', 'paid', 'void'];

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            $invoice->public_token ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'integer',
            'tax_total' => 'integer',
            'total' => 'integer',
            'amount_paid' => 'integer',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    #[Scope]
    protected function ownedBy(Builder $query, User $user): void
    {
        $query->where('company_id', $user->company_id);
    }

    /**
     * Filter by the status a person sees, including the derived "overdue".
     */
    #[Scope]
    protected function withDisplayStatus(Builder $query, string $status): void
    {
        $today = now()->toDateString();
        match ($status) {
            'overdue' => $query->where('status', InvoiceStatus::Sent)->whereDate('due_date', '<', $today),
            'sent' => $query->where('status', InvoiceStatus::Sent)->whereDate('due_date', '>=', $today),
            default => $query->where('status', $status),
        };
    }

    public function displayStatus(): string
    {
        if ($this->status === InvoiceStatus::Sent && $this->due_date->isBefore(today())) {
            return 'overdue';
        }

        return $this->status->value;
    }

    public function balance(): int
    {
        return max(0, $this->total - $this->amount_paid);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [InvoiceStatus::Draft, InvoiceStatus::Sent], true);
    }

    public function isPayable(): bool
    {
        return $this->status === InvoiceStatus::Sent && $this->balance() > 0;
    }
}
