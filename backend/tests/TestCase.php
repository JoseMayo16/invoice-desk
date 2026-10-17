<?php

namespace Tests;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceWriter;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function owner(): User
    {
        return User::factory()->create();
    }

    protected function memberOf(User $owner): User
    {
        return User::factory()->member()->create(['company_id' => $owner->company_id]);
    }

    /**
     * @param  list<array<string, mixed>>|null  $items
     */
    protected function invoiceFor(User $user, ?array $items = null, array $data = []): Invoice
    {
        $client = Client::factory()->create(['company_id' => $user->company_id]);

        return app(InvoiceWriter::class)->create($user->company, [
            'client_id' => $client->id,
            'issue_date' => today(),
            'due_date' => today()->addDays(30),
            ...$data,
        ], $items ?? [['description' => 'Design work', 'quantity' => 2, 'unit_price' => 150, 'tax_rate' => 19]]);
    }
}
