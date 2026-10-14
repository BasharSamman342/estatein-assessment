<?php

namespace Database\Seeders;

use App\Models\TeamMember;
use App\Services\ImageStorage;
use Database\Seeders\Support\Fixtures;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class TeamMemberSeeder extends Seeder
{
    public function run(ImageStorage $images): void
    {
        foreach (Fixtures::data('team_members') as $order => $member) {
            $images->saveWithImage(
                new TeamMember,
                [...Arr::except($member, 'photo'), 'sort_order' => $order],
                Fixtures::upload("team/{$member['photo']}"),
                'photo_path',
                'team',
            );
        }
    }
}
