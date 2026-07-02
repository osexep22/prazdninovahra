<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$contentTables = [
    'registration_sources',
    'locations',
    'location_requirements',
    'location_tasks',
    'task_hints',
    'building_slots',
    'buildings',
    'building_tasks',
    'customization_unlocks',
    'badges',
    'announcements',
    'game_contents',
    'game_files',
    'economy_settings',
];

$playerDataTables = [
    'message_reads',
    'message_entries',
    'messages',
    'friendships',
    'admin_notes',
    'audit_logs',
    'user_announcement_reads',
    'user_badges',
    'user_building_customizations',
    'user_customization_unlocks',
    'user_building_task_progress',
    'user_buildings',
    'user_building_slots',
    'user_hint_purchases',
    'user_task_progress',
    'user_location_progress',
    'password_reset_tokens',
    'sessions',
    'users',
];

$resolveGameTransferPath = function (string $path): string {
    if (Str::startsWith($path, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
        return $path;
    }

    return base_path($path);
};

$withoutForeignKeyChecks = function (Closure $callback): mixed {
    $driver = DB::getDriverName();

    if ($driver === 'mysql') {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
    } elseif ($driver === 'sqlite') {
        DB::statement('PRAGMA foreign_keys = OFF');
    }

    try {
        return $callback();
    } finally {
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
};

$clearTable = function (string $table): void {
    if (! Schema::hasTable($table)) {
        return;
    }

    DB::table($table)->delete();
};

Artisan::command('game:export-content {path=storage/app/game-content-export.json}', function (string $path) use ($contentTables, $resolveGameTransferPath) {
    $absolutePath = $resolveGameTransferPath($path);
    File::ensureDirectoryExists(dirname($absolutePath));

    $payload = [
        'exported_at' => now()->toIso8601String(),
        'app_url' => config('app.url'),
        'tables' => [],
    ];

    foreach ($contentTables as $table) {
        if (! Schema::hasTable($table)) {
            $payload['tables'][$table] = [];
            continue;
        }

        $rows = DB::table($table)->orderBy('id')->get()->map(function (object $row) use ($table) {
            $row = (array) $row;
            if ($table === 'game_files' && array_key_exists('uploaded_by', $row)) {
                $row['uploaded_by'] = null;
            }

            return $row;
        })->all();

        $payload['tables'][$table] = $rows;
    }

    file_put_contents($absolutePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $this->info('Export hotov: ' . $absolutePath);
    $this->line('Export obsahuje jen herní obsah a nastavení, ne uživatele ani hráčský postup.');
})->purpose('Export game content and economy settings without player data');

Artisan::command('game:import-content {path} {--force : Really replace production game content and remove all users}', function (string $path) use ($contentTables, $playerDataTables, $resolveGameTransferPath, $withoutForeignKeyChecks, $clearTable) {
    if (! $this->option('force')) {
        $this->error('Import je destruktivní. Spusť ho znovu s --force.');
        return 1;
    }

    $absolutePath = $resolveGameTransferPath($path);
    if (! File::exists($absolutePath)) {
        $this->error('Soubor neexistuje: ' . $absolutePath);
        return 1;
    }

    $payload = json_decode(File::get($absolutePath), true);
    if (! is_array($payload) || ! isset($payload['tables']) || ! is_array($payload['tables'])) {
        $this->error('Soubor nemá očekávaný formát exportu.');
        return 1;
    }

    DB::transaction(function () use ($payload, $contentTables, $playerDataTables, $withoutForeignKeyChecks, $clearTable) {
        $withoutForeignKeyChecks(function () use ($payload, $contentTables, $playerDataTables, $clearTable) {
            foreach ($playerDataTables as $table) {
                $clearTable($table);
            }

            foreach (array_reverse($contentTables) as $table) {
                $clearTable($table);
            }

            foreach ($contentTables as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $rows = $payload['tables'][$table] ?? [];
                foreach (array_chunk($rows, 100) as $chunk) {
                    if ($chunk !== []) {
                        DB::table($table)->insert($chunk);
                    }
                }
            }
        });

        $now = now();
        $admin = [
            'name' => 'Jura',
            'display_name' => 'Jura',
            'username' => 'Jura',
            'email' => null,
            'role' => 'admin',
            'status' => 'active',
            'registration_source' => 'admin',
            'colony_level' => 1,
            'resources' => 0,
            'prestige' => 0,
            'password' => Hash::make('JuraJura'),
            'remember_token' => Str::random(10),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('users', 'friend_code')) {
            $admin['friend_code'] = 'JURA01';
        }
        if (Schema::hasColumn('users', 'admin_contact_password')) {
            $admin['admin_contact_password'] = Hash::make('JuraJura');
        }
        if (Schema::hasColumn('users', 'admin_contact_code_hash')) {
            $admin['admin_contact_code_hash'] = Hash::make('JuraJura');
        }
        if (Schema::hasColumn('users', 'admin_contact_code_encrypted')) {
            $admin['admin_contact_code_encrypted'] = Crypt::encryptString('JuraJura');
        }

        DB::table('users')->insert($admin);
    });

    $this->info('Import hotov.');
    $this->line('Uživatelé byli smazáni a byl vytvořen admin Jura / JuraJura.');

    return 0;
})->purpose('Import game content, wipe player data, and create the production admin');
