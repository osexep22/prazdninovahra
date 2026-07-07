<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $images = [
            'ukol-7' => [
                'story_image_path' => '/assets/game/story/task-7-beruska.png',
                'completed_image_path' => '/assets/game/completed/task-7-beruska-completed.png',
            ],
            'ukol-9' => [
                'story_image_path' => '/assets/game/story/task-9-svetluska.png',
                'completed_image_path' => '/assets/game/completed/task-9-svetluska-completed.png',
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
            ->whereIn('slug', ['ukol-7', 'ukol-9'])
            ->update([
                'story_image_path' => null,
                'completed_image_path' => null,
                'updated_at' => now(),
            ]);
    }
};
