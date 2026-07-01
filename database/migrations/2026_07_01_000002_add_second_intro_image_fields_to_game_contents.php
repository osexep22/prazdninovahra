<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_contents', function (Blueprint $table) {
            $table->longText('body_middle')->nullable()->after('image_path');
            $table->string('image_path_2')->nullable()->after('body_middle');
        });
    }

    public function down(): void
    {
        Schema::table('game_contents', function (Blueprint $table) {
            $table->dropColumn(['body_middle', 'image_path_2']);
        });
    }
};
