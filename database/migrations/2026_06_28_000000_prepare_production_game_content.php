<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('intro_seen_at')->nullable()->after('last_customization_change_at');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('story');
            $table->string('story_image_path')->nullable()->after('image_path');
            $table->string('tooltip')->nullable()->after('story_image_path');
            $table->unsignedInteger('sort_order')->default(1)->after('tooltip');
        });

        Schema::table('location_tasks', function (Blueprint $table) {
            $table->foreignId('parent_task_id')->nullable()->after('location_id')->constrained('location_tasks')->nullOnDelete();
            $table->string('pdf_path')->nullable()->after('body');
            $table->unsignedInteger('sort_order')->default(1)->after('pdf_path');
        });

        Schema::table('building_tasks', function (Blueprint $table) {
            $table->foreignId('parent_task_id')->nullable()->after('building_id')->constrained('building_tasks')->nullOnDelete();
            $table->string('pdf_path')->nullable()->after('body');
            $table->unsignedInteger('sort_order')->default(1)->after('pdf_path');
        });

        Schema::create('game_contents', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->longText('body_top')->nullable();
            $table->string('image_path')->nullable();
            $table->longText('body_bottom')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_contents');

        Schema::table('building_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_task_id');
            $table->dropColumn(['pdf_path', 'sort_order']);
        });

        Schema::table('location_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_task_id');
            $table->dropColumn(['pdf_path', 'sort_order']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'story_image_path', 'tooltip', 'sort_order']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('intro_seen_at');
        });
    }
};
