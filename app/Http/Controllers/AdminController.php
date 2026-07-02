<?php

namespace App\Http\Controllers;

use App\Support\AnswerNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminController extends Controller
{
    private function guardAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->role === 'admin', 403);
    }

    public function dashboard(): View
    {
        $this->guardAdmin();

        return view('admin.dashboard', [
            'players' => DB::table('users')->where('role', 'player')->count(),
            'pending' => DB::table('users')->where('status', 'pending_approval')->count(),
            'newMessages' => DB::table('messages')->where('status', 'new')->count(),
            'manualTasks' => DB::table('user_task_progress')->where('status', 'pending')->whereNotNull('submitted_answer')->count(),
            'sourceStats' => DB::table('users')
                ->where('role', 'player')
                ->select('registration_source', DB::raw('COUNT(*) as total'))
                ->groupBy('registration_source')
                ->pluck('total', 'registration_source'),
            'activity' => DB::table('audit_logs')
                ->leftJoin('users as actors', 'actors.id', '=', 'audit_logs.actor_user_id')
                ->leftJoin('users as targets', 'targets.id', '=', 'audit_logs.target_user_id')
                ->select('audit_logs.*', 'actors.display_name as actor_name', 'actors.username as actor_username', 'targets.display_name as target_name', 'targets.username as target_username')
                ->latest('audit_logs.created_at')
                ->limit(12)
                ->get(),
        ]);
    }

    public function players(Request $request): View
    {
        $this->guardAdmin();
        $query = DB::table('users')->where('role', 'player')->orderBy('status')->orderByDesc('prestige');
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('username', 'like', '%' . $request->q . '%')
                    ->orWhere('display_name', 'like', '%' . $request->q . '%');
            });
        }

        $players = $query->paginate(30);
        $players->getCollection()->transform(function ($player) {
            $player->admin_contact_code_plain = $this->decryptAdminContactCode($player->admin_contact_code_encrypted ?? null);

            return $player;
        });

        return view('admin.players', ['players' => $players]);
    }

    public function player(int $id): View
    {
        $this->guardAdmin();
        $player = DB::table('users')->find($id);
        abort_unless($player, 404);
        $player->admin_contact_code_plain = $this->decryptAdminContactCode($player->admin_contact_code_encrypted ?? null);

        return view('admin.player', [
            'player' => $player,
            'tasks' => DB::table('user_task_progress')->where('user_id', $id)->latest()->get(),
            'buildings' => DB::table('user_buildings')->join('buildings', 'buildings.id', '=', 'user_buildings.building_id')->where('user_id', $id)->select('buildings.*')->get(),
            'badges' => DB::table('user_badges')->join('badges', 'badges.id', '=', 'user_badges.badge_id')->where('user_id', $id)->select('badges.*', 'user_badges.awarded_at')->get(),
            'messages' => DB::table('messages')->where('user_id', $id)->latest()->get(),
            'audit' => DB::table('audit_logs')->where('target_user_id', $id)->latest('created_at')->limit(50)->get(),
            'notes' => DB::table('admin_notes')
                ->join('users', 'users.id', '=', 'admin_notes.admin_user_id')
                ->where('target_user_id', $id)
                ->select('admin_notes.*', 'users.display_name as admin_name')
                ->latest('admin_notes.created_at')
                ->get(),
            'allBadges' => DB::table('badges')->get(),
        ]);
    }

    private function decryptAdminContactCode(?string $encrypted): ?string
    {
        if (! $encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return null;
        }
    }

    public function updatePlayer(Request $request, int $id): RedirectResponse
    {
        $this->guardAdmin();
        $data = $request->validate([
            'status' => ['required', 'in:pending_approval,active,blocked'],
            'role' => ['required', 'in:player,admin'],
        ]);

        DB::table('users')->where('id', $id)->update([
            'status' => $data['status'],
            'role' => $data['role'],
            'updated_at' => now(),
        ]);
        $this->audit('admin_status_role_changed', 'user', $id, $id, $data);

        return back()->with('success', 'Stav a role hráče upraveny.');
    }

    public function adjustPlayer(Request $request, int $id): RedirectResponse
    {
        $this->guardAdmin();
        $data = $request->validate([
            'prestige' => ['required', 'integer', 'min:0'],
            'resources' => ['required', 'integer', 'min:0'],
            'confirm_adjustment' => ['accepted'],
        ]);

        DB::table('users')->where('id', $id)->update([
            'prestige' => $data['prestige'],
            'resources' => $data['resources'],
            'updated_at' => now(),
        ]);
        $this->audit('admin_values_adjusted', 'user', $id, $id, $data, 'Ruční změna prestiže/surovin potvrzena adminem.');

        return back()->with('success', 'Prestiž a suroviny upraveny.');
    }

    public function addNote(Request $request, int $id): RedirectResponse
    {
        $this->guardAdmin();
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);

        DB::table('admin_notes')->insert([
            'admin_user_id' => Auth::id(),
            'target_user_id' => $id,
            'note' => $data['note'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->audit('admin_note_added', 'user', $id, $id, null, $data['note']);

        return back()->with('success', 'Poznámka uložena.');
    }

    public function impersonate(int $id): RedirectResponse
    {
        $this->guardAdmin();
        $player = DB::table('users')->where('id', $id)->where('role', 'player')->first();
        abort_unless($player, 404);

        session(['impersonator_admin_id' => Auth::id()]);
        Auth::loginUsingId($player->id);

        return redirect('/palouk')->with('success', 'Jsi v náhledu hráče ' . $player->display_name . '.');
    }

    public function stopImpersonating(): RedirectResponse
    {
        $adminId = session('impersonator_admin_id');
        abort_unless($adminId, 403);

        session()->forget('impersonator_admin_id');
        Auth::loginUsingId($adminId);

        return redirect('/admin')->with('success', 'Jsi zpět v admin účtu.');
    }

    public function deletePlayer(Request $request, int $id): RedirectResponse
    {
        $this->guardAdmin();
        $player = DB::table('users')->find($id);
        abort_unless($player && $player->role === 'player', 404);

        $request->validate(['confirm_delete' => ['accepted']]);
        $this->audit('player_deleted', 'user', $id, $id, ['username' => $player->username, 'display_name' => $player->display_name]);
        DB::table('users')->where('id', $id)->delete();

        return redirect('/admin/hraci')->with('success', 'Hráč byl smazán.');
    }

    public function addBadge(Request $request, int $id): RedirectResponse
    {
        $this->guardAdmin();
        $data = $request->validate(['badge_id' => ['required', 'integer']]);
        $badge = DB::table('badges')->find($data['badge_id']);
        abort_unless($badge, 404);
        if (DB::table('user_badges')->where(['user_id' => $id, 'badge_id' => $badge->id])->exists()) {
            return back()->with('error', 'Hráč už tento odznáček má.');
        }

        DB::table('user_badges')->insert([
            'user_id' => $id,
            'badge_id' => $badge->id,
            'awarded_at' => now(),
            'awarded_for_entity_type' => 'admin',
            'awarded_for_entity_id' => Auth::id(),
        ]);
        DB::table('users')->where('id', $id)->increment('prestige', $badge->prestige_bonus);
        $this->audit('badge_awarded', 'badge', $badge->id, $id);

        return back()->with('success', 'Odznáček přidán.');
    }

    public function messages(): View
    {
        $this->guardAdmin();
        $messages = DB::table('messages')->join('users', 'users.id', '=', 'messages.user_id')
            ->where('messages.thread_type', 'admin')
            ->select('messages.*', 'users.display_name')->latest('messages.updated_at')->get();

        return view('admin.messages', [
            'messages' => $messages,
            'players' => DB::table('users')
                ->where('role', 'player')
                ->orderBy('display_name')
                ->select('id', 'display_name', 'username', 'status')
                ->get(),
            'entries' => DB::table('message_entries')
                ->whereIn('message_id', $messages->pluck('id'))
                ->orderBy('created_at')
                ->get()
                ->groupBy('message_id'),
        ]);
    }

    public function startAdminMessage(Request $request): RedirectResponse
    {
        $this->guardAdmin();
        $data = $request->validate([
            'player_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['required', 'string', 'max:4000'],
        ], [], [
            'player_id' => 'hráč',
            'body' => 'zpráva',
        ]);

        $player = DB::table('users')->where('id', $data['player_id'])->where('role', 'player')->first();
        abort_unless($player, 404);

        $message = DB::table('messages')
            ->where('user_id', $player->id)
            ->whereNull('recipient_user_id')
            ->where('thread_type', 'admin')
            ->first();

        $messageId = $message?->id ?? DB::table('messages')->insertGetId([
            'user_id' => $player->id,
            'recipient_user_id' => null,
            'thread_type' => 'admin',
            'subject' => 'Admini',
            'body' => '',
            'status' => 'answered',
            'admin_user_id' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('message_entries')->insert([
            'message_id' => $messageId,
            'user_id' => Auth::id(),
            'sender_role' => 'admin',
            'body' => $data['body'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('messages')->where('id', $messageId)->update([
            'body' => $data['body'],
            'admin_reply' => $data['body'],
            'admin_user_id' => Auth::id(),
            'answered_at' => now(),
            'status' => 'answered',
            'updated_at' => now(),
        ]);
        $this->audit('admin_message_started', 'message', $messageId, $player->id);

        return redirect('/admin/zpravy')->with('success', 'Administrátorská zpráva byla odeslána hráči ' . $player->display_name . '.');
    }

    public function answerMessage(Request $request, int $id): RedirectResponse
    {
        $this->guardAdmin();
        $data = $request->validate([
            'admin_reply' => ['nullable', 'string', 'max:4000'],
            'status' => ['required', 'in:new,read,answered,closed'],
        ]);
        $message = DB::table('messages')->where('id', $id)->where('thread_type', 'admin')->first();
        abort_unless($message, 404);

        if ($data['admin_reply']) {
            DB::table('message_entries')->insert([
                'message_id' => $id,
                'user_id' => Auth::id(),
                'sender_role' => 'admin',
                'body' => $data['admin_reply'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $update = [
            'admin_user_id' => Auth::id(),
            'status' => $data['status'],
            'updated_at' => now(),
        ];
        if ($data['admin_reply']) {
            $update['admin_reply'] = $data['admin_reply'];
            $update['answered_at'] = now();
            $update['status'] = 'answered';
        }

        DB::table('messages')->where('id', $id)->update($update);
        $this->audit('message_answered', 'message', $id);

        return back()->with('success', 'Zpráva upravena.');
    }

    public function announcements(): View
    {
        $this->guardAdmin();
        return view('admin.announcements', ['announcements' => DB::table('announcements')->latest()->get()]);
    }

    public function storeAnnouncement(Request $request): RedirectResponse
    {
        $this->guardAdmin();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string'],
            'priority' => ['required', 'in:low,normal,high'],
            'is_active' => ['nullable'],
        ]);

        DB::table('announcements')->insert([
            'title' => $data['title'],
            'body' => $data['body'],
            'priority' => $data['priority'],
            'is_active' => $request->boolean('is_active'),
            'active_from' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Oznámení vytvořeno.');
    }

    public function content(): View
    {
        $this->guardAdmin();

        return view('admin.content', [
            'intro' => DB::table('game_contents')->where('key', 'intro_story')->first(),
            'locations' => DB::table('locations')->get(),
            'tasks' => DB::table('location_tasks')
                ->join('locations', 'locations.id', '=', 'location_tasks.location_id')
                ->select('location_tasks.*', 'locations.slug as location_slug', 'locations.name as location_name')
                ->orderBy('locations.sort_order')
                ->orderBy('location_tasks.sort_order')
                ->get(),
            'taskHints' => DB::table('task_hints')->orderBy('sort_order')->get()->groupBy('location_task_id'),
            'buildings' => DB::table('buildings')->get(),
            'buildingTasks' => DB::table('building_tasks')
                ->join('buildings', 'buildings.id', '=', 'building_tasks.building_id')
                ->select('building_tasks.*', 'buildings.name as building_name')
                ->orderBy('buildings.id')
                ->orderBy('building_tasks.sort_order')
                ->get(),
            'badges' => DB::table('badges')->get(),
            'gameFiles' => DB::table('game_files')->orderByDesc('created_at')->get(),
        ]);
    }

    public function storeGameFile(Request $request): RedirectResponse
    {
        $this->guardAdmin();
        $request->validate([
            'file' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,pdf', 'max:10240'],
            'category' => ['required', 'in:story,location,task,badge,general'],
        ]);

        $path = $this->uploadPublicFile($request, 'file', 'uploads/library/' . $request->input('category'), $request->input('category'));
        $this->audit('game_file_uploaded', 'game_file', null, null, ['path' => $path]);

        return back()->with('success', 'Soubor nahrán.');
    }

    public function updateGameContent(Request $request, string $key): RedirectResponse
    {
        $this->guardAdmin();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body_top' => ['nullable', 'string'],
            'body_middle' => ['nullable', 'string'],
            'body_bottom' => ['nullable', 'string'],
            'image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'image_2' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'existing_image_path' => ['nullable', 'string', 'max:255'],
            'existing_image_path_2' => ['nullable', 'string', 'max:255'],
        ]);

        $current = DB::table('game_contents')->where('key', $key)->first();
        $payload = [
            'title' => $data['title'],
            'body_top' => $data['body_top'] ?? null,
            'body_middle' => $data['body_middle'] ?? null,
            'body_bottom' => $data['body_bottom'] ?? null,
            'image_path' => $this->chosenFilePath($data['existing_image_path'] ?? null, $this->uploadPublicFile($request, 'image', 'uploads/story')) ?: ($current->image_path ?? null),
            'image_path_2' => $this->chosenFilePath($data['existing_image_path_2'] ?? null, $this->uploadPublicFile($request, 'image_2', 'uploads/story')) ?: ($current->image_path_2 ?? null),
            'updated_at' => now(),
        ];

        DB::table('game_contents')->updateOrInsert(
            ['key' => $key],
            $payload + ['created_at' => $current->created_at ?? now()]
        );
        $this->audit('game_content_updated', 'game_content', null, null, ['key' => $key]);

        return back()->with('success', 'Text hry uložen.');
    }

    public function updateLocation(Request $request, int $id): RedirectResponse
    {
        $this->guardAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string'],
            'story' => ['nullable', 'string'],
            'story_completed' => ['nullable', 'string'],
            'tooltip' => ['nullable', 'string', 'max:180'],
            'tooltip_completed' => ['nullable', 'string', 'max:180'],
            'map_x' => ['required', 'integer', 'min:0', 'max:100'],
            'map_y' => ['required', 'integer', 'min:0', 'max:100'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:999'],
            'reward_prestige' => ['required', 'integer', 'min:0'],
            'reward_resources' => ['required', 'integer', 'min:0'],
            'reward_colony_level' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'story_image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'completed_image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'existing_image_path' => ['nullable', 'string', 'max:255'],
            'existing_story_image_path' => ['nullable', 'string', 'max:255'],
            'existing_completed_image_path' => ['nullable', 'string', 'max:255'],
        ]);
        $location = DB::table('locations')->find($id);
        abort_unless($location, 404);

        DB::table('locations')->where('id', $id)->update([
            'name' => $data['name'],
            'description' => $data['description'],
            'story' => $data['story'] ?? null,
            'story_completed' => $data['story_completed'] ?? null,
            'tooltip' => $data['tooltip'] ?? null,
            'tooltip_completed' => $data['tooltip_completed'] ?? null,
            'map_x' => $data['map_x'],
            'map_y' => $data['map_y'],
            'sort_order' => $data['sort_order'],
            'reward_prestige' => $data['reward_prestige'],
            'reward_resources' => $data['reward_resources'],
            'reward_colony_level' => $data['reward_colony_level'],
            'image_path' => $this->chosenFilePath($data['existing_image_path'] ?? null, $this->uploadPublicFile($request, 'image', 'uploads/locations')) ?: $location->image_path,
            'story_image_path' => $this->chosenFilePath($data['existing_story_image_path'] ?? null, $this->uploadPublicFile($request, 'story_image', 'uploads/locations')) ?: $location->story_image_path,
            'completed_image_path' => $this->chosenFilePath($data['existing_completed_image_path'] ?? null, $this->uploadPublicFile($request, 'completed_image', 'uploads/locations')) ?: ($location->completed_image_path ?? null),
            'updated_at' => now(),
        ]);
        $this->audit('location_updated', 'location', $id);

        return back()->with('success', 'Lokace uložena.');
    }

    public function updateLocationTask(Request $request, int $id): RedirectResponse
    {
        $this->guardAdmin();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string'],
            'pdf_intro' => ['nullable', 'string'],
            'hint_text' => ['nullable', 'string'],
            'answer' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', 'in:code,manual,info'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:999'],
            'reward_prestige' => ['required', 'integer', 'min:0'],
            'reward_resources' => ['required', 'integer', 'min:0'],
            'required_for_completion' => ['nullable'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'existing_pdf_path' => ['nullable', 'string', 'max:255'],
        ]);
        $task = DB::table('location_tasks')->find($id);
        abort_unless($task, 404);

        $payload = [
            'title' => $data['title'],
            'body' => $data['body'],
            'pdf_intro' => $data['pdf_intro'] ?? null,
            'type' => $data['type'],
            'sort_order' => $data['sort_order'],
            'reward_prestige' => $data['reward_prestige'],
            'reward_resources' => $data['reward_resources'],
            'required_for_completion' => $request->boolean('required_for_completion'),
            'pdf_path' => $this->chosenFilePath($data['existing_pdf_path'] ?? null, $this->uploadPublicFile($request, 'pdf', 'uploads/tasks')) ?: $task->pdf_path,
            'updated_at' => now(),
        ];
        if (($data['answer'] ?? '') !== '') {
            $payload['answer_hash'] = Hash::make(AnswerNormalizer::normalize($data['answer']));
        }

        DB::table('location_tasks')->where('id', $id)->update($payload);
        DB::table('task_hints')->updateOrInsert(
            ['location_task_id' => $id, 'sort_order' => 1],
            [
                'text' => trim((string) ($data['hint_text'] ?? '')),
                'cost_resources' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $this->audit('location_task_updated', 'location_task', $id);

        return back()->with('success', 'Úkol uložen.');
    }

    public function updateBuildingTask(Request $request, int $id): RedirectResponse
    {
        $this->guardAdmin();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string'],
            'answer' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:999'],
            'reward_prestige' => ['required', 'integer', 'min:0'],
            'reward_resources' => ['required', 'integer', 'min:0'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'existing_pdf_path' => ['nullable', 'string', 'max:255'],
        ]);
        $task = DB::table('building_tasks')->find($id);
        abort_unless($task, 404);

        $payload = [
            'title' => $data['title'],
            'body' => $data['body'],
            'sort_order' => $data['sort_order'],
            'reward_prestige' => $data['reward_prestige'],
            'reward_resources' => $data['reward_resources'],
            'pdf_path' => $this->chosenFilePath($data['existing_pdf_path'] ?? null, $this->uploadPublicFile($request, 'pdf', 'uploads/tasks')) ?: $task->pdf_path,
            'updated_at' => now(),
        ];
        if (($data['answer'] ?? '') !== '') {
            $payload['answer_hash'] = Hash::make(AnswerNormalizer::normalize($data['answer']));
        }

        DB::table('building_tasks')->where('id', $id)->update($payload);
        $this->audit('building_task_updated', 'building_task', $id);

        return back()->with('success', 'Podúkol uložen.');
    }

    public function updateBadge(Request $request, int $id): RedirectResponse
    {
        $this->guardAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:1000'],
            'prestige_bonus' => ['required', 'integer', 'min:0'],
            'icon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'existing_icon_path' => ['nullable', 'string', 'max:255'],
        ]);
        $badge = DB::table('badges')->find($id);
        abort_unless($badge, 404);

        DB::table('badges')->where('id', $id)->update([
            'name' => $data['name'],
            'description' => $data['description'],
            'prestige_bonus' => $data['prestige_bonus'],
            'icon_path' => $this->chosenFilePath($data['existing_icon_path'] ?? null, $this->uploadPublicFile($request, 'icon', 'uploads/badges', 'badge')) ?: $badge->icon_path,
            'updated_at' => now(),
        ]);
        $this->audit('badge_updated', 'badge', $id);

        return back()->with('success', 'Odznáček uložen.');
    }

    public function previewLocation(string $slug): View
    {
        $this->guardAdmin();
        $location = DB::table('locations')->where('slug', $slug)->first();
        abort_unless($location, 404);

        $state = 'available';
        $tasks = DB::table('location_tasks')->where('location_id', $location->id)->get();
        $progress = collect();
        $hints = DB::table('task_hints')->whereIn('location_task_id', $tasks->pluck('id'))->orderBy('sort_order')->get()->groupBy('location_task_id');
        $purchased = DB::table('task_hints')->whereIn('location_task_id', $tasks->pluck('id'))->pluck('id')->all();

        return view('game.location', compact('location', 'state', 'tasks', 'progress', 'hints', 'purchased'));
    }

    public function previewBuilding(string $slug): View
    {
        $this->guardAdmin();
        $building = DB::table('buildings')->where('slug', $slug)->first();
        abort_unless($building, 404);

        $tasks = DB::table('building_tasks')->where('building_id', $building->id)->get();
        $progress = collect();
        $completed = 0;
        $unlocks = DB::table('customization_unlocks')->where('building_id', $building->id)->get();
        $userUnlocks = $unlocks->pluck('id')->all();
        $customization = null;

        return view('game.building', compact('building', 'tasks', 'progress', 'completed', 'unlocks', 'userUnlocks', 'customization'));
    }

    private function audit(string $action, ?string $entityType, ?int $entityId, ?int $targetUserId = null, ?array $newValue = null, ?string $note = null): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => Auth::id(),
            'target_user_id' => $targetUserId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'new_value' => $newValue ? json_encode($newValue) : null,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    private function chosenFilePath(?string $existingPath, ?string $uploadedPath): ?string
    {
        if ($uploadedPath) {
            return $uploadedPath;
        }
        if ($existingPath && DB::table('game_files')->where('public_path', $existingPath)->exists()) {
            return $existingPath;
        }

        return null;
    }

    private function uploadPublicFile(Request $request, string $field, string $directory, ?string $category = null): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        $file = $request->file($field);
        $extension = match ($file->getMimeType()) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => strtolower($file->extension() ?: 'bin'),
        };
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: $field;
        $safeName = $baseName . '-' . Str::ulid() . '.' . $extension;
        $diskPath = trim($directory, '/') . '/' . $safeName;

        $stored = Storage::disk('public')->putFileAs(trim($directory, '/'), $file, $safeName);
        abort_unless($stored, 500, 'Soubor se nepodařilo uložit.');
        $publicPath = '/storage/' . $diskPath;

        DB::table('game_files')->updateOrInsert(
            ['public_path' => $publicPath],
            [
                'disk_path' => $diskPath,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
                'size' => $file->getSize() ?: 0,
                'category' => $category ?: basename($directory),
                'uploaded_by' => Auth::id(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return $publicPath;
    }
}
