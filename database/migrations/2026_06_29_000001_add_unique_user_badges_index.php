<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('user_badges')
            ->selectRaw('MIN(id) as keep_id, user_id, badge_id')
            ->groupBy('user_id', 'badge_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('user_badges')
                ->where('user_id', $duplicate->user_id)
                ->where('badge_id', $duplicate->badge_id)
                ->where('id', '<>', $duplicate->keep_id)
                ->delete();
        }

        Schema::table('user_badges', function (Blueprint $table) {
            $table->unique(['user_id', 'badge_id'], 'user_badges_user_id_badge_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('user_badges', function (Blueprint $table) {
            $table->dropUnique('user_badges_user_id_badge_id_unique');
        });
    }
};
