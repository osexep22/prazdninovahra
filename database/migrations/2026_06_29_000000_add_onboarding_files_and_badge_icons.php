<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('meadow_onboarding_seen_at')->nullable()->after('intro_seen_at');
            $table->string('admin_contact_code_hash')->nullable()->after('admin_contact_password');
        });

        Schema::table('badges', function (Blueprint $table) {
            $table->string('icon_path')->nullable()->after('description');
        });

        Schema::create('game_files', function (Blueprint $table) {
            $table->id();
            $table->string('disk_path')->unique();
            $table->string('public_path')->unique();
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('category')->default('general');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_files');

        Schema::table('badges', function (Blueprint $table) {
            $table->dropColumn('icon_path');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['meadow_onboarding_seen_at', 'admin_contact_code_hash']);
        });
    }
};
