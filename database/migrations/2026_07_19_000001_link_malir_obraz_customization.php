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

        $taskId = DB::table('building_tasks')
            ->join('buildings', 'buildings.id', '=', 'building_tasks.building_id')
            ->where('buildings.slug', 'malir')
            ->where('building_tasks.sort_order', 1)
            ->value('building_tasks.id');

        if (! $taskId) {
            return;
        }

        $unlockIds = DB::table('customization_unlocks')
            ->where('type', 'variant')
            ->where('key', '4__obraz')
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

    public function down(): void
    {
        if (! Schema::hasTable('building_task_customization_unlocks')) {
            return;
        }

        $taskId = DB::table('building_tasks')
            ->join('buildings', 'buildings.id', '=', 'building_tasks.building_id')
            ->where('buildings.slug', 'malir')
            ->where('building_tasks.sort_order', 1)
            ->value('building_tasks.id');

        if (! $taskId) {
            return;
        }

        $unlockIds = DB::table('customization_unlocks')
            ->where('type', 'variant')
            ->where('key', '4__obraz')
            ->pluck('id');

        DB::table('building_task_customization_unlocks')
            ->where('building_task_id', $taskId)
            ->whereIn('customization_unlock_id', $unlockIds)
            ->delete();
    }
};
