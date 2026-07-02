<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            if (! Schema::hasColumn('buildings', 'is_available')) {
                $table->boolean('is_available')->default(true)->after('svg_asset_path');
            }
        });

        $activeBuildings = [
            'krejci' => [
                'name' => 'Krejčí',
                'description' => 'Místnost, kde se látá, šije a vyrábí výbava pro výpravu.',
                'asset_path' => '/assets/game/rooms/krejci.svg',
                'min_level' => 1,
                'cost' => 35,
            ],
            'kuchyn' => [
                'name' => 'Kuchař',
                'description' => 'Místnost pro třídění dobrot a lesních zásob.',
                'asset_path' => '/assets/game/rooms/kuchar.svg',
                'min_level' => 1,
                'cost' => 45,
            ],
            'malir' => [
                'name' => 'Malíř',
                'description' => 'Tvořivá komůrka pro barvy, značky a výzdobu mraveniště.',
                'asset_path' => '/assets/game/rooms/malir.svg',
                'min_level' => 1,
                'cost' => 40,
            ],
            'telocvicna' => [
                'name' => 'Sportovec',
                'description' => 'Tréninková komůrka pro sílu a obratnost.',
                'asset_path' => '/assets/game/rooms/sportovec.svg',
                'min_level' => 1,
                'cost' => 60,
            ],
        ];

        DB::table('buildings')->update(['is_available' => false]);

        foreach ($activeBuildings as $slug => $building) {
            $exists = DB::table('buildings')->where('slug', $slug)->exists();
            if ($exists) {
                DB::table('buildings')
                    ->where('slug', $slug)
                    ->update([
                        'svg_asset_path' => $building['asset_path'],
                        'is_available' => true,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('buildings')->insert([
                'slug' => $slug,
                'name' => $building['name'],
                'description' => $building['description'],
                'min_colony_level' => $building['min_level'],
                'cost_resources' => $building['cost'],
                'svg_asset_path' => $building['asset_path'],
                'is_available' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            if (Schema::hasColumn('buildings', 'is_available')) {
                $table->dropColumn('is_available');
            }
        });
    }
};
