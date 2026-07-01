<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('friend_code')->nullable()->unique()->after('registration_source');
            $table->json('ant_avatar_config')->nullable()->after('last_customization_change_at');
        });

        $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $digits = '23456789';
        $makeCode = function () use ($letters, $digits): string {
            $code = '';
            for ($i = 0; $i < 3; $i++) {
                $code .= $letters[random_int(0, strlen($letters) - 1)];
                $code .= $digits[random_int(0, strlen($digits) - 1)];
            }

            return $code;
        };

        DB::table('users')->orderBy('id')->get()->each(function ($user) use ($makeCode) {
            do {
                $code = $makeCode();
            } while (DB::table('users')->where('friend_code', $code)->exists());

            DB::table('users')->where('id', $user->id)->update([
                'friend_code' => $code,
            ]);
        });

        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('friend_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'friend_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('recipient_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('thread_type')->default('admin')->after('recipient_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recipient_user_id');
            $table->dropColumn('thread_type');
        });
        Schema::dropIfExists('friendships');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['friend_code', 'ant_avatar_config']);
        });
    }
};
