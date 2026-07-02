<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('economy_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->integer('value')->default(0);
            $table->timestamps();
        });

        foreach (config('economy.settings', []) as $key => $value) {
            DB::table('economy_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => (int) $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        foreach (config('economy.location_rewards', []) as $slug => $reward) {
            DB::table('locations')->where('slug', $slug)->update([
                'reward_resources' => (int) $reward['resources'],
                'reward_prestige' => (int) $reward['prestige'],
                'updated_at' => now(),
            ]);
        }

        DB::table('location_tasks')->update([
            'reward_resources' => 0,
            'reward_prestige' => 0,
            'updated_at' => now(),
        ]);

        $buildingDefaults = [
            'telocvicna' => ['Sportovec', 'Tréninková komůrka pro sílu a obratnost.', '/assets/game/rooms/sportovec.svg', 12, 18],
            'krejci' => ['Krejčí', 'Komůrka pro látky, opravy a drobné vybavení.', '/assets/placeholders/building-tunelarska-komora.svg', 30, 18],
            'kuchyn' => ['Kuchař', 'Místnost pro třídění dobrot a lesních zásob.', '/assets/game/rooms/kuchar.svg', 48, 18],
            'malirska-komora' => ['Malíř', 'Tvořivá komůrka pro barvy a zdobení.', '/assets/placeholders/building-malirska-komora.svg', 66, 18],
            'obyvak' => ['Obývák', 'Útulná místnost pro odpočinek celé výpravy.', '/assets/game/rooms/obyvak.svg', 84, 18],
            'remeslnik' => ['Řemeslník', 'Dílna pro opravy, nástroje a malé vynálezy.', '/assets/placeholders/building-sklad.svg', 20, 48],
            'hudebna' => ['Muzikant', 'Komůrka pro zpěv, rytmus a večerní oslavy.', '/assets/game/rooms/muzikant.svg', 38, 48],
            'zahradnik' => ['Zahradník', 'Místnost pro semínka, sazenice a péči o zeleň.', '/assets/placeholders/building-archiv.svg', 56, 48],
            'porodnice' => ['Porodnice', 'Bezpečná komůrka pro nejmenší členy kolonie.', '/assets/placeholders/building-sin-prestige.svg', 74, 48],
            'hospoda' => ['Hospoda', 'Veselá komůrka pro odpočinek po práci.', '/assets/placeholders/building-sklad.svg', 38, 76],
        ];

        foreach (config('economy.building_costs', []) as $slug => $cost) {
            if (! DB::table('buildings')->where('slug', $slug)->exists() && isset($buildingDefaults[$slug])) {
                [$name, $description, $assetPath, $x, $y] = $buildingDefaults[$slug];
                DB::table('buildings')->insert([
                    'slug' => $slug,
                    'name' => $name,
                    'description' => $description,
                    'min_colony_level' => 1,
                    'cost_resources' => (int) $cost,
                    'svg_asset_path' => $assetPath,
                    'layout_x' => $x,
                    'layout_y' => $y,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('buildings')->where('slug', $slug)->update([
                'cost_resources' => (int) $cost,
                'updated_at' => now(),
            ]);
        }

        DB::table('building_tasks')->update([
            'reward_resources' => 0,
            'reward_prestige' => (int) config('economy.settings.building_task.reward_prestige', 150),
            'updated_at' => now(),
        ]);

        DB::table('badges')->where('slug', 'like', 'lokace-%')->update([
            'prestige_bonus' => (int) config('economy.settings.badge.location.prestige_bonus', 0),
            'updated_at' => now(),
        ]);
        DB::table('badges')->where('slug', 'like', 'top-10-%')->update([
            'prestige_bonus' => (int) config('economy.settings.badge.top10.prestige_bonus', 25),
            'updated_at' => now(),
        ]);
        DB::table('badges')
            ->whereIn('slug', ['vsechny-ukoly-budovy', 'prvni-budova', 'pet-budov'])
            ->update([
                'prestige_bonus' => (int) config('economy.settings.badge.building_task.prestige_bonus', 0),
                'updated_at' => now(),
            ]);
        DB::table('badges')->where('slug', 'adminsky-odznacek')->update([
            'prestige_bonus' => (int) config('economy.settings.badge.special.prestige_bonus', 50),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('economy_settings');
    }
};
