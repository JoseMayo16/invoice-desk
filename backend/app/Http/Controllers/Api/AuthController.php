<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Session (cookie) auth for the SPA through Sanctum: the Vue app first calls
 * /sanctum/csrf-cookie, then logs in here. No tokens are stored in the browser.
 */
class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $company = Company::create([
                'name' => $request->validated('company_name'),
                'email' => $request->validated('email'),
                'currency' => $request->validated('currency'),
            ]);

            return User::create([
                ...$request->safe()->only('name', 'email', 'password'),
                'company_id' => $company->id,
                'role' => Role::Owner,
            ]);
        });

        Auth::guard('web')->login($user);
        $this->regenerate($request);

        return response()->json($this->profile($user), 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }
        $this->regenerate($request);

        return response()->json($this->profile($request->user('web')));
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(status: 204);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->profile($request->user()));
    }

    private function regenerate(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
    }

    /** @return array<string, mixed> */
    private function profile(User $user): array
    {
        $company = $user->company;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'company' => $company->only('id', 'name', 'email', 'address', 'tax_id', 'currency', 'invoice_prefix'),
        ];
    }
}
