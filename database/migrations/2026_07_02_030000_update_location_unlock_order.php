<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $requirements = [
            'ukol-1' => [],
            'ukol-3' => [],
            'ukol-2' => ['ukol-1'],
            'ukol-4' => ['ukol-3'],
            'ukol-5' => ['ukol-2', 'ukol-4'],
            'ukol-6' => ['ukol-5'],
            'ukol-8' => ['ukol-5'],
            'ukol-10' => ['ukol-6'],
            'ukol-7' => ['ukol-8'],
            'ukol-9' => ['ukol-7', 'ukol-10'],
        ];

        $sortOrder = [
            'ukol-1' => 1,
            'ukol-2' => 2,
            'ukol-3' => 3,
            'ukol-4' => 4,
            'ukol-5' => 5,
            'ukol-6' => 6,
            'ukol-8' => 7,
            'ukol-10' => 8,
            'ukol-7' => 9,
            'ukol-9' => 10,
        ];

        $ids = DB::table('locations')
            ->whereIn('slug', array_keys($requirements))
            ->pluck('id', 'slug')
            ->all();

        foreach ($sortOrder as $slug => $order) {
            if (isset($ids[$slug])) {
                DB::table('locations')->where('id', $ids[$slug])->update(['sort_order' => $order]);
            }
        }

        DB::table('location_requirements')
            ->whereIn('location_id', array_values($ids))
            ->delete();

        foreach ($requirements as $slug => $requiredSlugs) {
            if (! isset($ids[$slug])) {
                continue;
            }

            foreach ($requiredSlugs as $requiredSlug) {
                if (! isset($ids[$requiredSlug])) {
                    continue;
                }

                DB::table('location_requirements')->insert([
                    'location_id' => $ids[$slug],
                    'required_location_id' => $ids[$requiredSlug],
                ]);
            }
        }
    }

    public function down(): void
    {
        $requirements = [
            'ukol-1' => [],
            'ukol-2' => ['ukol-1'],
            'ukol-3' => [],
            'ukol-4' => ['ukol-3'],
            'ukol-5' => ['ukol-2', 'ukol-4'],
            'ukol-6' => ['ukol-5'],
            'ukol-7' => ['ukol-6'],
            'ukol-8' => ['ukol-5'],
            'ukol-9' => ['ukol-8'],
            'ukol-10' => ['ukol-9', 'ukol-7'],
        ];

        $ids = DB::table('locations')
            ->whereIn('slug', array_keys($requirements))
            ->pluck('id', 'slug')
            ->all();

        foreach (array_keys($requirements) as $index => $slug) {
            if (isset($ids[$slug])) {
                DB::table('locations')->where('id', $ids[$slug])->update(['sort_order' => $index + 1]);
            }
        }

        DB::table('location_requirements')
            ->whereIn('location_id', array_values($ids))
            ->delete();

        foreach ($requirements as $slug => $requiredSlugs) {
            if (! isset($ids[$slug])) {
                continue;
            }

            foreach ($requiredSlugs as $requiredSlug) {
                if (! isset($ids[$requiredSlug])) {
                    continue;
                }

                DB::table('location_requirements')->insert([
                    'location_id' => $ids[$slug],
                    'required_location_id' => $ids[$requiredSlug],
                ]);
            }
        }
    }
};
