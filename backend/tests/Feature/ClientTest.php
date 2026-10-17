<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_crud_and_search(): void
    {
        $user = $this->owner();
        $this->actingAs($user);

        $id = $this->postJson('/api/clients', ['name' => 'Harbor & Pine', 'email' => 'ap@harbor.test'])
            ->assertCreated()->json('data.id');
        Client::factory()->create(['company_id' => $user->company_id, 'name' => 'Zeta Labs']);

        $this->getJson('/api/clients?q=harbor')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Harbor & Pine');

        $this->putJson("/api/clients/{$id}", ['name' => 'Harbor and Pine', 'phone' => '+57 300 000 0000'])
            ->assertOk()->assertJsonPath('data.phone', '+57 300 000 0000');

        $this->deleteJson("/api/clients/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('clients', ['id' => $id]);
    }

    public function test_validation(): void
    {
        $this->actingAs($this->owner())
            ->postJson('/api/clients', ['email' => 'nope'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_other_companies_clients_are_not_found(): void
    {
        $theirs = Client::factory()->create();
        $this->actingAs($this->owner());

        $this->getJson("/api/clients/{$theirs->id}")->assertNotFound();
        $this->putJson("/api/clients/{$theirs->id}", ['name' => 'Hijacked'])->assertNotFound();
        $this->deleteJson("/api/clients/{$theirs->id}")->assertNotFound();
        $this->getJson('/api/clients')->assertJsonCount(0, 'data');
    }

    public function test_members_cannot_delete(): void
    {
        $owner = $this->owner();
        $client = Client::factory()->create(['company_id' => $owner->company_id]);

        $this->actingAs($this->memberOf($owner))->deleteJson("/api/clients/{$client->id}")->assertForbidden();
    }

    public function test_client_with_invoices_cannot_be_deleted(): void
    {
        $owner = $this->owner();
        $invoice = $this->invoiceFor($owner);

        $this->actingAs($owner)->deleteJson("/api/clients/{$invoice->client_id}")
            ->assertStatus(409)->assertJsonPath('message', 'This client has invoices. Delete or reassign them first.');
    }
}
