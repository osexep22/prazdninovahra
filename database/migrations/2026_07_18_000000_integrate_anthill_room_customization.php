<?php

use App\Support\AnswerNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('building_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('building_tasks', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
        });

        if (! Schema::hasTable('building_task_customization_unlocks')) {
            Schema::create('building_task_customization_unlocks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('building_task_id');
                $table->unsignedBigInteger('customization_unlock_id');
                $table->timestamps();
                $table->unique(['building_task_id', 'customization_unlock_id'], 'btcu_task_unlock_unique');
                $table->foreign('building_task_id', 'btcu_task_fk')->references('id')->on('building_tasks')->cascadeOnDelete();
                $table->foreign('customization_unlock_id', 'btcu_unlock_fk')->references('id')->on('customization_unlocks')->cascadeOnDelete();
            });
        }

        $buildings = [
            'telocvicna' => ['Sportovec', '/assets/game/rooms/sportovec.svg'],
            'krejci' => ['Krejčí', '/assets/game/rooms/krejci.svg'],
            'kuchyn' => ['Kuchař', '/assets/game/rooms/kuchar.svg'],
            'malir' => ['Malíř', '/assets/game/rooms/malir.svg'],
            'obyvak' => ['Obývák', '/assets/game/rooms/obyvak.svg'],
            'remeslnik' => ['Řemeslník', '/assets/game/rooms/kutil.svg'],
            'hudebna' => ['Muzikant', '/assets/game/rooms/muzikant.svg'],
            'zahradnik' => ['Zahradník', '/assets/game/rooms/zahradnik.svg'],
            'porodnice' => ['Porodnice', '/assets/game/rooms/porodnice.svg'],
            'hospoda' => ['Hospoda', '/assets/game/rooms/hospoda.svg'],
        ];

        foreach ($buildings as $slug => [$name, $asset]) {
            DB::table('buildings')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $this->buildingDescription($slug),
                    'svg_asset_path' => $asset,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        foreach ($buildings as $slug => [$name]) {
            $buildingId = DB::table('buildings')->where('slug', $slug)->value('id');
            if (! $buildingId) {
                continue;
            }

            DB::table('building_tasks')
                ->where('building_id', $buildingId)
                ->whereNotIn('sort_order', [1, 2])
                ->update(['is_active' => false, 'updated_at' => now()]);

            foreach ([1, 2] as $order) {
                DB::table('building_tasks')->updateOrInsert(
                    ['building_id' => $buildingId, 'sort_order' => $order],
                    [
                        'title' => $this->taskTitle($slug, $order),
                        'body' => $this->taskBody($slug, $order),
                        'answer_hash' => Hash::make(AnswerNormalizer::normalize($slug . '-' . $order)),
                        'reward_prestige' => 150,
                        'reward_resources' => 0,
                        'unlock_key' => null,
                        'unlock_description' => $this->unlockDescription($slug, $order),
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $badgeSlug = 'budova-' . $slug . '-ukol-' . $order;
                DB::table('badges')->updateOrInsert(
                    ['slug' => $badgeSlug],
                    [
                        'name' => $name . ' - úkol ' . $order,
                        'description' => 'Odznáček za splnění ' . $order . '. úkolu místnosti ' . $name . '.',
                        'icon_path' => '/assets/badges/' . $badgeSlug . '.svg',
                        'prestige_bonus' => 0,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                DB::table('badges')->updateOrInsert(
                    ['slug' => 'top-10-' . $badgeSlug],
                    [
                        'name' => 'První desítka: ' . $name . ' - úkol ' . $order,
                        'description' => 'Zlatý odznáček pro prvních 10 hráčů, kteří splní ' . $order . '. úkol místnosti ' . $name . '.',
                        'icon_path' => '/assets/badges/top-10-' . $badgeSlug . '.svg',
                        'prestige_bonus' => 25,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        $this->seedCustomizationUnlocks($buildings);
        $this->seedTaskUnlockLinks();
    }

    public function down(): void
    {
        Schema::dropIfExists('building_task_customization_unlocks');

        Schema::table('building_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('building_tasks', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }

    private function seedCustomizationUnlocks(array $buildings): void
    {
        $patterns = [
            'telocvicna' => [['edit_pattern__5__vzor', 'Čtverečky'], ['edit_pattern__5__vlny', 'Vlny']],
            'krejci' => [['edit_pattern__5__vlny', 'Vlny'], ['edit_pattern__5__zuby', 'Zuby']],
            'kuchyn' => [['edit_pattern__5__kosoctverce', 'Kosočtverce'], ['edit_pattern__5__cihly', 'Cihly']],
            'malir' => [['edit_pattern__5__palma', 'Palmy'], ['edit_pattern__5__puntiky', 'Puntíky']],
            'obyvak' => [['edit_pattern__5__kytky', 'Kytky'], ['edit_pattern__5__tapeta', 'Tapeta'], ['edit_pattern__5__kolecka', 'Kolečka']],
            'remeslnik' => [['edit_pattern__5__zed__cihly', 'Cihly'], ['edit_pattern__5__zed__pruhy', 'Pruhy']],
            'hudebna' => [['edit_pattern__5__noty', 'Noty']],
            'zahradnik' => [['edit_pattern__5__zed__kytky', 'Kytky'], ['edit_pattern__5__zed__listy', 'Listy']],
            'porodnice' => [['edit_pattern__5__vlny', 'Vlny'], ['edit_pattern__5__zuby', 'Zuby']],
            'hospoda' => [['edit_pattern__5__pruhy', 'Pruhy'], ['edit_pattern__5__puntiky', 'Puntíky']],
        ];

        $extras = [
            'telocvicna' => [
                ['variant', '3__sportovni-doplnky', 'Sportovní doplňky', [['edit_variant__3__hrnek', 'Hrnek'], ['edit_variant__3__sklenice', 'Sklenice']]],
            ],
            'krejci' => [
                ['variant', '3__jidlo', 'Jídlo', [['edit_variant__3__ovoce', 'Ovoce'], ['edit_variant__3__strava', 'Svačinka']]],
            ],
            'kuchyn' => [
                ['variant', '3__voda', 'Voda', [['edit_variant__3__voda', 'Zobrazit']]],
                ['variant', '6__linka', 'Kuchyňská linka', [['edit_variant__6__linka', 'Zobrazit']]],
                ['variant', '9__otevreno', 'Otevřeno', [['edit_variant__9__otevreno', 'Zobrazit']]],
                ['color', '3__stul', 'Barva stolu', []],
                ['color', '3__hrnec', 'Barva hrnce', []],
            ],
            'malir' => [
                ['variant', '3__jidlo', 'Jídlo', [['edit_variant__3__ovoce', 'Ovoce'], ['edit_variant__3__strava', 'Svačinka']]],
                ['variant', '8__kytka', 'Kytka', [['edit_variant__8__kytka', 'Zobrazit']]],
            ],
            'obyvak' => [
                ['variant', '5__bryle', 'Druh brýlí', [['edit_variant__5__bryle', 'Brýle'], ['edit_variant__5__hvezda', 'Hvězdné brýle']]],
                ['variant', '6__lampa', 'Lampa', [['edit_variant__6__lampa', 'Zobrazit']]],
                ['variant', '8__kytka', 'Kytka', [['edit_variant__8__kytka', 'Zobrazit']]],
                ['variant', '3__voda', 'Voda', [['edit_variant__3__voda', 'Zobrazit']]],
            ],
            'hudebna' => [
                ['color', '7__klobouk', 'Barva klobouku', []],
                ['color', '7__pruh', 'Barva pruhu na klobouku', []],
            ],
            'porodnice' => [
                ['variant', '6__lampy', 'Lampy', [['edit_variant__6__lampy', 'Zobrazit']]],
            ],
            'hospoda' => [
                ['variant', '3__pivo', 'Pivo', [['edit_variant__3__pivo', 'Zobrazit']]],
                ['variant', '4__obraz', 'Obraz', [['edit_variant__4__obraz', 'Zobrazit']]],
            ],
        ];

        foreach ($buildings as $slug => $_) {
            $buildingId = DB::table('buildings')->where('slug', $slug)->value('id');
            if (! $buildingId) {
                continue;
            }

            $this->upsertUnlock($buildingId, 'color', $slug === 'hudebna' ? '4__stena_komory' : '4__stena', 'Barva stěny');
            $this->upsertUnlock($buildingId, 'color', '2__koberec', 'Barva koberce');
            $this->upsertUnlock($buildingId, 'pattern', '5__vzor-na-zdi', 'Vzor na zdi', array_merge([['__off', 'Vypnuto']], $patterns[$slug] ?? []));
            if ($slug !== 'zahradnik') {
                $this->upsertUnlock($buildingId, 'variant', '6__lustr', 'Lustr', [
                    ['__off', 'Bez lustru'],
                    ['edit_variant__6__lustr__1', 'Lustr 1'],
                    ['edit_variant__6__lustr__2', 'Lustr 2'],
                    ['edit_variant__6__lustr__3', 'Lustr 3'],
                    ['edit_variant__6__lustr__4', 'Lustr 4'],
                    ['edit_variant__6__lustr__5', 'Lustr 5'],
                ]);
                foreach ([1, 2, 3] as $index) {
                    $this->upsertUnlock($buildingId, 'color', '6__lustr__' . $index, 'Barva lustru');
                }
            }
            foreach ($extras[$slug] ?? [] as [$type, $key, $label, $options]) {
                $this->upsertUnlock($buildingId, $type, $key, $label, $options);
            }
        }
    }

    private function seedTaskUnlockLinks(): void
    {
        $rules = [
            ['malir', 1, 'color', ['4__stena', '4__stena_komory']],
            ['malir', 2, 'color', ['4__stena', '4__stena_komory']],
            ['krejci', 1, 'color', ['2__koberec']],
            ['krejci', 2, 'color', ['2__koberec']],
            ['obyvak', 1, 'pattern', ['5__vzor-na-zdi']],
            ['obyvak', 2, 'pattern', ['5__vzor-na-zdi']],
            ['remeslnik', 1, 'variant', ['6__lustr', '6__linka', '6__lampa', '6__lampy']],
            ['remeslnik', 2, 'variant', ['6__lustr', '6__linka', '6__lampa', '6__lampy']],
            ['remeslnik', 2, 'color', ['6__lustr__1', '6__lustr__2', '6__lustr__3']],
            ['kuchyn', 1, 'variant', ['3__sportovni-doplnky']],
            ['kuchyn', 1, 'variant', ['3__jidlo']],
            ['kuchyn', 2, 'variant', ['3__jidlo', '3__voda']],
            ['kuchyn', 2, 'color', ['3__stul', '3__hrnec']],
            ['obyvak', 1, 'variant', ['5__bryle']],
            ['porodnice', 1, 'variant', ['9__otevreno']],
            ['malir', 1, 'variant', ['4__obraz']],
            ['hospoda', 1, 'variant', ['3__pivo']],
            ['hospoda', 2, 'variant', ['3__voda']],
            ['zahradnik', 1, 'variant', ['8__kytka']],
            ['hudebna', 1, 'color', ['7__klobouk']],
            ['hudebna', 2, 'color', ['7__pruh']],
        ];

        foreach ($rules as [$sourceSlug, $order, $type, $keys]) {
            $sourceBuildingId = DB::table('buildings')->where('slug', $sourceSlug)->value('id');
            $taskId = $sourceBuildingId ? DB::table('building_tasks')->where(['building_id' => $sourceBuildingId, 'sort_order' => $order])->value('id') : null;
            if (! $taskId) {
                continue;
            }

            foreach ($keys as $key) {
                $unlockIds = DB::table('customization_unlocks')
                    ->where('type', $type)
                    ->where('key', $key)
                    ->pluck('id');
                foreach ($unlockIds as $unlockId) {
                    DB::table('building_task_customization_unlocks')->updateOrInsert(
                        ['building_task_id' => $taskId, 'customization_unlock_id' => $unlockId],
                        ['updated_at' => now(), 'created_at' => now()]
                    );
                }
            }
        }
    }

    private function upsertUnlock(int $buildingId, string $type, string $key, string $label, array $options = []): void
    {
        DB::table('customization_unlocks')->updateOrInsert(
            ['building_id' => $buildingId, 'key' => $key, 'type' => $type],
            [
                'label' => $label,
                'options' => $options === [] ? null : json_encode(array_map(fn ($option) => [
                    'value' => $option[0],
                    'label' => $option[1],
                ], $options), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function taskTitle(string $slug, int $order): string
    {
        return [
            'telocvicna' => ['Rychlé nožky', 'Silná tykadla'],
            'krejci' => ['První stehy', 'Výprava do látky'],
            'kuchyn' => ['Lesní zásoby', 'Hostina pro kolonii'],
            'malir' => ['Míchání barev', 'Velká výzdoba'],
            'obyvak' => ['Útulný kout', 'Vzory domova'],
            'remeslnik' => ['Světlo v dílně', 'Mistr vylepšení'],
            'hudebna' => ['Rytmus chodbiček', 'Píseň večera'],
            'zahradnik' => ['První sazenička', 'Zelený plán'],
            'porodnice' => ['Tiché chůvičky', 'Bezpečný pelíšek'],
            'hospoda' => ['Malý lok', 'Velká hostina'],
        ][$slug][$order - 1] ?? ('Úkol ' . $order);
    }

    private function taskBody(string $slug, int $order): string
    {
        return 'Splň úkol místnosti a zadej správný kód. Odměna pomůže kolonii a odemkne novou úpravu vzhledu.';
    }

    private function unlockDescription(string $slug, int $order): string
    {
        return [
            'telocvicna' => ['sportovní doplňky v některých místnostech', 'další sportovní drobnosti'],
            'krejci' => ['základní barvy koberce', 'volnější úpravy koberců'],
            'kuchyn' => ['ovoce a první dobroty', 'svačinku, vodu a kuchyňské doplňky'],
            'malir' => ['základní barvy stěn', 'volnější malování stěn'],
            'obyvak' => ['první vzory na zdi', 'další vzory na zdi'],
            'remeslnik' => ['první lustry', 'další lustry a barvy lustru'],
            'hudebna' => ['barvu klobouku muzikanta', 'barvu pruhu na klobouku'],
            'zahradnik' => ['kytky a zahradní ozdoby', 'další zahradní drobnosti'],
            'porodnice' => ['otevírání bezpečných úkrytů', 'další péči o komůrky'],
            'hospoda' => ['pivo v hospodě', 'vodu v některých místnostech'],
        ][$slug][$order - 1] ?? 'novou úpravu vzhledu';
    }

    private function buildingDescription(string $slug): string
    {
        return [
            'telocvicna' => 'Tréninková komůrka pro sílu, rychlost a mravenčí obratnost.',
            'krejci' => 'Dílna, kde se látá, šije a připravuje výbava pro výpravu.',
            'kuchyn' => 'Místnost pro třídění dobrot a lesních zásob.',
            'malir' => 'Tvořivá komůrka pro barvy, značky a výzdobu mraveniště.',
            'obyvak' => 'Útulná místnost pro odpočinek celé výpravy.',
            'remeslnik' => 'Kutilova dílna pro opravy, světla a zvláštní vylepšení.',
            'hudebna' => 'Komůrka pro rytmus, zpěv a večerní oslavy.',
            'zahradnik' => 'Zelená místnost pro pěstování a péči o živé ozdoby.',
            'porodnice' => 'Klidná komůrka pro nejmenší členy kolonie.',
            'hospoda' => 'Místo setkávání, odpočinku a malých oslav.',
        ][$slug] ?? 'Místnost v mraveništi.';
    }
};
