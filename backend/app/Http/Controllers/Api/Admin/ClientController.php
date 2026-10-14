<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Services\ImageStorage;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClientController extends Controller
{
    public function __construct(private readonly ImageStorage $images) {}

    public function index(): AnonymousResourceCollection
    {
        return ClientResource::collection(Client::query()->ordered()->get());
    }

    public function store(ClientRequest $request): ClientResource
    {
        return new ClientResource($this->save(new Client, $request));
    }

    public function show(Client $client): ClientResource
    {
        return new ClientResource($client);
    }

    public function update(ClientRequest $request, Client $client): ClientResource
    {
        return new ClientResource($this->save($client, $request));
    }

    public function destroy(Client $client): Response
    {
        $client->delete();

        return response()->noContent();
    }

    private function save(Client $client, ClientRequest $request): Client
    {
        return $this->images->saveWithImage(
            $client,
            $request->safe()->except('logo'),
            $request->file('logo'),
            'logo_path',
            'clients',
        );
    }
}
