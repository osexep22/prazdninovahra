<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            if (! Schema::hasColumn('buildings', 'tooltip')) {
                $table->string('tooltip', 240)->nullable()->after('description');
            }
            if (! Schema::hasColumn('buildings', 'detail_text')) {
                $table->text('detail_text')->nullable()->after('tooltip');
            }
            if (! Schema::hasColumn('buildings', 'customization_schema')) {
                $table->json('customization_schema')->nullable()->after('svg_asset_path');
            }
        });

        Schema::table('building_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('building_tasks', 'unlock_description')) {
                $table->string('unlock_description', 240)->nullable()->after('unlock_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('building_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('building_tasks', 'unlock_description')) {
                $table->dropColumn('unlock_description');
            }
        });

        Schema::table('buildings', function (Blueprint $table) {
            foreach (['customization_schema', 'detail_text', 'tooltip'] as $column) {
                if (Schema::hasColumn('buildings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
