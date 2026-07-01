<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SocialDemoSeeder extends Seeder
{
    public function run(): void
    {
        $jiirka = DB::table('users')
            ->where('username', 'Jiirka')
            ->orWhere('display_name', 'Jiirkak')
            ->first();

        if (! $jiirka) {
            $this->command?->warn('Učet Jiirkak/Jiirka nebyl nalezen.');
            return;
        }

        $demoUsers = [
            ['demo_anka', 'Anka z palouku', 'ANKA2026', 320, 'Ahoj Jiirkak, koukla jsem na tvoje mraveniste. Mas uz plan na dalsi komoru?'],
            ['demo_borek', 'Borek Tunelar', 'BOREK026', 510, 'Nasel jsem kratkou cestu pres mech. Chces ji pak vyzkouset?'],
            ['demo_cilka', 'Cilka Sberacka', 'CILKA026', 210, 'Mam par surovin navic a hledam nekoho na vymenu napoved.'],
        ];

        foreach ($demoUsers as [$username, $displayName, $code, $prestige, $body]) {
            $friendId = DB::table('users')->updateOrInsert(
                ['username' => $username],
                [
                    'display_name' => $displayName,
                    'name' => $displayName,
                    'email' => null,
                    'role' => 'player',
                    'status' => 'active',
                    'friend_code' => $code,
                    'password' => Hash::make('heslo123'),
                    'colony_level' => 2,
                    'resources' => 140,
                    'prestige' => $prestige,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $friend = DB::table('users')->where('username', $username)->first();

            DB::table('friendships')->updateOrInsert(
                ['user_id' => $jiirka->id, 'friend_id' => $friend->id],
                ['created_at' => now(), 'updated_at' => now()]
            );
            DB::table('friendships')->updateOrInsert(
                ['user_id' => $friend->id, 'friend_id' => $jiirka->id],
                ['created_at' => now(), 'updated_at' => now()]
            );

            $a = min($jiirka->id, $friend->id);
            $b = max($jiirka->id, $friend->id);
            $messageId = DB::table('messages')->where([
                'user_id' => $a,
                'recipient_user_id' => $b,
                'thread_type' => 'direct',
            ])->value('id');

            if (! $messageId) {
                $messageId = DB::table('messages')->insertGetId([
                    'user_id' => $a,
                    'recipient_user_id' => $b,
                    'thread_type' => 'direct',
                    'subject' => 'Soukroma zprava',
                    'body' => $body,
                    'status' => 'new',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('message_entries')->insert([
                'message_id' => $messageId,
                'user_id' => $friend->id,
                'sender_role' => 'player',
                'body' => $body,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('messages')->where('id', $messageId)->update([
                'body' => $body,
                'status' => 'new',
                'updated_at' => now(),
            ]);
        }

        $admin = DB::table('users')->where('username', 'admin')->first();
        if ($admin) {
            $adminThreadId = DB::table('messages')->where([
                'user_id' => $jiirka->id,
                'recipient_user_id' => null,
                'thread_type' => 'admin',
            ])->value('id');

            if (! $adminThreadId) {
                $adminThreadId = DB::table('messages')->insertGetId([
                    'user_id' => $jiirka->id,
                    'recipient_user_id' => null,
                    'thread_type' => 'admin',
                    'subject' => 'Admini',
                    'body' => 'Ahoj, tohle je testovací odpověď adminů.',
                    'status' => 'answered',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('message_entries')->insert([
                'message_id' => $adminThreadId,
                'user_id' => $admin->id,
                'sender_role' => 'admin',
                'body' => 'Ahoj, tohle je testovací odpověď adminů. Měl bys ji vidět jako novou v chatu Admini.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('messages')->where('id', $adminThreadId)->update([
                'status' => 'answered',
                'updated_at' => now(),
            ]);
        }
    }
}
