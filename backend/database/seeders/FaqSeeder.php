<?php

namespace Database\Seeders;

use App\Models\Faq;
use Database\Seeders\Support\Fixtures;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Fixtures::data('faqs') as $order => $faq) {
            Faq::query()->create([...$faq, 'sort_order' => $order]);
        }
    }
}
