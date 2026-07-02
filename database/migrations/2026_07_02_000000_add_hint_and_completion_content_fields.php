<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (! Schema::hasColumn('locations', 'completed_image_path')) {
                $table->string('completed_image_path')->nullable()->after('story_image_path');
            }
        });

        Schema::table('location_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('location_tasks', 'pdf_intro')) {
                $table->text('pdf_intro')->nullable()->after('body');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('resources')->default(0)->change();
        });

        DB::table('task_hints')->update(['cost_resources' => 0]);
    }

    public function down(): void
    {
        Schema::table('location_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('location_tasks', 'pdf_intro')) {
                $table->dropColumn('pdf_intro');
            }
        });

        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasColumn('locations', 'completed_image_path')) {
                $table->dropColumn('completed_image_path');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('resources')->default(80)->change();
        });
    }
};
