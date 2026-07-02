<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\AnswerNormalizer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            app(QuickLaunchSeeder::class)->seedContent(preservePlayerData: true);

            return;
        }

        DB::table('users')->insert([
            [
                'display_name' => 'Admin',
                'name' => 'Admin',
                'username' => 'admin',
                'email' => null,
                'role' => 'admin',
                'status' => 'active',
                'friend_code' => 'A1D2M3',
                'password' => Hash::make('admin123'),
                'admin_contact_password' => Hash::make('admin-heslo'),
                'admin_contact_code_hash' => Hash::make('admin-kod'),
                'admin_contact_code_encrypted' => Crypt::encryptString('admin-kod'),
                'colony_level' => 5,
                'resources' => 500,
                'prestige' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        foreach (['hrac1', 'hrac2', 'hrac3', 'hrac4', 'hrac5'] as $index => $username) {
            DB::table('users')->insert([
                'display_name' => 'Hráč ' . ($index + 1),
                'name' => 'Hráč ' . ($index + 1),
                'username' => $username,
                'email' => null,
                'role' => 'player',
                'status' => 'pending_approval',
                'password' => Hash::make('heslo123'),
                'admin_contact_password' => Hash::make('admin-heslo'),
                'admin_contact_code_hash' => Hash::make('admin-kod'),
                'admin_contact_code_encrypted' => Crypt::encryptString('admin-kod'),
                'colony_level' => min(5, $index + 1),
                'resources' => 120 + ($index * 30),
                'prestige' => 1000 - ($index * 120),
                'registration_source' => ['plakat_chotebor', 'facebook', 'instagram', 'skola', 'qr_test'][$index],
                'friend_code' => ['H1R2A3', 'H2R3A4', 'H3R4A5', 'H4R5A6', 'H5R6A7'][$index],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (['plakat_chotebor', 'facebook', 'instagram', 'skola', 'qr_test'] as $source) {
            DB::table('registration_sources')->insert([
                'code' => $source,
                'label' => str_replace('_', ' ', ucfirst($source)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $locations = [
            ['startovni-kamen', 'Startovni kamen', 12, 16, []],
            ['rosna-stezka', 'Rosna stezka', 34, 28, []],
            ['makove-pole', 'Makove pole', 55, 13, []],
            ['dutina-v-koreni', 'Dutina v koreni', 24, 52, ['startovni-kamen']],
            ['listovy-most', 'Listovy most', 50, 49, ['startovni-kamen', 'rosna-stezka']],
            ['stary-parek', 'Stary parek', 16, 78, ['dutina-v-koreni']],
            ['slunecni-mech', 'Slunecni mech', 64, 75, ['listovy-most']],
            ['srdce-palouku', 'Srdce palouku', 42, 92, ['stary-parek', 'slunecni-mech']],
        ];

        $locationIds = [];
        foreach ($locations as $i => [$slug, $name, $x, $y]) {
            $locationIds[$slug] = DB::table('locations')->insertGetId([
                'slug' => $slug,
                'name' => $name,
                'description' => 'Kousek palouku, kde kolonie ziska dalsi stopu.',
                'story' => 'Mravenci tu nacházejí znamení, drobné úkoly a materiál pro budoucí mraveniště.',
                'reward_prestige' => 80 + ($i * 20),
                'reward_resources' => 20 + ($i * 5),
                'reward_colony_level' => in_array($i, [2, 5, 7], true) ? 1 : 0,
                'svg_locked' => '/assets/placeholders/location-locked.svg',
                'svg_available' => '/assets/placeholders/location-available.svg',
                'svg_completed' => '/assets/placeholders/location-completed.svg',
                'map_x' => $x,
                'map_y' => $y,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($locations as [$slug, , , , $requirements]) {
            foreach ($requirements as $requiredSlug) {
                DB::table('location_requirements')->insert([
                    'location_id' => $locationIds[$slug],
                    'required_location_id' => $locationIds[$requiredSlug],
                ]);
            }
        }

        foreach ($locationIds as $slug => $locationId) {
            $taskId = DB::table('location_tasks')->insertGetId([
                'location_id' => $locationId,
                'type' => 'code',
                'title' => 'Sifra lokace',
                'body' => 'Zadej správné heslo pro tuto stopu.',
                'answer_hash' => Hash::make(AnswerNormalizer::normalize($slug)),
                'required_for_completion' => true,
                'reward_prestige' => 25,
                'reward_resources' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('task_hints')->insert([
                'location_task_id' => $taskId,
                'text' => 'Všímej si názvů míst, směru cesty a drobných nápisů kolem sebe.',
                'cost_resources' => 5,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        for ($slot = 1; $slot <= 12; $slot++) {
            DB::table('building_slots')->insert([
                'slot_number' => $slot,
                'cost_resources' => 20 + ($slot * 8),
                'required_colony_level' => (int) ceil($slot / 3),
                'layout_x' => 8 + (($slot - 1) % 4) * 22,
                'layout_y' => 10 + (int) floor(($slot - 1) / 4) * 28,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $buildings = [
            ['obyvak', 'Obývák', 'Útulná místnost pro odpočinek celé výpravy.', 1, 35, '/assets/game/rooms/obyvak.svg'],
            ['kuchyn', 'Kuchyň', 'Místo, kde se třídí dobroty, semínka a lesní zásoby.', 1, 45, '/assets/game/rooms/kuchar.svg'],
            ['hudebna', 'Hudebna', 'Komůrka pro zpěv, rytmus a večerní oslavy.', 2, 55, '/assets/game/rooms/muzikant.svg'],
            ['telocvicna', 'Tělocvična', 'Prostor na trénink síly, skoků a mravenčí obratnosti.', 2, 60, '/assets/game/rooms/sportovec.svg'],
            ['sklad', 'Sklad', 'Zásobárna materiálu a budoucích plánů.', 3, 70, '/assets/placeholders/building-sklad.svg'],
        ];

        foreach ($buildings as $i => [$slug, $name, $description, $level, $cost, $assetPath]) {
            $buildingId = DB::table('buildings')->insertGetId([
                'slug' => $slug,
                'name' => $name,
                'description' => $description,
                'min_colony_level' => $level,
                'cost_resources' => $cost,
                'svg_asset_path' => $assetPath,
                'layout_x' => 10 + ($i * 16),
                'layout_y' => 16 + ($i % 2) * 22,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (['koberec', 'stena', 'vlajka_symbol'] as $u => $key) {
                DB::table('customization_unlocks')->insert([
                    'building_id' => $buildingId,
                    'key' => $key,
                    'type' => $u === 2 ? 'variant' : 'color',
                    'label' => ucfirst(str_replace('_', ' ', $key)),
                    'options' => json_encode($u === 2 ? ['koruna', 'mravenec', 'stetec'] : ['#b93535', '#d79b63', '#3f7f6f']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('building_tasks')->insert([
                    'building_id' => $buildingId,
                    'title' => 'Speciální úkol ' . ($u + 1),
                    'body' => 'Zadej kód ' . $slug . '-' . ($u + 1) . ' a odemkni úpravu.',
                    'answer_hash' => Hash::make(AnswerNormalizer::normalize($slug . '-' . ($u + 1))),
                    'reward_prestige' => 45,
                    'reward_resources' => 12,
                    'unlock_key' => $key,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        foreach ([
            ['vsechny-ukoly-budovy', 'Dokončení všech úkolů budovy', 80, '/assets/badges/building-master.png'],
            ['prvni-budova', 'První postavená budova', 30, '/assets/badges/first-room.png'],
            ['pet-budov', '5 postavených budov', 100, '/assets/badges/five-rooms.png'],
            ['adminsky-odznacek', 'Adminský odznáček', 0, '/assets/badges/admin.png'],
        ] as [$slug, $name, $bonus, $icon]) {
            DB::table('badges')->insert([
                'slug' => $slug,
                'name' => $name,
                'description' => 'Odznáček za důležitý krok ve výpravě.',
                'icon_path' => $icon,
                'prestige_bonus' => $bonus,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('announcements')->insert([
            'title' => 'Vítej v Prázdninové hře',
            'body' => 'Palouk se probouzí a mravenčí výprava čeká na první odvážné luštitele.',
            'priority' => 'normal',
            'active_from' => now()->subDay(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->call(QuickLaunchSeeder::class);
    }
}
