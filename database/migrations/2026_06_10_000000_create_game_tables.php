<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_sources', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->text('story')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('reward_prestige')->default(0);
            $table->integer('reward_resources')->default(0);
            $table->integer('reward_colony_level')->default(0);
            $table->string('svg_locked')->nullable();
            $table->string('svg_available')->nullable();
            $table->string('svg_completed')->nullable();
            $table->unsignedInteger('map_x')->default(0);
            $table->unsignedInteger('map_y')->default(0);
            $table->timestamps();
        });

        Schema::create('location_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('required_location_id')->constrained('locations')->cascadeOnDelete();
            $table->unique(['location_id', 'required_location_id']);
        });

        Schema::create('location_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('code');
            $table->string('title');
            $table->text('body');
            $table->string('answer_hash')->nullable();
            $table->boolean('required_for_completion')->default(true);
            $table->integer('reward_prestige')->default(0);
            $table->integer('reward_resources')->default(0);
            $table->timestamps();
        });

        Schema::create('task_hints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_task_id')->constrained()->cascadeOnDelete();
            $table->text('text');
            $table->integer('cost_resources')->default(5);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });

        Schema::create('user_location_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('available');
            $table->timestamp('completed_at')->nullable();
            $table->unique(['user_id', 'location_id']);
        });

        Schema::create('user_task_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_task_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('submitted_answer')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'location_task_id']);
        });

        Schema::create('user_hint_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hint_id')->constrained('task_hints')->cascadeOnDelete();
            $table->timestamp('purchased_at');
            $table->integer('cost_paid');
            $table->unique(['user_id', 'hint_id']);
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->unsignedInteger('min_colony_level')->default(1);
            $table->integer('cost_resources')->default(25);
            $table->string('svg_asset_path');
            $table->unsignedInteger('layout_x')->default(0);
            $table->unsignedInteger('layout_y')->default(0);
            $table->unsignedInteger('layout_w')->default(160);
            $table->unsignedInteger('layout_h')->default(120);
            $table->timestamps();
        });

        Schema::create('building_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('slot_number')->unique();
            $table->integer('cost_resources')->default(20);
            $table->unsignedInteger('required_colony_level')->default(1);
            $table->unsignedInteger('layout_x')->default(0);
            $table->unsignedInteger('layout_y')->default(0);
            $table->timestamps();
        });

        Schema::create('user_building_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_slot_id')->constrained()->cascadeOnDelete();
            $table->timestamp('purchased_at');
            $table->integer('cost_paid');
            $table->unique(['user_id', 'building_slot_id']);
        });

        Schema::create('user_buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_slot_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'building_id']);
            $table->unique(['user_id', 'building_slot_id']);
        });

        Schema::create('building_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('answer_hash')->nullable();
            $table->integer('reward_prestige')->default(0);
            $table->integer('reward_resources')->default(0);
            $table->string('unlock_key')->nullable();
            $table->timestamps();
        });

        Schema::create('user_building_task_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_task_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('submitted_answer')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'building_task_id']);
        });

        Schema::create('customization_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('type');
            $table->string('label');
            $table->json('options')->nullable();
            $table->timestamps();
        });

        Schema::create('user_customization_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customization_unlock_id')->constrained()->cascadeOnDelete();
            $table->timestamp('unlocked_at');
            $table->unique(['user_id', 'customization_unlock_id'], 'ucu_user_unlock_unique');
        });

        Schema::create('user_building_customizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->json('config_json');
            $table->timestamps();
            $table->unique(['user_id', 'building_id']);
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->integer('prestige_bonus')->default(0);
            $table->timestamps();
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained()->cascadeOnDelete();
            $table->timestamp('awarded_at');
            $table->string('awarded_for_entity_type')->nullable();
            $table->unsignedBigInteger('awarded_for_entity_id')->nullable();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('priority')->default('normal');
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->timestamp('seen_at');
            $table->unique(['user_id', 'announcement_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->text('admin_reply')->nullable();
            $table->string('status')->default('new');
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        foreach ([
            'audit_logs', 'messages', 'user_announcement_reads', 'announcements',
            'user_badges', 'badges', 'user_building_customizations',
            'user_customization_unlocks', 'customization_unlocks',
            'user_building_task_progress', 'building_tasks', 'user_buildings',
            'user_building_slots', 'building_slots', 'buildings', 'user_hint_purchases',
            'user_task_progress', 'user_location_progress', 'task_hints',
            'location_tasks', 'location_requirements', 'locations',
            'registration_sources',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
