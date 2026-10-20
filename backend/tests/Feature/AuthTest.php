<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** Requests that look like they come from the SPA get a session (Sanctum stateful). */
    private function fromSpa(): static
    {
        return $this->withHeader('Referer', 'http://localhost');
    }

    public function test_register_creates_a_company_and_signs_in_as_owner(): void
    {
        $this->fromSpa()->postJson('/api/register', [
            'name' => 'Ana Ríos',
            'email' => 'ana@studio.test',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'company_name' => 'Ríos Studio',
            'currency' => 'COP',
        ])->assertCreated()
            ->assertJsonPath('role', 'owner')
            ->assertJsonPath('company.name', 'Ríos Studio')
            ->assertJsonPath('company.currency', 'COP');

        $user = User::firstWhere('email', 'ana@studio.test');
        $this->assertSame(Role::Owner, $user->role);
        $this->assertAuthenticatedAs($user, 'web');
        $this->getJson('/api/me')->assertOk()->assertJsonPath('email', 'ana@studio.test');
    }

    public function test_register_validation(): void
    {
        $this->fromSpa()->postJson('/api/register', [
            'email' => 'not-an-email', 'password' => 'short', 'currency' => 'XXX',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password', 'company_name', 'currency']);
    }

    public function test_login_and_logout(): void
    {
        $user = $this->owner();

        $this->fromSpa()->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest('web');

        $this->fromSpa()->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()->assertJsonPath('id', $user->id);
        $this->assertAuthenticatedAs($user, 'web');

        $this->fromSpa()->postJson('/api/logout')->assertNoContent();
        $this->assertGuest('web');
    }

    public function test_api_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
        $this->getJson('/api/invoices')->assertUnauthorized();
    }

    public function test_api_answers_401_to_a_plain_browser_request(): void
    {
        $this->get('/api/me')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
    }
}
