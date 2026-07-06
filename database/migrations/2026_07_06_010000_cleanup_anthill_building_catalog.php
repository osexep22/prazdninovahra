<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('buildings', 'is_available')) {
            return;
        }

        $activeSlugs = ['telocvicna', 'krejci', 'kuchyn', 'malir'];
        $futureSlugs = ['obyvak', 'remeslnik', 'hudebna', 'zahradnik', 'porodnice', 'hospoda'];
        $allowedSlugs = array_merge($activeSlugs, $futureSlugs);

        DB::table('buildings')
            ->whereNotIn('slug', $allowedSlugs)
            ->update([
                'is_available' => false,
                'min_colony_level' => 11,
                'updated_at' => now(),
            ]);

        DB::table('buildings')
            ->whereIn('slug', $futureSlugs)
            ->update([
                'is_available' => false,
                'min_colony_level' => 11,
                'updated_at' => now(),
            ]);

        DB::table('buildings')
            ->whereIn('slug', $activeSlugs)
            ->update([
                'is_available' => true,
                'min_colony_level' => 1,
                'updated_at' => now(),
            ]);

        $labels = [
            'telocvicna' => ['Sportovec', '/assets/game/rooms/sportovec.svg'],
            'krejci' => ['Krejčí', '/assets/game/rooms/krejci.svg'],
            'kuchyn' => ['Kuchař', '/assets/game/rooms/kuchar.svg'],
            'malir' => ['Malíř', '/assets/game/rooms/malir.svg'],
            'obyvak' => ['Obývák', '/assets/game/rooms/obyvak.svg'],
            'remeslnik' => ['Řemeslník', '/assets/placeholders/building-sklad.svg'],
            'hudebna' => ['Muzikant', '/assets/game/rooms/muzikant.svg'],
            'zahradnik' => ['Zahradník', '/assets/placeholders/building-archiv.svg'],
            'porodnice' => ['Porodnice', '/assets/placeholders/building-sin-prestige.svg'],
            'hospoda' => ['Hospoda', '/assets/placeholders/building-sklad.svg'],
        ];

        foreach ($labels as $slug => [$name, $assetPath]) {
            DB::table('buildings')->where('slug', $slug)->update([
                'name' => $name,
                'svg_asset_path' => $assetPath,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('buildings', 'is_available')) {
            return;
        }

        DB::table('buildings')->update([
            'is_available' => true,
            'min_colony_level' => 1,
            'updated_at' => now(),
        ]);
    }
};
