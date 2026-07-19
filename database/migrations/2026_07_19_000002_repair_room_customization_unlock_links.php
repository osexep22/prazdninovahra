<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('building_task_customization_unlocks')) {
            return;
        }

        $this->upsertHudebnaCarpetPatterns();

        $rules = [
            ['remeslnik', 1, 'variant', ['6__lustr', '6__lampa', '6__lampy']],
            ['remeslnik', 2, 'variant', ['6__lustr', '6__linka', '6__lampa', '6__lampy']],
            ['kuchyn', 1, 'variant', ['3__jidlo', '3__sportovni-doplnky']],
            ['kuchyn', 2, 'variant', ['3__jidlo', '3__sportovni-doplnky']],
            ['krejci', 1, 'pattern', ['2__vzor-koberce']],
            ['krejci', 2, 'pattern', ['2__vzor-koberce']],
        ];

        foreach ($rules as [$sourceSlug, $order, $type, $keys]) {
            $taskId = DB::table('building_tasks')
                ->join('buildings', 'buildings.id', '=', 'building_tasks.building_id')
                ->where('buildings.slug', $sourceSlug)
                ->where('building_tasks.sort_order', $order)
                ->value('building_tasks.id');

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
                        [
                            'building_task_id' => $taskId,
                            'customization_unlock_id' => $unlockId,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        // One-way content repair. Existing player progress/customization data must not be changed.
    }

    private function upsertHudebnaCarpetPatterns(): void
    {
        $buildingId = DB::table('buildings')->where('slug', 'hudebna')->value('id');
        if (! $buildingId) {
            return;
        }

        DB::table('customization_unlocks')->updateOrInsert(
            [
                'building_id' => $buildingId,
                'key' => '2__vzor-koberce',
                'type' => 'pattern',
            ],
            [
                'label' => 'Vzor koberce',
                'options' => json_encode([
                    ['value' => '__off', 'label' => 'Vypnuto'],
                    ['value' => 'edit_pattern__2__kytky', 'label' => 'Kytky'],
                    ['value' => 'edit_pattern__2__kosoctverce', 'label' => 'Kosočtverce'],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
};
