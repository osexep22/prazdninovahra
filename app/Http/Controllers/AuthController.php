<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/palouk');
        }

        return $this->uncachedView('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], $this->validationMessages(), [
            'username' => 'přezdívka',
            'password' => 'heslo',
        ]);

        $username = $this->usernameFromDisplayName($credentials['username']);

        if (Auth::attempt(['username' => $username, 'password' => $credentials['password']])) {
            $request->session()->regenerate();

            if (Auth::user()->status === 'blocked') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/login')->withErrors(['username' => 'Účet je zablokovaný.']);
            }

            return redirect()->intended('/palouk');
        }

        return back()->withErrors(['username' => 'Přezdívka nebo heslo nesedí.'])->onlyInput('username');
    }

    public function showRegister(Request $request): Response
    {
        $captcha = $this->newCaptcha($request);

        return $this->uncachedView('auth.register', [
            'src' => $request->query('src'),
            'captchaQuestion' => $captcha['question'],
        ]);
    }

    public function checkName(Request $request): JsonResponse
    {
        $displayName = trim((string) $request->query('display_name', ''));
        if ($displayName === '') {
            return response()->json(['available' => false, 'message' => 'Nejdřív napiš přezdívku.']);
        }

        if (mb_strlen($displayName) > 40) {
            return response()->json(['available' => false, 'message' => 'Přezdívka je moc dlouhá.']);
        }

        $username = $this->usernameFromDisplayName($displayName);
        $available = ! User::where('username', $username)->exists();

        return response()->json([
            'available' => $available,
            'message' => $available ? 'Přezdívka je volná.' : 'Tahle přezdívka už je obsazená.',
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:40'],
            'password' => ['required', 'confirmed', 'min:6'],
            'admin_contact_code' => ['required', 'string', 'min:4', 'max:80'],
            'antispam' => ['required', 'integer', 'in:' . $request->session()->get('antispam_answer')],
            'website' => ['nullable', 'max:0'],
            'src' => ['nullable', 'string', 'max:80'],
        ], $this->validationMessages(), [
            'display_name' => 'přezdívka',
            'password' => 'heslo',
            'admin_contact_code' => 'potvrzovací kód pro komunikaci s adminem',
            'antispam' => 'antispam',
            'website' => 'kontrolní pole',
        ]);

        $username = $this->usernameFromDisplayName($data['display_name']);

        if (User::where('username', $username)->exists()) {
            return back()
                ->withErrors(['display_name' => 'Tahle přezdívka už je obsazená. Zvol prosím jinou.'])
                ->withInput();
        }

        $user = User::create([
            'display_name' => $data['display_name'],
            'name' => $data['display_name'],
            'username' => $username,
            'password' => Hash::make($data['password']),
            'admin_contact_code_hash' => Hash::make($data['admin_contact_code']),
            'admin_contact_code_encrypted' => Crypt::encryptString($data['admin_contact_code']),
            'registration_source' => $data['src'] ?? null,
            'friend_code' => $this->newFriendCode(),
            'status' => 'pending_approval',
            'role' => 'player',
            'colony_level' => 1,
            'resources' => 80,
            'prestige' => 0,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('antispam_answer');

        return redirect('/palouk');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->withHeaders($this->noStoreHeaders());
    }

    private function usernameFromDisplayName(string $displayName): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $displayName);
        $username = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '-', $ascii ?: $displayName));
        $username = trim($username, '-_');

        return $username !== '' ? $username : 'hrac';
    }

    private function newFriendCode(): string
    {
        $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $digits = '23456789';

        do {
            $code = '';
            for ($i = 0; $i < 3; $i++) {
                $code .= $letters[random_int(0, strlen($letters) - 1)];
                $code .= $digits[random_int(0, strlen($digits) - 1)];
            }
        } while (User::where('friend_code', $code)->exists());

        return $code;
    }

    private function newCaptcha(Request $request): array
    {
        $a = random_int(2, 9);
        $b = random_int(2, 9);
        $request->session()->put('antispam_answer', $a + $b);

        return ['question' => "Kolik je {$a} + {$b}?"];
    }

    private function validationMessages(): array
    {
        return [
            'required' => 'Pole :attribute je povinné.',
            'string' => 'Pole :attribute musí být text.',
            'integer' => 'Pole :attribute musí být číslo.',
            'max' => 'Pole :attribute je příliš dlouhé.',
            'min' => 'Pole :attribute je příliš krátké.',
            'confirmed' => 'Potvrzení hesla nesedí.',
            'in' => 'Odpověď v poli :attribute není správná.',
            'alpha_dash' => 'Pole :attribute může obsahovat jen písmena, čísla, pomlčky a podtržítka.',
            'unique' => 'Tahle hodnota v poli :attribute už existuje.',
        ];
    }

    private function uncachedView(string $view, array $data = []): Response
    {
        return response()
            ->view($view, $data)
            ->withHeaders($this->noStoreHeaders());
    }

    private function noStoreHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Sat, 01 Jan 2000 00:00:00 GMT',
        ];
    }
}
