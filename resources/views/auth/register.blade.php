@extends('layouts.app')
@section('content')
<script>
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            window.location.reload();
        }
    });
</script>
<div class="panel auth-card">
    <h1>Registrace</h1>
    @if($errors->any()) <div class="flash err">{{ $errors->first() }}</div> @endif
    <form method="post" action="/register" autocomplete="off" data-register-form>
        @csrf
        <input type="hidden" name="src" value="{{ old('src', $src) }}">
        <div style="position:absolute;left:-9999px"><label>Web</label><input name="website" tabindex="-1"></div>

        <label>Přezdívka</label>
        <input name="display_name" value="{{ old('display_name') }}" required autofocus data-name-input>
        <p class="field-hint small" data-name-feedback>Zadej přezdívku, pod kterou tě uvidí ostatní hráči.</p>

        <label>Heslo</label>
        <input name="password" type="password" required data-password-input>
        <p class="field-hint small" data-password-feedback>Heslo musí mít alespoň 6 znaků.</p>

        <label>Heslo znovu</label>
        <input name="password_confirmation" type="password" required data-password-confirmation-input>
        <p class="field-hint small" data-password-match-feedback>Pro kontrolu napiš stejné heslo ještě jednou.</p>

        <label>Potvrzovací kód pro komunikaci s adminem</label>
        <input name="admin_contact_code" value="{{ old('admin_contact_code') }}" required>
        <p class="small">Tenhle kód použiješ, když budeš admina žádat o změnu účtu nebo opravu údajů. Není to heslo do hry, ale zapiš si ho čitelně.</p>

        <label>Antispam: {{ $captchaQuestion }}</label>
        <input name="antispam" inputmode="numeric" required>
        <p><button class="primary">Vytvořit kolonii</button> <a class="btn" href="/login">Mám účet</a></p>
    </form>
</div>
<script>
(() => {
    const form = document.querySelector('[data-register-form]');
    if (!form) return;

    const nameInput = form.querySelector('[data-name-input]');
    const passwordInput = form.querySelector('[data-password-input]');
    const confirmationInput = form.querySelector('[data-password-confirmation-input]');
    const nameFeedback = form.querySelector('[data-name-feedback]');
    const passwordFeedback = form.querySelector('[data-password-feedback]');
    const matchFeedback = form.querySelector('[data-password-match-feedback]');
    let nameTimer = null;

    const setFeedback = (element, type, message) => {
        element.textContent = message;
        element.classList.remove('valid', 'invalid');
        if (type) element.classList.add(type);
    };

    const checkName = () => {
        const value = nameInput.value.trim();
        clearTimeout(nameTimer);
        if (value.length === 0) {
            setFeedback(nameFeedback, '', 'Zadej přezdívku, pod kterou tě uvidí ostatní hráči.');
            return;
        }
        if (value.length > 40) {
            setFeedback(nameFeedback, 'invalid', 'Přezdívka je moc dlouhá.');
            return;
        }
        setFeedback(nameFeedback, '', 'Kontroluji dostupnost přezdívky...');
        nameTimer = setTimeout(async () => {
            try {
                const response = await fetch(`/register/check-name?display_name=${encodeURIComponent(value)}`, {
                    headers: { 'Accept': 'application/json' },
                });
                const result = await response.json();
                setFeedback(nameFeedback, result.available ? 'valid' : 'invalid', result.message);
            } catch (error) {
                setFeedback(nameFeedback, '', 'Dostupnost zkontrolujeme při odeslání formuláře.');
            }
        }, 800);
    };

    const checkPasswords = () => {
        const password = passwordInput.value;
        const confirmation = confirmationInput.value;
        setFeedback(
            passwordFeedback,
            password.length >= 6 ? 'valid' : (password.length ? 'invalid' : ''),
            password.length >= 6 ? 'Heslo je dost dlouhé.' : 'Heslo musí mít alespoň 6 znaků.'
        );
        setFeedback(
            matchFeedback,
            confirmation && confirmation === password ? 'valid' : (confirmation ? 'invalid' : ''),
            confirmation && confirmation === password ? 'Hesla se shodují.' : 'Pro kontrolu napiš stejné heslo ještě jednou.'
        );
    };

    nameInput.addEventListener('input', checkName);
    passwordInput.addEventListener('input', checkPasswords);
    confirmationInput.addEventListener('input', checkPasswords);
    checkName();
    checkPasswords();
})();
</script>
@endsection
