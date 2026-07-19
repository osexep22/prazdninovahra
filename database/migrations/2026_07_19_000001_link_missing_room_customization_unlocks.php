<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These links are safe to run after the original customization migration has
     * already been deployed. They only add missing unlock mappings.
     */
    private array $rules = [
        ['remeslnik', 1, 'variant', ['6__linka', '6__lampa', '6__lampy']],
        ['remeslnik', 2, 'variant', ['6__linka', '6__lampa', '6__lampy']],
        ['kuchyn', 1, 'variant', ['3__sportovni-doplnky']],
        ['obyvak', 1, 'variant', ['5__bryle']],
        ['malir', 1, 'variant', ['4__obraz']],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('building_task_customization_unlocks')) {
            return;
        }

        foreach ($this->rules as [$sourceSlug, $order, $type, $keys]) {
            $taskId = $this->taskId($sourceSlug, $order);
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
        if (! Schema::hasTable('building_task_customization_unlocks')) {
            return;
        }

        foreach ($this->rules as [$sourceSlug, $order, $type, $keys]) {
            $taskId = $this->taskId($sourceSlug, $order);
            if (! $taskId) {
                continue;
            }

            $unlockIds = DB::table('customization_unlocks')
                ->where('type', $type)
                ->whereIn('key', $keys)
                ->pluck('id');

            DB::table('building_task_customization_unlocks')
                ->where('building_task_id', $taskId)
                ->whereIn('customization_unlock_id', $unlockIds)
                ->delete();
        }
    }

    private function taskId(string $sourceSlug, int $order): ?int
    {
        return DB::table('building_tasks')
            ->join('buildings', 'buildings.id', '=', 'building_tasks.building_id')
            ->where('buildings.slug', $sourceSlug)
            ->where('building_tasks.sort_order', $order)
            ->value('building_tasks.id');
    }
};
