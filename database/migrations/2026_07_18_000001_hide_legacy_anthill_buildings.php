<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $activeSlugs = [
            'telocvicna',
            'krejci',
            'kuchyn',
            'malir',
            'obyvak',
            'remeslnik',
            'hudebna',
            'zahradnik',
            'porodnice',
            'hospoda',
        ];

        $legacyBuildingIds = DB::table('buildings')
            ->whereNotIn('slug', $activeSlugs)
            ->pluck('id');

        if ($legacyBuildingIds->isEmpty()) {
            return;
        }

        $payload = ['updated_at' => now()];
        if (Schema::hasColumn('buildings', 'is_available')) {
            $payload['is_available'] = false;
        }
        if (Schema::hasColumn('buildings', 'min_colony_level')) {
            $payload['min_colony_level'] = 11;
        }

        DB::table('buildings')->whereIn('id', $legacyBuildingIds)->update($payload);

        if (Schema::hasColumn('building_tasks', 'is_active')) {
            DB::table('building_tasks')
                ->whereIn('building_id', $legacyBuildingIds)
                ->update(['is_active' => false, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Intentionally no-op: do not re-enable legacy/test building catalog entries automatically.
    }
};
