<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $pdfs = [
            'ukol-6' => '/assets/game/tasks/task-6-vodomerka.pdf',
            'ukol-7' => '/assets/game/tasks/task-7-beruska.pdf',
            'ukol-8' => '/assets/game/tasks/task-8-vcela.pdf',
            'ukol-9' => '/assets/game/tasks/task-9-svetluska.pdf',
            'ukol-10' => '/assets/game/tasks/task-10-kobylka.pdf',
        ];

        foreach ($pdfs as $slug => $path) {
            DB::table('location_tasks')
                ->join('locations', 'locations.id', '=', 'location_tasks.location_id')
                ->where('locations.slug', $slug)
                ->update([
                    'location_tasks.pdf_path' => $path,
                    'location_tasks.updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        DB::table('location_tasks')
            ->join('locations', 'locations.id', '=', 'location_tasks.location_id')
            ->whereIn('locations.slug', ['ukol-6', 'ukol-7', 'ukol-8', 'ukol-9', 'ukol-10'])
            ->update([
                'location_tasks.pdf_path' => null,
                'location_tasks.updated_at' => now(),
            ]);
    }
};
