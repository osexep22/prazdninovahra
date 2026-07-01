<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
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
                return back()->withErrors(['username' => 'Účet je zablokovaný.']);
            }

            return redirect()->intended('/palouk');
        }

        return back()->withErrors(['username' => 'Přezdívka nebo heslo nesedí.'])->onlyInput('username');
    }

    public function showRegister(Request $request): View
    {
        $captcha = $this->newCaptcha($request);

        return view('auth.register', [
            'src' => $request->query('src'),
            'captchaQuestion' => $captcha['question'],
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
            'registration_source' => $data['src'] ?? null,
            'friend_code' => $this->newFriendCode(),
            'status' => 'pending_approval',
            'role' => 'player',
            'colony_level' => 1,
            'resources' => 80,
            'prestige' => 0,
        ]);

        Auth::login($user);
        $request->session()->forget('antispam_answer');

        return redirect('/palouk');
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
        do {
            $code = strtoupper(Str::random(8));
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

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
