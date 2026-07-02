<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class EconomyService
{
    public function setting(string $key): int
    {
        $value = DB::table('economy_settings')->where('key', $key)->value('value');

        return (int) ($value ?? config('economy.settings.' . $key, 0));
    }

    public function allSettings(): array
    {
        $defaults = config('economy.settings', []);
        $stored = DB::table('economy_settings')->pluck('value', 'key')->all();

        return collect($defaults)
            ->mapWithKeys(fn ($value, $key) => [$key => (int) ($stored[$key] ?? $value)])
            ->all();
    }

    public function updateSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            if (! array_key_exists($key, config('economy.settings', []))) {
                continue;
            }

            DB::table('economy_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => max(0, (int) $value), 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function locationPrestigeAfterHint(object $location, int $userId): int
    {
        $prestige = (int) $location->reward_prestige;
        if (! $this->locationHintUsed((int) $location->id, $userId)) {
            return $prestige;
        }

        return (int) floor($prestige * $this->setting('hint.prestige_multiplier_percent') / 100);
    }

    public function locationHintUsed(int $locationId, int $userId): bool
    {
        return DB::table('user_hint_purchases')
            ->join('task_hints', 'task_hints.id', '=', 'user_hint_purchases.hint_id')
            ->join('location_tasks', 'location_tasks.id', '=', 'task_hints.location_task_id')
            ->where('user_hint_purchases.user_id', $userId)
            ->where('location_tasks.location_id', $locationId)
            ->exists();
    }

    public function ensureInitialAnthillRooms(int $userId): void
    {
        $initialRooms = $this->setting('anthill.initial_rooms');
        $this->grantRooms($userId, $initialRooms, 0);
    }

    public function anthillCapacity(int $userId): int
    {
        return (int) DB::table('user_building_slots')
            ->join('building_slots', 'building_slots.id', '=', 'user_building_slots.building_slot_id')
            ->where('user_building_slots.user_id', $userId)
            ->where('building_slots.slot_number', '<=', $this->setting('anthill.max_rooms'))
            ->count();
    }

    public function expansionTargets(): array
    {
        return [5, 7, 10];
    }

    public function availableExpansionTargets(int $capacity): array
    {
        return array_values(array_filter($this->expansionTargets(), fn (int $target) => $target > $capacity));
    }

    public function expansionCost(int $target): int
    {
        return $this->setting('anthill.expansion.' . $target . '.cost_resources');
    }

    public function grantRooms(int $userId, int $targetRooms, int $costPaid): void
    {
        $slotIds = DB::table('building_slots')
            ->where('slot_number', '<=', $targetRooms)
            ->orderBy('slot_number')
            ->pluck('id');

        foreach ($slotIds as $slotId) {
            DB::table('user_building_slots')->updateOrInsert(
                ['user_id' => $userId, 'building_slot_id' => $slotId],
                ['purchased_at' => now(), 'cost_paid' => $costPaid]
            );
        }
    }
}
