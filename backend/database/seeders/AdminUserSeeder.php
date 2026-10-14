<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => config('estatein.admin.email')],
            [
                'name' => config('estatein.admin.name'),
                'password' => config('estatein.admin.password'),
            ],
        );
    }
}
