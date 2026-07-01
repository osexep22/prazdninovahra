<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sender_role');
            $table->text('body');
            $table->timestamps();
        });

        DB::table('messages')->orderBy('id')->get()->each(function ($message) {
            DB::table('message_entries')->insert([
                'message_id' => $message->id,
                'user_id' => $message->user_id,
                'sender_role' => 'player',
                'body' => $message->body,
                'created_at' => $message->created_at,
                'updated_at' => $message->updated_at,
            ]);

            if ($message->admin_reply) {
                DB::table('message_entries')->insert([
                    'message_id' => $message->id,
                    'user_id' => $message->admin_user_id,
                    'sender_role' => 'admin',
                    'body' => $message->admin_reply,
                    'created_at' => $message->answered_at ?? $message->updated_at,
                    'updated_at' => $message->answered_at ?? $message->updated_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_entries');
    }
};
