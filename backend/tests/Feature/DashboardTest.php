<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Services\PaymentRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary(): void
    {
        $user = $this->owner();
        $open = $this->invoiceFor($user);                                   // 357.00, due in 30 days
        $late = $this->invoiceFor($user, data: ['issue_date' => today()->subDays(40), 'due_date' => today()->subDays(5)]);
        $paid = $this->invoiceFor($user);
        $this->invoiceFor($user);                                           // draft
        foreach ([$open, $late, $paid] as $invoice) {
            $invoice->forceFill(['status' => InvoiceStatus::Sent])->save();
        }
        app(PaymentRecorder::class)->record($paid, $paid->total, PaymentMethod::BankTransfer);
        app(PaymentRecorder::class)->record($late, 5700, PaymentMethod::Cash); // partial
        $this->invoiceFor($this->owner())->forceFill(['status' => InvoiceStatus::Sent])->save(); // other company

        $data = $this->actingAs($user)->getJson('/api/dashboard')->assertOk()->json();

        $this->assertSame(357 + 300, (int) $data['outstanding']);   // open + rest of late
        $this->assertSame(300, (int) $data['overdue']);
        $this->assertSame(1, $data['overdue_count']);
        $this->assertSame(357 + 57, (int) $data['paid_this_month']);
        $this->assertSame(1, $data['draft_count']);
        $this->assertCount(12, $data['revenue_by_month']);
        $this->assertSame(414, (int) end($data['revenue_by_month'])['amount']);
        $this->assertSame($late->id, $data['overdue_invoices'][0]['id']);
    }
}
