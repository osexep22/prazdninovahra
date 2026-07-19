<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('badges')->where('slug', 'prvni-budova')->update([
            'name' => 'První komůrka',
            'description' => 'Kolonie postavila svou první místnost v mraveništi.',
            'icon_path' => '/assets/badges/prvni-budova.svg',
            'updated_at' => now(),
        ]);

        DB::table('badges')->where('slug', 'pet-budov')->update([
            'name' => 'Rozrůstající se domov',
            'description' => 'V mraveništi už stojí pět místností.',
            'icon_path' => '/assets/badges/pet-budov.svg',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('badges')->where('slug', 'prvni-budova')->update([
            'name' => 'První postavená budova',
            'description' => 'Odznáček za důležitý krok ve výpravě.',
            'icon_path' => '/assets/badges/first-room.png',
            'updated_at' => now(),
        ]);

        DB::table('badges')->where('slug', 'pet-budov')->update([
            'name' => '5 postavených budov',
            'description' => 'Odznáček za důležitý krok ve výpravě.',
            'icon_path' => '/assets/badges/five-rooms.png',
            'updated_at' => now(),
        ]);
    }
};
