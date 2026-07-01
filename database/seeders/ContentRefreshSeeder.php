<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ContentRefreshSeeder extends Seeder
{
    public function run(): void
    {
        app(QuickLaunchSeeder::class)->seedContent(preservePlayerData: true);
    }
}
