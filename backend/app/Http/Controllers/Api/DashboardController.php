<?php

namespace App\Http\Controllers\Api;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\Money;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = today();
        $open = Invoice::ownedBy($user)->where('status', InvoiceStatus::Sent);
        $overdue = (clone $open)->whereDate('due_date', '<', $today);

        $since = $today->copy()->startOfMonth()->subMonths(11);
        $payments = Payment::query()
            ->whereHas('invoice', fn ($q) => $q->ownedBy($user))
            ->where('paid_at', '>=', $since)
            ->get(['amount', 'paid_at']);

        // Grouped in PHP so the query stays portable across SQLite, MySQL and Postgres.
        $months = [];
        for ($m = $since->copy(); $m->lte($today); $m->addMonth()) {
            $months[$m->format('Y-m')] = 0;
        }
        foreach ($payments as $payment) {
            $months[$payment->paid_at->format('Y-m')] += $payment->amount;
        }

        $topClients = Invoice::ownedBy($user)
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Paid])
            ->where('issue_date', '>=', $since)
            ->selectRaw('client_id, SUM(total) as billed, COUNT(*) as invoices')
            ->groupBy('client_id')
            ->orderByDesc('billed')
            ->with('client:id,name')
            ->limit(5)
            ->get();

        return response()->json([
            'currency' => $user->company->currency,
            'outstanding' => Money::out((int) $open->sum('total') - (int) $open->sum('amount_paid')),
            'overdue' => Money::out((int) $overdue->sum('total') - (int) $overdue->sum('amount_paid')),
            'overdue_count' => $overdue->count(),
            'paid_this_month' => Money::out($payments->where('paid_at', '>=', $today->copy()->startOfMonth())->sum('amount')),
            'draft_count' => Invoice::ownedBy($user)->where('status', InvoiceStatus::Draft)->count(),
            'revenue_by_month' => collect($months)
                ->map(fn ($cents, $month) => ['month' => $month, 'amount' => Money::out($cents)])
                ->values(),
            'top_clients' => $topClients->map(fn ($row) => [
                'id' => $row->client_id,
                'name' => $row->client?->name,
                'billed' => Money::out((int) $row->billed),
                'invoices' => (int) $row->invoices,
            ]),
            'overdue_invoices' => InvoiceResource::collection(
                $overdue->with('client')->orderBy('due_date')->limit(5)->get()
            ),
        ]);
    }
}
