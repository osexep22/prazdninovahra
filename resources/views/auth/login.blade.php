@extends('layouts.app')
@section('content')
<script>
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            window.location.reload();
        }
    });
</script>
<div class="login-shell">
    <div class="login-stack">
        <div class="auth-game-title">
            <h1>Prázdninová hra</h1>
            <p>Dobrodružství z palouku a mraveniště</p>
        </div>

        <div class="panel auth-card">
            <div class="login-title">
                <h1>Přihlášení</h1>
                <button class="help-dot" type="button" aria-label="Co je Prázdninová hra?">?</button>
                <div class="help-popover">
                    <b>Co je Prázdninová hra?</b>
                    <p>Letní dobrodružství pro děti a rodiny. Hráči plní šifry a úkoly na palouku, sbírají suroviny, staví mraveniště a postupně odemykají další části příběhu.</p>
                    <p>Na cestě potkají obyvatele palouku, napraví staré chyby a pomohou mravenčí výpravě najít nový domov.</p>
                </div>
            </div>
            @if($errors->any()) <div class="flash err">{{ $errors->first() }}</div> @endif
            <form method="post" action="/login" autocomplete="off">
                @csrf
                <label>Přezdívka</label>
                <input name="username" value="{{ old('username') }}" required autofocus>
                <label>Heslo</label>
                <input name="password" type="password" required>
                <p><button class="primary">Přihlásit</button> <a class="btn" href="{{ $src ? '/register?src=' . urlencode($src) : '/register' }}">Registrovat</a></p>
            </form>
        </div>
    </div>

    <div class="auth-footer">
        <span>Kontakt doplníme</span>
        <a href="https://krucemburk.shm.cz/" target="_blank" rel="noopener">SHM Krucemburk</a>
    </div>
</div>
@endsection
