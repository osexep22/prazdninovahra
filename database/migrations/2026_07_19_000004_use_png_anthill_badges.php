<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->badgeIcons() as $slug => $iconPath) {
            DB::table('badges')->where('slug', $slug)->update([
                'icon_path' => $iconPath,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach ($this->badgeIcons() as $slug => $iconPath) {
            DB::table('badges')->where('slug', $slug)->update([
                'icon_path' => preg_replace('/\.png$/', '.svg', $iconPath),
                'updated_at' => now(),
            ]);
        }
    }

    private function badgeIcons(): array
    {
        $icons = [
            'prvni-budova' => '/assets/badges/prvni-budova.png',
            'pet-budov' => '/assets/badges/pet-budov.png',
        ];

        foreach ([
            'hospoda',
            'hudebna',
            'krejci',
            'kuchyn',
            'malir',
            'obyvak',
            'porodnice',
            'remeslnik',
            'telocvicna',
            'zahradnik',
        ] as $buildingSlug) {
            foreach ([1, 2] as $taskOrder) {
                $badgeSlug = "budova-{$buildingSlug}-ukol-{$taskOrder}";
                $icons[$badgeSlug] = "/assets/badges/{$badgeSlug}.png";

                $topBadgeSlug = "top-10-{$badgeSlug}";
                $icons[$topBadgeSlug] = "/assets/badges/{$topBadgeSlug}.png";
            }
        }

        return $icons;
    }
};
