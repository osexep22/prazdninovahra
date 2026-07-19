<?php

namespace App\Http\Controllers;

use App\Services\EconomyService;
use App\Support\AnswerNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GameController extends Controller
{
    private const CUSTOMIZATION_DAILY_LIMIT = 1000;

    public function __construct(private readonly EconomyService $economy)
    {
    }

    public function home(): RedirectResponse
    {
        return redirect('/palouk');
    }

    public function meadow(): View
    {
        $user = Auth::user();

        return view('game.meadow', [
            'locations' => $this->locationsWithState($user->id),
            'announcement' => $this->nextAnnouncement(),
            'intro' => $user->intro_seen_at ? null : DB::table('game_contents')->where('key', 'intro_story')->first(),
            'showOnboarding' => $user->intro_seen_at && ! $user->meadow_onboarding_seen_at,
            'onboardingWelcome' => DB::table('game_contents')->where('key', 'meadow_onboarding_welcome')->first(),
            'anthillUnlocked' => $this->anthillUnlocked($user->id),
        ]);
    }

    public function markIntroSeen(): RedirectResponse
    {
        DB::table('users')->where('id', Auth::id())->update(['intro_seen_at' => now()]);

        return back();
    }

    public function markMeadowOnboardingSeen(): RedirectResponse
    {
        DB::table('users')->where('id', Auth::id())->update(['meadow_onboarding_seen_at' => now()]);

        return back();
    }

    public function markAnnouncement(int $id): RedirectResponse
    {
        DB::table('user_announcement_reads')->updateOrInsert(
            ['user_id' => Auth::id(), 'announcement_id' => $id],
            ['seen_at' => now()]
        );

        return back();
    }

    public function location(string $slug): View|RedirectResponse
    {
        $location = DB::table('locations')->where('slug', $slug)->first();
        abort_unless($location, 404);

        $states = collect($this->locationsWithState(Auth::id()))->keyBy('id');
        $state = $states[$location->id]->state ?? 'locked';

        if ($state === 'locked') {
            return redirect('/palouk')->with('error', 'Lokace je zatím zamčená.');
        }

        $tasks = DB::table('location_tasks')->where('location_id', $location->id)->orderBy('sort_order')->get();
        $progress = DB::table('user_task_progress')->where('user_id', Auth::id())->pluck('status', 'location_task_id');
        $hints = DB::table('task_hints')->whereIn('location_task_id', $tasks->pluck('id'))->orderBy('sort_order')->get()->groupBy('location_task_id');
        $purchased = DB::table('user_hint_purchases')->where('user_id', Auth::id())->pluck('hint_id')->all();
        $hintedTasks = DB::table('user_hint_purchases')
            ->join('task_hints', 'task_hints.id', '=', 'user_hint_purchases.hint_id')
            ->where('user_hint_purchases.user_id', Auth::id())
            ->whereIn('task_hints.location_task_id', $tasks->pluck('id'))
            ->pluck('task_hints.location_task_id')
            ->unique()
            ->all();
        $effectiveLocationPrestige = $this->economy->locationPrestigeAfterHint($location, Auth::id());

        return view('game.location', compact('location', 'state', 'tasks', 'progress', 'hints', 'purchased', 'hintedTasks', 'effectiveLocationPrestige'));
    }

    public function submitTask(Request $request, int $taskId): RedirectResponse
    {
        $task = DB::table('location_tasks')->find($taskId);
        abort_unless($task, 404);
        abort_unless($this->locationIsAvailableForUser((int) $task->location_id, Auth::id()), 403);

        $data = $request->validate(['answer' => ['nullable', 'string', 'max:2000']]);
        $status = $task->type === 'info' ? 'completed' : 'pending';

        if ($task->type === 'code') {
            $status = Hash::check(AnswerNormalizer::normalize($data['answer'] ?? ''), $task->answer_hash) ? 'completed' : 'pending';
        }

        DB::table('user_task_progress')->updateOrInsert(
            ['user_id' => Auth::id(), 'location_task_id' => $task->id],
            [
                'status' => $status,
                'submitted_answer' => $data['answer'] ?? null,
                'completed_at' => $status === 'completed' ? now() : null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        if ($status === 'completed') {
            $storyMessage = $this->syncLocationCompletion($task->location_id);
            return back()->with('success', $storyMessage ?: 'Úkol splněn.');
        }

        return back()->with('error', $task->type === 'manual' ? 'Odesláno adminovi ke kontrole.' : 'Kód zatím nesedí.');
    }

    public function buyHint(int $hintId): RedirectResponse
    {
        $hint = DB::table('task_hints')->find($hintId);
        abort_unless($hint, 404);
        $task = DB::table('location_tasks')->find($hint->location_task_id);
        abort_unless($task && $this->locationIsAvailableForUser((int) $task->location_id, Auth::id()), 403);

        if (DB::table('user_hint_purchases')->where(['user_id' => Auth::id(), 'hint_id' => $hintId])->exists()) {
            return back();
        }

        $user = Auth::user();

        DB::transaction(function () use ($hint, $user) {
            DB::table('user_hint_purchases')->insert([
                'user_id' => $user->id,
                'hint_id' => $hint->id,
                'purchased_at' => now(),
                'cost_paid' => 0,
            ]);
            $this->audit('hint_purchased', 'task_hint', $hint->id, $user->id);
        });

        return back()->with('success', 'Nápověda zobrazena. Maximální prestiž za celé stanoviště je snížená.');
    }

    public function anthill(): View
    {
        $this->ensureAnthillUnlocked();

        return $this->anthillView(Auth::id(), false);
    }

    public function friendAnthill(int $friendId): View
    {
        abort_unless($this->isFriend(Auth::id(), $friendId), 403);
        $this->ensureAnthillUnlocked();

        return $this->anthillView($friendId, true);
    }

    private function anthillView(int $userId, bool $readonly): View
    {
        $owner = DB::table('users')->find($userId);
        abort_unless($owner, 404);
        if (! $readonly) {
            $this->economy->ensureInitialAnthillRooms($userId);
        }
        $capacity = $this->economy->anthillCapacity($userId);
        $slotLayouts = $this->anthillSlotLayouts($capacity);
        $slots = DB::table('building_slots')
            ->where('slot_number', '<=', $capacity)
            ->orderBy('slot_number')
            ->get()
            ->map(function ($slot) use ($slotLayouts) {
                $layout = $slotLayouts[(string) $slot->slot_number] ?? null;
                $slot->layout_x = round((float) ($layout['x'] ?? $slot->layout_x), 3);
                $slot->layout_y = round((float) ($layout['y'] ?? $slot->layout_y), 3);
                $slot->layout_w = round((float) ($layout['w'] ?? 12), 3);
                $slot->layout_h = round((float) ($layout['h'] ?? 12), 3);

                return $slot;
            });
        $ownedSlots = DB::table('user_building_slots')->where('user_id', $userId)->pluck('building_slot_id')->all();
        $buildingHasTooltip = Schema::hasColumn('buildings', 'tooltip');
        $placedBuildings = DB::table('user_buildings')
            ->join('buildings', 'buildings.id', '=', 'user_buildings.building_id')
            ->leftJoin('user_building_customizations', function ($join) use ($userId) {
                $join->on('user_building_customizations.building_id', '=', 'buildings.id')
                    ->where('user_building_customizations.user_id', '=', $userId);
            })
            ->where('user_buildings.user_id', $userId)
            ->select(
                'user_buildings.*',
                'buildings.name',
                'buildings.slug',
                'buildings.svg_asset_path',
                $buildingHasTooltip ? 'buildings.tooltip' : DB::raw('NULL as tooltip'),
                'user_building_customizations.config_json as customization_json'
            )
            ->get();
        $unlocksByBuilding = $this->customizationUnlocksByBuilding($placedBuildings->pluck('building_id')->unique()->all());
        $accessByBuilding = $this->customizationAccessForBuildings($userId, $unlocksByBuilding);
        $placed = $placedBuildings
            ->map(function ($building) use ($unlocksByBuilding, $accessByBuilding) {
                $unlocks = $unlocksByBuilding->get($building->building_id, collect());
                $access = $accessByBuilding[$building->building_id] ?? [];
                $config = json_decode((string) ($building->customization_json ?? ''), true);
                if (! is_array($config)) {
                    $config = [];
                }
                $building->customization_json = json_encode($this->sanitizeCustomizationConfig($config, $access, $unlocks));

                return $building;
            })
            ->keyBy('building_slot_id');
        $buildingQuery = DB::table('buildings')->orderBy('min_colony_level')->orderBy('name');
        if (Schema::hasColumn('buildings', 'is_available')) {
            $buildingQuery->where('is_available', true);
        }
        $buildings = $buildingQuery->get();
        $activeBuildingIds = $buildings->pluck('id')->all();
        $ownedBuildingIds = DB::table('user_buildings')
            ->where('user_id', $userId)
            ->whereIn('building_id', $activeBuildingIds)
            ->pluck('building_id')
            ->all();
        $anthillVariant = match (true) {
            $capacity >= 10 => '/assets/game/anthill/anthill-10-rooms.png',
            $capacity >= 7 => '/assets/game/anthill/anthill-7-rooms.png',
            $capacity >= 5 => '/assets/game/anthill/anthill-5-rooms.png',
            default => '/assets/game/anthill/anthill-3-rooms.png',
        };
        $anthillScale = match (true) {
            $capacity >= 10 => 1.2,
            $capacity >= 7 => 1.0,
            $capacity >= 5 => 0.75,
            default => 0.5,
        };
        $availableExpansions = $readonly ? [] : $this->economy->availableExpansionTargets($capacity);
        $expansionCosts = collect($availableExpansions)
            ->mapWithKeys(fn (int $target) => [$target => $this->economy->expansionCost($target)])
            ->all();

        return view('game.anthill', compact('slots', 'ownedSlots', 'placed', 'buildings', 'ownedBuildingIds', 'readonly', 'owner', 'anthillVariant', 'anthillScale', 'capacity', 'availableExpansions', 'expansionCosts'));
    }

    private function customizationUnlocksByBuilding(array $buildingIds)
    {
        $buildingIds = array_values(array_filter(array_unique($buildingIds)));
        if (empty($buildingIds)) {
            return collect();
        }

        return DB::table('customization_unlocks')
            ->whereIn('building_id', $buildingIds)
            ->get()
            ->groupBy('building_id');
    }

    private function anthillSlotLayouts(int $capacity): array
    {
        $draft = null;

        if (Storage::disk('local')->exists('anthill-layout-draft.json')) {
            $draft = json_decode(Storage::disk('local')->get('anthill-layout-draft.json'), true);
        } elseif (file_exists(resource_path('data/anthill-layout.json'))) {
            $draft = json_decode(file_get_contents(resource_path('data/anthill-layout.json')), true);
        }

        if (! $draft) {
            return [];
        }

        $items = $draft['variants'][(string) $capacity]['items'] ?? $draft['items'] ?? [];

        return collect($items)
            ->keyBy(fn (array $item) => (string) ($item['slot'] ?? ''))
            ->all();
    }

    public function buySlot(int $slotId): RedirectResponse
    {
        $slot = DB::table('building_slots')->find($slotId);
        abort_unless($slot, 404);
        $this->ensureAnthillUnlocked();
        $user = Auth::user();

        if ($slot->required_colony_level > $user->colony_level) {
            return back()->with('error', 'Kolonie ještě nemá potřebný level.');
        }
        if (DB::table('user_building_slots')->where(['user_id' => $user->id, 'building_slot_id' => $slotId])->exists()) {
            return back();
        }
        if ($user->resources < $slot->cost_resources) {
            return back()->with('error', 'Nemáš dost surovin.');
        }

        $response = DB::transaction(function () use ($slot, $user) {
            $lockedUser = DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
            if (DB::table('user_building_slots')->where(['user_id' => $user->id, 'building_slot_id' => $slot->id])->exists()) {
                return back();
            }
            if ($lockedUser->resources < $slot->cost_resources) {
                return back()->with('error', 'Nemáš dost surovin.');
            }

            DB::table('users')->where('id', $user->id)->decrement('resources', $slot->cost_resources);
            DB::table('user_building_slots')->insert([
                'user_id' => $user->id,
                'building_slot_id' => $slot->id,
                'purchased_at' => now(),
                'cost_paid' => $slot->cost_resources,
            ]);
            $this->audit('slot_purchased', 'building_slot', $slot->id, $user->id);
        });
        if ($response) {
            return $response;
        }

        return back()->with('success', 'Slot koupen.');
    }

    public function buyAnthillExpansion(int $rooms): RedirectResponse
    {
        $this->ensureAnthillUnlocked();
        $user = Auth::user();
        $this->economy->ensureInitialAnthillRooms($user->id);

        $capacity = $this->economy->anthillCapacity($user->id);
        abort_unless(in_array($rooms, $this->economy->expansionTargets(), true), 404);
        if ($rooms <= $capacity) {
            return back();
        }
        if (! $this->economy->canBuyExpansion($capacity, $rooms)) {
            $nextTarget = $this->economy->nextExpansionTarget($capacity);
            $message = $nextTarget
                ? 'Mraveniště je potřeba rozšiřovat postupně. Teď můžeš rozšířit jen na ' . $nextTarget . ' komůrek.'
                : 'Mraveniště už má největší dostupné rozšíření.';

            return back()->with('error', $message);
        }

        $cost = $this->economy->expansionCost($rooms);
        if ($user->resources < $cost) {
            return back()->with('error', 'Nemáš dost surovin na rozšíření mraveniště.');
        }

        $response = DB::transaction(function () use ($user, $rooms, $cost) {
            $lockedUser = DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
            $capacity = $this->economy->anthillCapacity($user->id);
            if ($rooms <= $capacity) {
                return back();
            }
            if (! $this->economy->canBuyExpansion($capacity, $rooms)) {
                $nextTarget = $this->economy->nextExpansionTarget($capacity);
                $message = $nextTarget
                    ? 'Mraveniště je potřeba rozšiřovat postupně. Teď můžeš rozšířit jen na ' . $nextTarget . ' komůrek.'
                    : 'Mraveniště už má největší dostupné rozšíření.';

                return back()->with('error', $message);
            }
            if ($lockedUser->resources < $cost) {
                return back()->with('error', 'Nemáš dost surovin na rozšíření mraveniště.');
            }

            DB::table('users')->where('id', $user->id)->decrement('resources', $cost);
            $this->economy->grantRooms($user->id, $rooms, $cost);
            $this->audit('anthill_expansion_purchased', 'anthill_expansion', $rooms, $user->id, [
                'rooms' => $rooms,
                'resources' => -$cost,
            ]);
        });
        if ($response) {
            return $response;
        }

        return back()->with('success', 'Mraveniště rozšířeno na ' . $rooms . ' komůrek.');
    }

    public function build(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slot_id' => ['required', 'integer'],
            'building_id' => ['required', 'integer'],
        ]);
        $user = Auth::user();
        $this->ensureAnthillUnlocked();
        $this->economy->ensureInitialAnthillRooms($user->id);
        $building = DB::table('buildings')->find($data['building_id']);
        abort_unless($building, 404);

        if (property_exists($building, 'is_available') && ! $building->is_available) {
            return back()->with('error', 'Tahle budova zatím není připravená ke stavění.');
        }
        if ($building->min_colony_level > $user->colony_level) {
            return back()->with('error', 'Budovu zatím nejde postavit. Potřebuješ úroveň kolonie ' . $building->min_colony_level . ', ale teď máš úroveň ' . $user->colony_level . '.');
        }
        if ($user->resources < $building->cost_resources) {
            return back()->with('error', 'Budovu zatím nejde postavit. Stojí ' . $building->cost_resources . ' surovin, ale teď máš jen ' . $user->resources . '.');
        }
        if (DB::table('user_buildings')->where(['user_id' => $user->id, 'building_id' => $building->id])->exists()) {
            return back()->with('error', 'Tento typ budovy už v mraveništi máš. Každý typ budovy lze postavit jen jednou.');
        }
        if (! DB::table('user_building_slots')->where(['user_id' => $user->id, 'building_slot_id' => $data['slot_id']])->exists()) {
            return back()->with('error', 'Tahle komůrka ještě není koupená. Nejdřív ji otevři nebo rozšiř mraveniště.');
        }
        if (DB::table('user_buildings')->where(['user_id' => $user->id, 'building_slot_id' => $data['slot_id']])->exists()) {
            return back()->with('error', 'Tahle komůrka už je obsazená jinou budovou.');
        }

        $response = DB::transaction(function () use ($user, $building, $data) {
            $lockedUser = DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
            if ($lockedUser->resources < $building->cost_resources) {
                return back()->with('error', 'Budovu zatím nejde postavit. Stojí ' . $building->cost_resources . ' surovin, ale teď máš jen ' . $lockedUser->resources . '.');
            }
            if (DB::table('user_buildings')->where(['user_id' => $user->id, 'building_id' => $building->id])->exists()) {
                return back()->with('error', 'Tento typ budovy už v mraveništi máš. Každý typ budovy lze postavit jen jednou.');
            }
            if (! DB::table('user_building_slots')->where(['user_id' => $user->id, 'building_slot_id' => $data['slot_id']])->exists()) {
                return back()->with('error', 'Tahle komůrka ještě není koupená. Nejdřív ji otevři nebo rozšiř mraveniště.');
            }
            if (DB::table('user_buildings')->where(['user_id' => $user->id, 'building_slot_id' => $data['slot_id']])->exists()) {
                return back()->with('error', 'Tahle komůrka už je obsazená jinou budovou.');
            }

            DB::table('users')->where('id', $user->id)->decrement('resources', $building->cost_resources);
            DB::table('user_buildings')->insert([
                'user_id' => $user->id,
                'building_id' => $building->id,
                'building_slot_id' => $data['slot_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->audit('building_built', 'building', $building->id, $user->id);
        });
        if ($response) {
            return $response;
        }
        $buildingCount = DB::table('user_buildings')->where('user_id', $user->id)->count();
        if ($buildingCount >= 1) {
            $this->awardBadge('prvni-budova', $user->id, 'building', $building->id);
        }
        if ($buildingCount >= 5) {
            $this->awardBadge('pet-budov', $user->id, 'building', $building->id);
        }

        return back()->with('success', 'Budova postavena.');
    }

    public function building(string $slug): View
    {
        $building = DB::table('buildings')->where('slug', $slug)->first();
        abort_unless($building, 404);
        $this->ensureAnthillUnlocked();
        abort_unless(DB::table('user_buildings')->where(['user_id' => Auth::id(), 'building_id' => $building->id])->exists(), 403);

        $tasks = DB::table('building_tasks')
            ->where('building_id', $building->id)
            ->when(Schema::hasColumn('building_tasks', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->get();
        $progress = DB::table('user_building_task_progress')->where('user_id', Auth::id())->pluck('status', 'building_task_id');
        $completed = $progress->filter(fn ($status) => $status === 'completed')->count();
        $unlocks = DB::table('customization_unlocks')->where('building_id', $building->id)->get();
        $customizationAccess = $this->customizationAccessForBuilding(Auth::id(), $building->id, $unlocks);
        $userUnlocks = array_keys($customizationAccess);
        $basicColorPalette = $this->basicColorPalette();
        $customization = DB::table('user_building_customizations')->where(['user_id' => Auth::id(), 'building_id' => $building->id])->first();
        $customizationConfig = $customization ? json_decode((string) $customization->config_json, true) : [];
        if (! is_array($customizationConfig)) {
            $customizationConfig = [];
        }
        $customizationConfig = $this->sanitizeCustomizationConfig($customizationConfig, $customizationAccess, $unlocks);

        return view('game.building', compact('building', 'tasks', 'progress', 'completed', 'unlocks', 'userUnlocks', 'customizationAccess', 'basicColorPalette', 'customization', 'customizationConfig'));
    }

    public function submitBuildingTask(Request $request, int $taskId): RedirectResponse
    {
        $task = DB::table('building_tasks')->find($taskId);
        abort_unless($task, 404);
        abort_unless(DB::table('user_buildings')->where(['user_id' => Auth::id(), 'building_id' => $task->building_id])->exists(), 403);
        $data = $request->validate(['answer' => ['required', 'string', 'max:2000']]);

        $alreadyCompleted = DB::table('user_building_task_progress')
            ->where(['user_id' => Auth::id(), 'building_task_id' => $task->id, 'status' => 'completed'])
            ->exists();
        if ($alreadyCompleted) {
            return back()->with('success', 'Úkol už máš splněný.');
        }

        if (! Hash::check(AnswerNormalizer::normalize($data['answer']), $task->answer_hash)) {
            return back()->with('error', 'Kód zatím nesedí.');
        }

        DB::transaction(function () use ($task, $data) {
            DB::table('user_building_task_progress')->updateOrInsert(
                ['user_id' => Auth::id(), 'building_task_id' => $task->id],
                ['status' => 'completed', 'submitted_answer' => $data['answer'], 'completed_at' => now(), 'created_at' => now(), 'updated_at' => now()]
            );
            $unlock = DB::table('customization_unlocks')->where(['building_id' => $task->building_id, 'key' => $task->unlock_key])->first();
            if ($unlock) {
                DB::table('user_customization_unlocks')->updateOrInsert(
                    ['user_id' => Auth::id(), 'customization_unlock_id' => $unlock->id],
                    ['unlocked_at' => now()]
                );
            }
            if (Schema::hasTable('building_task_customization_unlocks')) {
                $unlockIds = DB::table('building_task_customization_unlocks')
                    ->where('building_task_id', $task->id)
                    ->pluck('customization_unlock_id');
                foreach ($unlockIds as $unlockId) {
                    DB::table('user_customization_unlocks')->updateOrInsert(
                        ['user_id' => Auth::id(), 'customization_unlock_id' => $unlockId],
                        ['unlocked_at' => now()]
                    );
                }
            }
            $this->rewardUser($task->reward_prestige, $task->reward_resources, 0, 'building_task_completed', 'building_task', $task->id);
        });
        $this->awardBuildingTaskBadges($task);

        return back()->with('success', 'Speciální úkol splněn.');
    }

    public function saveCustomization(Request $request, int $buildingId): RedirectResponse
    {
        $user = Auth::user();
        $this->ensureAnthillUnlocked();
        abort_unless(DB::table('user_buildings')->where(['user_id' => $user->id, 'building_id' => $buildingId])->exists(), 403);
        $changesToday = DB::table('audit_logs')
            ->where('actor_user_id', $user->id)
            ->where('action', 'customization_changed')
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        if ($changesToday >= self::CUSTOMIZATION_DAILY_LIMIT) {
            return back()->with('error', 'Vzhled lze měnit maximálně ' . self::CUSTOMIZATION_DAILY_LIMIT . 'x denně.');
        }

        $data = $request->validate([
            'colors' => ['array'],
            'patterns' => ['array'],
            'variants' => ['array'],
        ]);
        $unlocks = DB::table('customization_unlocks')->where('building_id', $buildingId)->get();
        $customizationAccess = $this->customizationAccessForBuilding($user->id, $buildingId, $unlocks);
        $userUnlocks = array_keys($customizationAccess);
        $filtered = ['colors' => [], 'patterns' => [], 'variants' => []];
        foreach ($unlocks->whereIn('id', $userUnlocks) as $unlock) {
            if ($unlock->type === 'color' && isset($data['colors'][$unlock->key])) {
                $value = (string) $data['colors'][$unlock->key];
                $access = $customizationAccess[$unlock->id] ?? ['mode' => 'full'];
                $allowedBasicColors = collect($access['palette'] ?? [])->pluck('value')->all();
                if (($access['mode'] ?? 'full') === 'basic' && in_array($value, $allowedBasicColors, true)) {
                    $filtered['colors'][$unlock->key] = $value;
                } elseif (($access['mode'] ?? 'full') === 'full' && preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                    $filtered['colors'][$unlock->key] = $value;
                }
            }
            if ($unlock->type === 'variant' && isset($data['variants'][$unlock->key])) {
                $options = json_decode((string) $unlock->options, true);
                $value = (string) $data['variants'][$unlock->key];
                $access = $customizationAccess[$unlock->id] ?? ['allowed_values' => []];
                $allowedValues = collect(is_array($options) ? $options : [])
                    ->map(fn ($option) => is_array($option) ? ($option['value'] ?? null) : $option)
                    ->filter()
                    ->all();
                if (! empty($access['allowed_values'])) {
                    $allowedValues = array_values(array_intersect($allowedValues, $access['allowed_values']));
                }
                if (in_array($value, $allowedValues, true)) {
                    $filtered['variants'][$unlock->key] = $value;
                }
            }
            if ($unlock->type === 'pattern' && isset($data['patterns'][$unlock->key])) {
                $options = json_decode((string) $unlock->options, true);
                $value = (string) $data['patterns'][$unlock->key];
                $access = $customizationAccess[$unlock->id] ?? ['allowed_values' => []];
                $allowedValues = collect(is_array($options) ? $options : [])
                    ->map(fn ($option) => is_array($option) ? ($option['value'] ?? null) : $option)
                    ->filter()
                    ->all();
                if (! empty($access['allowed_values'])) {
                    $allowedValues = array_values(array_intersect($allowedValues, $access['allowed_values']));
                }
                if (in_array($value, $allowedValues, true)) {
                    $filtered['patterns'][$unlock->key] = $value;
                }
            }
        }
        $filtered = $this->sanitizeCustomizationDependencies($filtered);

        DB::table('user_building_customizations')->updateOrInsert(
            ['user_id' => $user->id, 'building_id' => $buildingId],
            ['config_json' => json_encode($filtered), 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('users')->where('id', $user->id)->update(['last_customization_change_at' => now()]);
        $this->audit('customization_changed', 'building', $buildingId, $user->id);

        return back()->with('success', 'Vzhled uložen.');
    }

    private function customizationAccessForBuilding(int $userId, int $buildingId, $unlocks): array
    {
        $this->syncCustomizationUnlocksFromCompletedTasks($userId);

        return $this->buildCustomizationAccessForUnlocks($userId, $buildingId, $unlocks);
    }

    private function customizationAccessForBuildings(int $userId, $unlocksByBuilding): array
    {
        $this->syncCustomizationUnlocksFromCompletedTasks($userId);

        $access = [];
        foreach ($unlocksByBuilding as $buildingId => $unlocks) {
            $access[$buildingId] = $this->buildCustomizationAccessForUnlocks($userId, (int) $buildingId, $unlocks);
        }

        return $access;
    }

    private function buildCustomizationAccessForUnlocks(int $userId, int $buildingId, $unlocks): array
    {
        $unlocksById = $unlocks->keyBy('id');
        $access = [];

        if (Schema::hasTable('building_task_customization_unlocks')) {
            $completedLinks = DB::table('user_building_task_progress as progress')
                ->join('building_task_customization_unlocks as links', 'links.building_task_id', '=', 'progress.building_task_id')
                ->join('customization_unlocks as unlocks', 'unlocks.id', '=', 'links.customization_unlock_id')
                ->join('building_tasks as tasks', 'tasks.id', '=', 'progress.building_task_id')
                ->join('buildings as source_buildings', 'source_buildings.id', '=', 'tasks.building_id')
                ->where('progress.user_id', $userId)
                ->where('progress.status', 'completed')
                ->where('unlocks.building_id', $buildingId)
                ->select('links.customization_unlock_id', 'source_buildings.slug as source_slug', 'tasks.sort_order')
                ->get();

            foreach ($completedLinks as $link) {
                $unlock = $unlocksById[$link->customization_unlock_id] ?? null;
                if (! $unlock) {
                    continue;
                }

                $mode = $this->customizationUnlockMode($unlock, $link->source_slug, (int) $link->sort_order);
                if (! $mode) {
                    continue;
                }
                if (($access[$unlock->id]['mode'] ?? null) !== 'full') {
                    $access[$unlock->id] = [
                        'mode' => $mode,
                        'palette' => $mode === 'basic' ? $this->basicColorPalette() : [],
                        'allowed_values' => $this->allowedOptionValues($unlock, $mode),
                    ];
                }
            }
        }

        $directUnlockQuery = DB::table('user_customization_unlocks')
            ->where('user_id', $userId)
            ->whereIn('customization_unlock_id', $unlocks->pluck('id'));

        if (Schema::hasTable('building_task_customization_unlocks')) {
            $taskLinkedUnlockIds = DB::table('building_task_customization_unlocks')
                ->whereIn('customization_unlock_id', $unlocks->pluck('id'))
                ->pluck('customization_unlock_id')
                ->unique()
                ->all();
            if (! empty($taskLinkedUnlockIds)) {
                $directUnlockQuery->whereNotIn('customization_unlock_id', $taskLinkedUnlockIds);
            }
        }

        $directUnlocks = $directUnlockQuery->pluck('customization_unlock_id');

        foreach ($directUnlocks as $unlockId) {
            if (! isset($access[$unlockId])) {
                $unlock = $unlocksById[$unlockId] ?? null;
                $access[$unlockId] = [
                    'mode' => 'full',
                    'palette' => [],
                    'allowed_values' => $unlock ? $this->allowedOptionValues($unlock, 'full') : [],
                ];
            }
        }

        $this->addAlwaysAvailableCustomizationAccess($buildingId, $unlocks, $access);

        return $access;
    }

    private function syncCustomizationUnlocksFromCompletedTasks(int $userId): void
    {
        if (! Schema::hasTable('building_task_customization_unlocks')) {
            return;
        }

        $unlockIds = DB::table('user_building_task_progress as progress')
            ->join('building_task_customization_unlocks as links', 'links.building_task_id', '=', 'progress.building_task_id')
            ->where('progress.user_id', $userId)
            ->where('progress.status', 'completed')
            ->pluck('links.customization_unlock_id')
            ->unique();

        foreach ($unlockIds as $unlockId) {
            DB::table('user_customization_unlocks')->updateOrInsert(
                ['user_id' => $userId, 'customization_unlock_id' => $unlockId],
                ['unlocked_at' => now()]
            );
        }
    }

    private function addAlwaysAvailableCustomizationAccess(int $buildingId, $unlocks, array &$access): void
    {
        $buildingSlug = DB::table('buildings')->where('id', $buildingId)->value('slug');
        if ($buildingSlug !== 'hospoda') {
            return;
        }

        $pivoUnlock = $unlocks->first(fn ($unlock) => $unlock->type === 'variant' && $unlock->key === '3__pivo');
        if (! $pivoUnlock || isset($access[$pivoUnlock->id])) {
            return;
        }

        $access[$pivoUnlock->id] = [
            'mode' => 'full',
            'palette' => [],
            'allowed_values' => $this->allowedOptionValues($pivoUnlock, 'full'),
        ];
    }

    private function customizationUnlockMode(object $unlock, string $sourceSlug, int $sourceTaskOrder): ?string
    {
        if ($unlock->type === 'color' && in_array($unlock->key, ['4__stena', '4__stena_komory'], true) && $sourceSlug === 'malir') {
            return $sourceTaskOrder >= 2 ? 'full' : 'basic';
        }

        if ($unlock->type === 'color' && $unlock->key === '2__koberec' && $sourceSlug === 'krejci') {
            return $sourceTaskOrder >= 2 ? 'full' : 'basic';
        }

        if ($unlock->type === 'variant' && $unlock->key === '6__lustr' && $sourceSlug === 'remeslnik') {
            return $sourceTaskOrder >= 2 ? 'full' : 'basic_lustr';
        }

        if ($unlock->type === 'variant' && $unlock->key === '3__jidlo' && $sourceSlug === 'kuchyn') {
            return $sourceTaskOrder >= 2 ? 'full' : 'basic_food';
        }

        if ($unlock->type === 'variant' && $unlock->key === '3__voda' && $sourceSlug === 'kuchyn') {
            return null;
        }

        if ($unlock->type === 'pattern' && $unlock->key === '5__vzor-na-zdi' && $sourceSlug === 'obyvak') {
            return $sourceTaskOrder >= 2 ? 'full' : 'basic_pattern';
        }

        return 'full';
    }

    private function allowedOptionValues(object $unlock, string $mode): array
    {
        if (! in_array($unlock->type, ['variant', 'pattern'], true)) {
            return [];
        }

        $options = json_decode((string) $unlock->options, true);
        $values = collect(is_array($options) ? $options : [])
            ->map(fn ($option) => is_array($option) ? ($option['value'] ?? null) : $option)
            ->filter()
            ->values()
            ->all();

        if ($unlock->key === '6__lustr' && $mode === 'basic_lustr') {
            return ['__off', 'edit_variant__6__lustr__1', 'edit_variant__6__lustr__3'];
        }

        if ($unlock->key === '3__jidlo' && $mode === 'basic_food') {
            return array_values(array_intersect($values, ['edit_variant__3__ovoce']));
        }

        if ($unlock->key === '5__vzor-na-zdi' && $mode === 'basic_pattern') {
            $off = in_array('__off', $values, true) ? ['__off'] : [];
            $editable = array_values(array_filter($values, fn ($value) => $value !== '__off'));
            return array_merge($off, array_slice($editable, 0, (int) ceil(count($editable) / 2)));
        }

        return $values;
    }

    private function sanitizeCustomizationConfig(array $config, array $customizationAccess, $unlocks): array
    {
        $clean = ['colors' => [], 'patterns' => [], 'variants' => []];

        foreach ($unlocks as $unlock) {
            $access = $customizationAccess[$unlock->id] ?? null;
            if (! $access) {
                continue;
            }

            if ($unlock->type === 'color') {
                $value = data_get($config, 'colors.' . $unlock->key);
                if (! is_string($value)) {
                    continue;
                }

                $paletteValues = collect($access['palette'] ?? [])->pluck('value')->all();
                if (($access['mode'] ?? 'full') === 'basic' && in_array($value, $paletteValues, true)) {
                    $clean['colors'][$unlock->key] = $value;
                } elseif (($access['mode'] ?? 'full') === 'full' && preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                    $clean['colors'][$unlock->key] = $value;
                }
            }

            if (in_array($unlock->type, ['variant', 'pattern'], true)) {
                $bucket = $unlock->type === 'variant' ? 'variants' : 'patterns';
                $value = data_get($config, $bucket . '.' . $unlock->key);
                if (! is_string($value)) {
                    continue;
                }

                $allowedValues = $access['allowed_values'] ?? $this->allowedOptionValues($unlock, $access['mode'] ?? 'full');
                if (in_array($value, $allowedValues, true)) {
                    $clean[$bucket][$unlock->key] = $value;
                }
            }
        }

        return $this->sanitizeCustomizationDependencies($clean);
    }

    private function sanitizeCustomizationDependencies(array $config): array
    {
        $selectedLustr = data_get($config, 'variants.6__lustr');
        foreach (array_keys($config['colors'] ?? []) as $key) {
            if (preg_match('/^6__lustr__(\d+)$/', (string) $key, $match)) {
                $requiredVariant = 'edit_variant__6__lustr__' . $match[1];
                if ($selectedLustr !== $requiredVariant) {
                    unset($config['colors'][$key]);
                }
            }
        }

        return $config;
    }

    private function basicColorPalette(): array
    {
        return [
            ['value' => '#8b5a2b', 'label' => 'Hnědá'],
            ['value' => '#6f9f4a', 'label' => 'Mechová zelená'],
            ['value' => '#f0c64a', 'label' => 'Sluneční žlutá'],
            ['value' => '#4ca3d9', 'label' => 'Potůčková modrá'],
        ];
    }

    public function leaderboard(): View
    {
        $friendIds = DB::table('friendships')->where('user_id', Auth::id())->pluck('friend_id')->all();
        $playersQuery = DB::table('users')
            ->where('role', 'player')
            ->where('status', 'active');
        if (Schema::hasColumn('users', 'is_test')) {
            $playersQuery->where('is_test', false);
        }
        $players = $playersQuery->orderByDesc('prestige')->get();
        $ranked = $players->values()->map(function ($player, $index) {
            $player->rank = $index + 1;
            return $player;
        });
        $current = $ranked->firstWhere('id', Auth::id());
        $visible = $ranked->where('rank', '<=', 3);
        if ($current) {
            $around = $ranked->whereBetween('rank', [max(1, $current->rank - 1), $current->rank + 1]);
            $visible = $visible->merge($around)->unique('id')->sortBy('rank');
        }
        $visible = $visible->merge($ranked->whereIn('id', $friendIds))->unique('id')->sortBy('rank');

        return view('game.leaderboard', compact('visible', 'current', 'friendIds'));
    }

    public function messages(): View
    {
        $friends = DB::table('friendships')
            ->join('users', 'users.id', '=', 'friendships.friend_id')
            ->where('friendships.user_id', Auth::id())
            ->select('users.id', 'users.display_name')
            ->orderBy('users.display_name')
            ->get();
        $messages = DB::table('messages')
            ->leftJoin('users as starters', 'starters.id', '=', 'messages.user_id')
            ->leftJoin('users as recipients', 'recipients.id', '=', 'messages.recipient_user_id')
            ->where(function ($query) {
                $query->where('messages.user_id', Auth::id())
                    ->orWhere('messages.recipient_user_id', Auth::id());
            })
            ->select('messages.*', 'starters.display_name as starter_name', 'recipients.display_name as recipient_name')
            ->latest('messages.updated_at')
            ->get();
        $activeKey = request('thread', request('recipient'));
        if (! $activeKey && $messages->isNotEmpty()) {
            $first = $messages->first();
            $activeKey = $first->thread_type === 'admin'
                ? 'admin'
                : ((int) $first->user_id === Auth::id() ? (string) $first->recipient_user_id : (string) $first->user_id);
        }
        $activeKey = $activeKey ?: 'admin';
        $activeMessage = $this->messageForThreadKey($messages, (string) $activeKey);

        if ($activeMessage) {
            DB::table('message_reads')->updateOrInsert(
                ['message_id' => $activeMessage->id, 'user_id' => Auth::id()],
                ['read_at' => now()]
            );
        }

        $entries = DB::table('message_entries')
            ->whereIn('message_id', $messages->pluck('id'))
            ->orderBy('created_at')
            ->get()
            ->groupBy('message_id');
        $reads = DB::table('message_reads')->where('user_id', Auth::id())->pluck('read_at', 'message_id');

        return view('game.messages', compact('messages', 'entries', 'friends', 'reads', 'activeKey'));
    }

    public function sendMessage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient' => ['required', 'string', 'max:40'],
            'body' => ['required', 'string', 'max:4000'],
        ]);
        $message = $this->findOrCreateThread($data['recipient']);

        DB::transaction(function () use ($data, $message) {
            DB::table('message_entries')->insert([
                'message_id' => $message->id,
                'user_id' => Auth::id(),
                'sender_role' => 'player',
                'body' => $data['body'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('messages')->where('id', $message->id)->update([
                'body' => $data['body'],
                'status' => 'new',
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Zpráva odeslána.');
    }

    public function replyMessage(Request $request, int $id): RedirectResponse
    {
        $message = DB::table('messages')
            ->where('id', $id)
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                    ->orWhere('recipient_user_id', Auth::id());
            })
            ->first();
        abort_unless($message, 404);
        $data = $request->validate(['body' => ['required', 'string', 'max:4000']]);

        DB::table('message_entries')->insert([
            'message_id' => $message->id,
            'user_id' => Auth::id(),
            'sender_role' => 'player',
            'body' => $data['body'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('messages')->where('id', $message->id)->update(['status' => 'new', 'updated_at' => now()]);

        return back()->with('success', 'Zpráva odeslána.');
    }

    public function friends(): View
    {
        $user = Auth::user();
        $friends = DB::table('friendships')
            ->join('users', 'users.id', '=', 'friendships.friend_id')
            ->where('friendships.user_id', $user->id)
            ->select('users.id', 'users.display_name', 'users.username', 'users.prestige', 'users.colony_level', 'users.status')
            ->orderBy('users.display_name')
            ->get();

        return view('game.friends', compact('user', 'friends'));
    }

    public function addFriend(Request $request): RedirectResponse
    {
        $data = $request->validate(['friend_code' => ['required', 'string', 'regex:/^[A-Za-z][0-9][A-Za-z][0-9][A-Za-z][0-9]$/']], [
            'friend_code.regex' => 'Kód přítele má tvar například A4P8K2.',
        ]);
        $friendCode = strtoupper(trim($data['friend_code']));
        $friend = DB::table('users')->where('friend_code', $friendCode)->first();

        if (! $friend) {
            return back()->with('error', 'Takový kód jsem nenašel.');
        }
        if ((int) $friend->id === Auth::id()) {
            return back()->with('error', 'Sám sebe přidávat nemusíš.');
        }

        DB::table('friendships')->updateOrInsert(
            ['user_id' => Auth::id(), 'friend_id' => $friend->id],
            ['created_at' => now(), 'updated_at' => now()]
        );
        DB::table('friendships')->updateOrInsert(
            ['user_id' => $friend->id, 'friend_id' => Auth::id()],
            ['created_at' => now(), 'updated_at' => now()]
        );

        return back()->with('success', 'Přidán přítel ' . $friend->display_name . '.');
    }

    private function locationsWithState(int $userId): array
    {
        $completedIds = DB::table('user_location_progress')->where(['user_id' => $userId, 'status' => 'completed'])->pluck('location_id')->all();
        $requirements = DB::table('location_requirements')->get()->groupBy('location_id');

        return DB::table('locations')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function ($location) use ($completedIds, $requirements) {
            $required = $requirements[$location->id] ?? collect();
            $missing = $required->pluck('required_location_id')->diff($completedIds)->isNotEmpty();
            $location->state = in_array($location->id, $completedIds, true) ? 'completed' : ($missing ? 'locked' : 'available');
            $location->requirements = $required;
            return $location;
        })->all();
    }

    private function locationIsAvailableForUser(int $locationId, int $userId): bool
    {
        $state = collect($this->locationsWithState($userId))->firstWhere('id', $locationId);

        return $state && $state->state !== 'locked';
    }

    private function syncLocationCompletion(int $locationId): ?string
    {
        $requiredTaskIds = DB::table('location_tasks')->where(['location_id' => $locationId, 'required_for_completion' => true])->pluck('id');
        $done = DB::table('user_task_progress')->where('user_id', Auth::id())->whereIn('location_task_id', $requiredTaskIds)->where('status', 'completed')->count();

        if ($requiredTaskIds->count() > 0 && $done === $requiredTaskIds->count()) {
            $location = DB::table('locations')->find($locationId);
            $already = DB::table('user_location_progress')->where(['user_id' => Auth::id(), 'location_id' => $locationId, 'status' => 'completed'])->exists();
            DB::table('user_location_progress')->updateOrInsert(
                ['user_id' => Auth::id(), 'location_id' => $locationId],
                ['status' => 'completed', 'completed_at' => now()]
            );
            if (! $already) {
                $rewardPrestige = $this->economy->locationPrestigeAfterHint($location, Auth::id());
                $this->rewardUser($rewardPrestige, $location->reward_resources, $location->reward_colony_level, 'location_completed', 'location', $locationId);
                $this->awardBadge('lokace-' . $location->slug, Auth::id(), 'location', $locationId);
                $completedCountQuery = DB::table('user_location_progress')
                    ->join('users', 'users.id', '=', 'user_location_progress.user_id')
                    ->where('user_location_progress.location_id', $locationId)
                    ->where('user_location_progress.status', 'completed');
                if (Schema::hasColumn('users', 'is_test')) {
                    $completedCountQuery->where('users.is_test', false);
                }
                $completedCount = $completedCountQuery->count();
                if ($completedCount <= 10) {
                    $this->awardBadge('top-10-' . $location->slug, Auth::id(), 'location', $locationId);
                }

                if ($location->slug === 'ukol-2') {
                    $this->economy->ensureInitialAnthillRooms(Auth::id());

                    return 'Mravenečci si konečně postavili první opravdový domov. Od téhle chvíle můžeš navštívit své Mraveniště a začít stavět nové místnosti.';
                }
            }
        }

        return null;
    }
    private function rewardUser(int $prestige, int $resources, int $level, string $action, string $entityType, int $entityId): void
    {
        DB::table('users')->where('id', Auth::id())->increment('prestige', $prestige);
        DB::table('users')->where('id', Auth::id())->increment('resources', $resources);
        if ($level > 0) {
            DB::table('users')->where('id', Auth::id())->increment('colony_level', $level);
        }
        $this->audit($action, $entityType, $entityId, Auth::id(), compact('prestige', 'resources', 'level'));
        $this->syncPrestigeBadges(Auth::id());
    }

    private function taskHintUsed(int $taskId): bool
    {
        return DB::table('user_hint_purchases')
            ->join('task_hints', 'task_hints.id', '=', 'user_hint_purchases.hint_id')
            ->where('user_hint_purchases.user_id', Auth::id())
            ->where('task_hints.location_task_id', $taskId)
            ->exists();
    }

    private function audit(string $action, ?string $entityType, ?int $entityId, ?int $targetUserId = null, ?array $newValue = null): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => Auth::id(),
            'target_user_id' => $targetUserId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'new_value' => $newValue ? json_encode($newValue) : null,
            'created_at' => now(),
        ]);
    }

    private function awardBadge(string $slug, int $userId, ?string $entityType = null, ?int $entityId = null): void
    {
        if ($this->isTestUser($userId)) {
            return;
        }

        $badge = DB::table('badges')->where('slug', $slug)->first();
        if (! $badge) {
            return;
        }
        $alreadyAwarded = DB::table('user_badges')->where(['user_id' => $userId, 'badge_id' => $badge->id])->exists();
        if ($alreadyAwarded) {
            return;
        }

        DB::table('user_badges')->insert([
            'user_id' => $userId,
            'badge_id' => $badge->id,
            'awarded_at' => now(),
            'awarded_for_entity_type' => $entityType,
            'awarded_for_entity_id' => $entityId,
        ]);
        if ($badge->prestige_bonus > 0) {
            DB::table('users')->where('id', $userId)->increment('prestige', $badge->prestige_bonus);
        }
        $this->audit('badge_awarded_auto', 'badge', $badge->id, $userId, [
            'slug' => $slug,
            'prestige_bonus' => $badge->prestige_bonus,
        ]);
    }

    private function awardBuildingTaskBadges(object $task): void
    {
        $building = DB::table('buildings')->find($task->building_id);
        if (! $building) {
            return;
        }

        $slug = 'budova-' . $building->slug . '-ukol-' . (int) $task->sort_order;
        $this->awardBadge($slug, Auth::id(), 'building_task', $task->id);

        $completedCountQuery = DB::table('user_building_task_progress')
            ->join('users', 'users.id', '=', 'user_building_task_progress.user_id')
            ->where('user_building_task_progress.building_task_id', $task->id)
            ->where('user_building_task_progress.status', 'completed');
        if (Schema::hasColumn('users', 'is_test')) {
            $completedCountQuery->where('users.is_test', false);
        }

        if ($completedCountQuery->count() <= 10) {
            $this->awardBadge('top-10-' . $slug, Auth::id(), 'building_task', $task->id);
        }
    }

    private function isTestUser(int $userId): bool
    {
        if (! Schema::hasColumn('users', 'is_test')) {
            return false;
        }

        return (bool) DB::table('users')->where('id', $userId)->value('is_test');
    }

    private function findOrCreateThread(string $recipient): object
    {
        if ($recipient === 'admin') {
            $message = DB::table('messages')->where([
                'user_id' => Auth::id(),
                'recipient_user_id' => null,
                'thread_type' => 'admin',
            ])->first();

            if ($message) {
                return $message;
            }

            $id = DB::table('messages')->insertGetId([
                'user_id' => Auth::id(),
                'recipient_user_id' => null,
                'thread_type' => 'admin',
                'subject' => 'Admini',
                'body' => '',
                'status' => 'new',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return DB::table('messages')->find($id);
        }

        $friendId = (int) $recipient;
        abort_unless($this->isFriend(Auth::id(), $friendId), 403);
        $a = min(Auth::id(), $friendId);
        $b = max(Auth::id(), $friendId);
        $message = DB::table('messages')->where([
            'user_id' => $a,
            'recipient_user_id' => $b,
            'thread_type' => 'direct',
        ])->first();

        if ($message) {
            return $message;
        }

        $id = DB::table('messages')->insertGetId([
            'user_id' => $a,
            'recipient_user_id' => $b,
            'thread_type' => 'direct',
                'subject' => 'Soukromá zpráva',
            'body' => '',
            'status' => 'new',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('messages')->find($id);
    }

    private function isFriend(int $userId, int $friendId): bool
    {
        return DB::table('friendships')->where(['user_id' => $userId, 'friend_id' => $friendId])->exists();
    }

    private function anthillUnlocked(int $userId): bool
    {
        return DB::table('user_location_progress')
            ->join('locations', 'locations.id', '=', 'user_location_progress.location_id')
            ->where('user_location_progress.user_id', $userId)
            ->where('user_location_progress.status', 'completed')
            ->where('locations.slug', 'ukol-2')
            ->exists();
    }

    private function ensureAnthillUnlocked(): void
    {
        if (! $this->anthillUnlocked(Auth::id())) {
            abort(403, 'Zatím ještě nemáš vlastní mraveniště. Společně s ostatními mravenci zatím přespáváte na louce pod hvězdami.');
        }
    }

    private function syncPrestigeBadges(int $userId): void
    {
        $prestige = (int) DB::table('users')->where('id', $userId)->value('prestige');
        foreach ([100, 250, 500, 1000] as $threshold) {
            if ($prestige >= $threshold) {
                $this->awardBadge('prestiz-' . $threshold, $userId, 'prestige', $threshold);
            }
        }
    }

    private function messageForThreadKey($messages, string $key): ?object
    {
        if ($key === 'admin') {
            return $messages->firstWhere('thread_type', 'admin');
        }

        return $messages->first(function ($message) use ($key) {
            return $message->thread_type === 'direct'
                && ((int) $message->user_id === (int) $key || (int) $message->recipient_user_id === (int) $key);
        });
    }

    private function nextAnnouncement(): ?object
    {
        return DB::table('announcements')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('active_from')->orWhere('active_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('active_to')->orWhere('active_to', '>=', now());
            })
            ->whereNotIn('id', DB::table('user_announcement_reads')->where('user_id', Auth::id())->select('announcement_id'))
            ->orderByDesc('priority')
            ->first();
    }
}
