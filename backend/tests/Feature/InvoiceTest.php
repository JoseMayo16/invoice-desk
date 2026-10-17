<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Mail\InvoiceSent;
use App\Models\Client;
use App\Services\PaymentRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Client $client, array $overrides = []): array
    {
        return [
            'client_id' => $client->id,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-31',
            'notes' => 'Thanks!',
            'items' => [
                ['description' => 'Website design', 'quantity' => 1, 'unit_price' => '2400.00', 'tax_rate' => 19],
                ['description' => 'Hosting', 'quantity' => 12, 'unit_price' => '15.5', 'tax_rate' => 0],
            ],
            ...$overrides,
        ];
    }

    public function test_create_computes_totals_on_the_server_and_numbers_in_sequence(): void
    {
        $user = $this->owner();
        $client = Client::factory()->create(['company_id' => $user->company_id]);
        $this->actingAs($user);

        $this->postJson('/api/invoices', $this->payload($client) + ['total' => 1])
            ->assertCreated()
            ->assertJsonPath('data.number', 'INV-0001')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonPath('data.subtotal', 2586)   // 2400 + 12 x 15.50
            ->assertJsonPath('data.tax_total', 456)   // 19% of 2400
            ->assertJsonPath('data.total', 3042)
            ->assertJsonPath('data.items.1.line_subtotal', 186)
            ->assertJsonPath('data.public_url', null);

        $this->postJson('/api/invoices', $this->payload($client))->assertJsonPath('data.number', 'INV-0002');
    }

    public function test_validation(): void
    {
        $user = $this->owner();
        $client = Client::factory()->create(['company_id' => $user->company_id]);
        $foreign = Client::factory()->create();
        $this->actingAs($user);

        $this->postJson('/api/invoices', $this->payload($foreign))
            ->assertUnprocessable()->assertJsonValidationErrors('client_id');
        $this->postJson('/api/invoices', $this->payload($client, ['items' => []]))
            ->assertJsonValidationErrors('items');
        $this->postJson('/api/invoices', $this->payload($client, ['due_date' => '2026-09-01']))
            ->assertJsonValidationErrors('due_date');
        $this->postJson('/api/invoices', $this->payload($client, ['items' => [
            ['description' => '', 'quantity' => 0, 'unit_price' => '1.999', 'tax_rate' => 120],
        ]]))->assertJsonValidationErrors([
            'items.0.description', 'items.0.quantity', 'items.0.unit_price', 'items.0.tax_rate',
        ]);
    }

    public function test_update_replaces_lines_and_recalculates(): void
    {
        $user = $this->owner();
        $invoice = $this->invoiceFor($user);
        $this->actingAs($user);

        $this->putJson("/api/invoices/{$invoice->id}", [
            'client_id' => $invoice->client_id,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'items' => [['description' => 'Single line', 'quantity' => 3, 'unit_price' => 100, 'tax_rate' => 0]],
        ])->assertOk()
            ->assertJsonPath('data.number', $invoice->number)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.total', 300);
    }

    public function test_paid_invoices_are_locked(): void
    {
        $user = $this->owner();
        $invoice = $this->invoiceFor($user);
        $invoice->forceFill(['status' => InvoiceStatus::Sent])->save();
        app(PaymentRecorder::class)->record($invoice, $invoice->total, PaymentMethod::Cash);

        $this->actingAs($user)->putJson("/api/invoices/{$invoice->id}", [
            'client_id' => $invoice->client_id, 'issue_date' => '2026-10-01', 'due_date' => '2026-10-15',
            'items' => [['description' => 'x', 'quantity' => 1, 'unit_price' => 1]],
        ])->assertForbidden();
        $this->postJson("/api/invoices/{$invoice->id}/void")->assertStatus(409);
        $this->getJson("/api/invoices/{$invoice->id}")->assertJsonPath('data.can.update', false);
    }

    public function test_list_filters_by_display_status_and_search(): void
    {
        $user = $this->owner();
        $draft = $this->invoiceFor($user);
        $open = $this->invoiceFor($user);
        $late = $this->invoiceFor($user, data: ['issue_date' => today()->subDays(40), 'due_date' => today()->subDays(10)]);
        $open->forceFill(['status' => InvoiceStatus::Sent])->save();
        $late->forceFill(['status' => InvoiceStatus::Sent])->save();
        $this->invoiceFor($this->owner()); // another company
        $this->actingAs($user);

        $this->getJson('/api/invoices')->assertJsonCount(3, 'data');
        $this->getJson('/api/invoices?status=overdue')->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $late->id)->assertJsonPath('data.0.status', 'overdue');
        $this->getJson('/api/invoices?status=sent')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $open->id);
        $this->getJson('/api/invoices?status=draft')->assertJsonPath('data.0.id', $draft->id);
        $this->getJson('/api/invoices?q='.$draft->number)->assertJsonCount(1, 'data');
        $this->getJson('/api/invoices?q='.urlencode($late->client->name))->assertJsonPath('data.0.id', $late->id);
        $this->getJson('/api/invoices?status=bogus')->assertJsonValidationErrors('status');
    }

    public function test_send_queues_the_email_and_marks_the_invoice_sent(): void
    {
        Mail::fake();
        $user = $this->owner();
        $invoice = $this->invoiceFor($user);

        $this->actingAs($user)->postJson("/api/invoices/{$invoice->id}/send")
            ->assertOk()->assertJsonPath('data.status', 'sent')
            ->assertJsonPath('data.public_url', "http://localhost:5173/pay/{$invoice->public_token}");

        Mail::assertQueued(InvoiceSent::class, fn ($mail) => $mail->hasTo($invoice->client->email)
            && $mail->invoice->is($invoice));
        $this->assertNotNull($invoice->fresh()->sent_at);
    }

    public function test_send_needs_a_client_email(): void
    {
        Mail::fake();
        $user = $this->owner();
        $invoice = $this->invoiceFor($user);
        $invoice->client->update(['email' => null]);

        $this->actingAs($user)->postJson("/api/invoices/{$invoice->id}/send")->assertUnprocessable();
        Mail::assertNothingQueued();
    }

    public function test_duplicate_creates_a_new_draft(): void
    {
        $user = $this->owner();
        $invoice = $this->invoiceFor($user);
        $invoice->forceFill(['status' => InvoiceStatus::Sent])->save();

        $this->actingAs($user)->postJson("/api/invoices/{$invoice->id}/duplicate")
            ->assertCreated()
            ->assertJsonPath('data.number', 'INV-0002')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.total', $invoice->total / 100)
            ->assertJsonPath('data.issue_date', today()->toDateString())
            ->assertJsonPath('data.due_date', today()->addDays(30)->toDateString());
    }

    public function test_only_owners_delete(): void
    {
        $owner = $this->owner();
        $invoice = $this->invoiceFor($owner);

        $this->actingAs($this->memberOf($owner))->deleteJson("/api/invoices/{$invoice->id}")->assertForbidden();
        $this->actingAs($owner)->deleteJson("/api/invoices/{$invoice->id}")->assertNoContent();
        $this->assertDatabaseMissing('invoice_items', ['invoice_id' => $invoice->id]);
    }

    public function test_other_companies_invoices_are_not_found(): void
    {
        $theirs = $this->invoiceFor($this->owner());
        $this->actingAs($this->owner());

        foreach (['get' => '', 'put' => '', 'delete' => ''] as $method => $suffix) {
            $this->json($method, "/api/invoices/{$theirs->id}")->assertNotFound();
        }
        foreach (['send', 'duplicate', 'void', 'payments'] as $action) {
            $this->postJson("/api/invoices/{$theirs->id}/{$action}")->assertNotFound();
        }
        $this->getJson("/api/invoices/{$theirs->id}/pdf")->assertNotFound();
    }
}
