<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Services\ImageStorage;
use Database\Seeders\Support\Fixtures;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ClientSeeder extends Seeder
{
    public function run(ImageStorage $images): void
    {
        foreach (Fixtures::data('clients') as $order => $client) {
            $images->saveWithImage(
                new Client,
                [...Arr::except($client, 'logo'), 'sort_order' => $order],
                Fixtures::upload("clients/{$client['logo']}"),
                'logo_path',
                'clients',
            );
        }
    }
}
