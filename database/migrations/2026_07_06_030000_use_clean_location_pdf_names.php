<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $paths = [
            'ukol-1' => '/assets/game/tasks/Rohac.pdf',
            'ukol-2' => '/assets/game/tasks/Cervotoc.pdf',
            'ukol-3' => '/assets/game/tasks/Vazka.pdf',
            'ukol-4' => '/assets/game/tasks/Snek.pdf',
            'ukol-5' => '/assets/game/tasks/Stonozka.pdf',
            'ukol-6' => '/assets/game/tasks/Vodomerka.pdf',
            'ukol-7' => '/assets/game/tasks/Beruska.pdf',
            'ukol-8' => '/assets/game/tasks/Vcelka.pdf',
            'ukol-9' => '/assets/game/tasks/Svetluska.pdf',
            'ukol-10' => '/assets/game/tasks/Kobylka.pdf',
        ];

        foreach ($paths as $slug => $path) {
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
        $paths = [
            'ukol-1' => '/assets/game/tasks/task-1-rohac.pdf',
            'ukol-2' => '/assets/game/tasks/task-2-cervotoc.pdf',
            'ukol-3' => '/assets/game/tasks/task-3-vazka.pdf',
            'ukol-4' => '/assets/game/tasks/task-4-snek.pdf',
            'ukol-5' => '/assets/game/tasks/task-5-stonozka.pdf',
            'ukol-6' => '/assets/game/tasks/task-6-vodomerka.pdf',
            'ukol-7' => '/assets/game/tasks/task-7-beruska.pdf',
            'ukol-8' => '/assets/game/tasks/task-8-vcela.pdf',
            'ukol-9' => '/assets/game/tasks/task-9-svetluska.pdf',
            'ukol-10' => '/assets/game/tasks/task-10-kobylka.pdf',
        ];

        foreach ($paths as $slug => $path) {
            DB::table('location_tasks')
                ->join('locations', 'locations.id', '=', 'location_tasks.location_id')
                ->where('locations.slug', $slug)
                ->update([
                    'location_tasks.pdf_path' => $path,
                    'location_tasks.updated_at' => now(),
                ]);
        }
    }
};
