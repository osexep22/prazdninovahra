<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $images = [
            'ukol-6' => [
                'story_image_path' => '/assets/game/story/task-6-vodomerka.png',
                'completed_image_path' => '/assets/game/completed/task-6-vodomerka-completed.png',
            ],
            'ukol-8' => [
                'story_image_path' => '/assets/game/story/task-8-vcela.png',
                'completed_image_path' => '/assets/game/completed/task-8-vcela-completed.png',
            ],
            'ukol-10' => [
                'story_image_path' => '/assets/game/story/task-10-kobylka.png',
                'completed_image_path' => '/assets/game/completed/task-10-kobylka-completed.png',
            ],
        ];

        foreach ($images as $slug => $paths) {
            DB::table('locations')->where('slug', $slug)->update($paths + [
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('locations')
            ->whereIn('slug', ['ukol-6', 'ukol-8', 'ukol-10'])
            ->update([
                'story_image_path' => null,
                'completed_image_path' => null,
                'updated_at' => now(),
            ]);
    }
};
