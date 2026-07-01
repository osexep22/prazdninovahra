<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister']);
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/', [GameController::class, 'home']);
    Route::get('/palouk', [GameController::class, 'meadow']);
    Route::post('/intro/seen', [GameController::class, 'markIntroSeen']);
    Route::post('/onboarding/palouk/seen', [GameController::class, 'markMeadowOnboardingSeen']);
    Route::post('/announcements/{id}/seen', [GameController::class, 'markAnnouncement']);
    Route::get('/palouk/{slug}', [GameController::class, 'location']);
    Route::post('/tasks/{taskId}', [GameController::class, 'submitTask']);
    Route::post('/hints/{hintId}/buy', [GameController::class, 'buyHint']);
    Route::get('/mraveniste', [GameController::class, 'anthill']);
    Route::get('/pratele/{friendId}/mraveniste', [GameController::class, 'friendAnthill']);
    Route::post('/mraveniste/slots/{slotId}/buy', [GameController::class, 'buySlot']);
    Route::post('/mraveniste/build', [GameController::class, 'build']);
    Route::get('/budovy/{slug}', [GameController::class, 'building']);
    Route::post('/building-tasks/{taskId}', [GameController::class, 'submitBuildingTask']);
    Route::post('/buildings/{buildingId}/customization', [GameController::class, 'saveCustomization']);
    Route::get('/zebricek', [GameController::class, 'leaderboard']);
    Route::get('/pratele', [GameController::class, 'friends']);
    Route::post('/pratele', [GameController::class, 'addFriend']);
    Route::get('/zpravy', [GameController::class, 'messages']);
    Route::post('/zpravy', [GameController::class, 'sendMessage']);
    Route::post('/zpravy/{id}/reply', [GameController::class, 'replyMessage']);

    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard']);
        Route::get('/hraci', [AdminController::class, 'players']);
        Route::get('/hraci/{id}', [AdminController::class, 'player']);
        Route::post('/hraci/{id}', [AdminController::class, 'updatePlayer']);
        Route::post('/hraci/{id}/adjust', [AdminController::class, 'adjustPlayer']);
        Route::post('/hraci/{id}/notes', [AdminController::class, 'addNote']);
        Route::post('/hraci/{id}/impersonate', [AdminController::class, 'impersonate']);
        Route::delete('/hraci/{id}', [AdminController::class, 'deletePlayer']);
        Route::post('/hraci/{id}/badges', [AdminController::class, 'addBadge']);
        Route::get('/zpravy', [AdminController::class, 'messages']);
        Route::post('/zpravy/{id}', [AdminController::class, 'answerMessage']);
        Route::get('/novinky', [AdminController::class, 'announcements']);
        Route::post('/novinky', [AdminController::class, 'storeAnnouncement']);
        Route::get('/obsah', [AdminController::class, 'content']);
        Route::post('/obsah/texty/{key}', [AdminController::class, 'updateGameContent']);
        Route::post('/obsah/lokace/{id}', [AdminController::class, 'updateLocation']);
        Route::post('/obsah/ukoly/{id}', [AdminController::class, 'updateLocationTask']);
        Route::post('/obsah/budovy/ukoly/{id}', [AdminController::class, 'updateBuildingTask']);
        Route::post('/odznacky/{id}', [AdminController::class, 'updateBadge']);
        Route::post('/soubory', [AdminController::class, 'storeGameFile']);
        Route::get('/nahled/lokace/{slug}', [AdminController::class, 'previewLocation']);
        Route::get('/nahled/budovy/{slug}', [AdminController::class, 'previewBuilding']);
        Route::get('/ladeni-palouku', function () {
            $map = ['width' => 1774, 'height' => 887, 'image' => '/assets/game/meadow-map-v9.png'];
            $draft = Storage::disk('local')->exists('meadow-layout-draft.json')
                ? json_decode(Storage::disk('local')->get('meadow-layout-draft.json'), true)
                : null;
            $draftItems = collect($draft['items'] ?? [])->keyBy('slug');
            $patchDefaults = [
                'ukol-1' => ['x' => 811, 'y' => 715, 'w' => 251, 'h' => 179],
                'ukol-2' => ['x' => 496, 'y' => 529, 'w' => 231, 'h' => 195],
                'ukol-3' => ['x' => 622, 'y' => 213, 'w' => 225, 'h' => 166],
                'ukol-4' => ['x' => 1115, 'y' => 24, 'w' => 165, 'h' => 240],
                'ukol-5' => ['x' => 899, 'y' => 25, 'w' => 201, 'h' => 200],
                'ukol-6' => ['x' => 1065, 'y' => 682, 'w' => 201, 'h' => 187],
                'ukol-7' => ['x' => 878, 'y' => 333, 'w' => 213, 'h' => 174],
                'ukol-8' => ['x' => 1092, 'y' => 337, 'w' => 181, 'h' => 190],
                'ukol-9' => ['x' => 1046, 'y' => 531, 'w' => 274, 'h' => 151],
            ];
            $locations = DB::table('locations')->orderBy('sort_order')->get()->map(function ($location) use ($map, $draftItems, $patchDefaults) {
                $asset = $location->image_path ?: '/assets/placeholders/location-available.svg';
                $defaults = $patchDefaults[$location->slug] ?? null;
                $width = $defaults['w'] ?? 112;
                $height = $defaults['h'] ?? 112;
                $x = $defaults['x'] ?? ((float) $location->map_x / 100 * $map['width'] - $width / 2);
                $y = $defaults['y'] ?? ((float) $location->map_y / 100 * $map['height'] - $height / 2);
                $saved = $draftItems->get($location->slug);
                return [
                    'slug' => $location->slug,
                    'name' => $location->name,
                    'asset' => $asset,
                    'x' => round($saved['x'] ?? $x, 2),
                    'y' => round($saved['y'] ?? $y, 2),
                    'w' => round($saved['w'] ?? $width, 2),
                    'h' => round($saved['h'] ?? $height, 2),
                ];
            })->values();
            $anthillSaved = $draftItems->get('anthill');
            $locations->push([
                'slug' => 'anthill',
                'name' => 'Mraveni?t?',
                'asset' => '/assets/game/stations-final/anthill-final-edgefade.png',
                'x' => round($anthillSaved['x'] ?? 559, 2),
                'y' => round($anthillSaved['y'] ?? 393, 2),
                'w' => round($anthillSaved['w'] ?? 182, 2),
                'h' => round($anthillSaved['h'] ?? 139, 2),
            ]);

            return view('admin.meadow-editor', [
                'map' => $map,
                'items' => $locations,
                'savedAt' => $draft['saved_at'] ?? null,
            ]);
        });
        Route::post('/ladeni-palouku', function (Request $request) {
            $data = $request->validate([
                'items' => ['required', 'array'],
                'items.*.slug' => ['required', 'string'],
                'items.*.name' => ['nullable', 'string'],
                'items.*.asset' => ['nullable', 'string'],
                'items.*.x' => ['required', 'numeric'],
                'items.*.y' => ['required', 'numeric'],
                'items.*.w' => ['required', 'numeric', 'min:1'],
                'items.*.h' => ['required', 'numeric', 'min:1'],
            ]);
            $payload = [
                'map' => ['width' => 1774, 'height' => 887, 'image' => '/assets/game/meadow-map-v9.png'],
                'saved_at' => now()->toIso8601String(),
                'items' => array_values($data['items']),
            ];
            Storage::disk('local')->put('meadow-layout-draft.json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return response()->json(['ok' => true, 'saved_at' => $payload['saved_at']]);
        });
    });
    Route::post('/admin/stop-impersonating', [AdminController::class, 'stopImpersonating']);
});
