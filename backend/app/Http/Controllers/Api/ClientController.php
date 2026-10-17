<?php

namespace App\Http\Controllers\Api;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ClientController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $clients = Client::ownedBy($request->user())
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(
                fn ($q) => $q->whereLike('name', "%{$term}%")->orWhereLike('email', "%{$term}%"),
            ))
            ->withCount('invoices')
            ->withSum(['invoices as open_total' => fn ($q) => $q->where('status', InvoiceStatus::Sent)], 'total')
            ->withSum(['invoices as open_paid' => fn ($q) => $q->where('status', InvoiceStatus::Sent)], 'amount_paid')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25));

        return ClientResource::collection($clients);
    }

    public function store(ClientRequest $request): JsonResponse
    {
        $client = $request->user()->company->clients()->create($request->validated());

        return (new ClientResource($client))->response()->setStatusCode(201);
    }

    public function show(Client $client): ClientResource
    {
        return new ClientResource($client->loadCount('invoices'));
    }

    public function update(ClientRequest $request, Client $client): ClientResource
    {
        $client->update($request->validated());

        return new ClientResource($client);
    }

    public function destroy(Client $client): JsonResponse
    {
        Gate::authorize('delete', $client);

        if ($client->invoices()->exists()) {
            return response()->json([
                'message' => 'This client has invoices. Delete or reassign them first.',
            ], 409);
        }
        $client->delete();

        return response()->json(status: 204);
    }
}
