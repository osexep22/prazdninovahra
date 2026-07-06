@extends('layouts.app')
@section('content')
@php
    $loginHelpTitle = $loginHelp->title ?? 'Co je Prázdninová hra?';
    $loginHelpText = trim(implode("\n\n", array_filter([
        $loginHelp->body_top ?? null,
        $loginHelp->body_middle ?? null,
        $loginHelp->body_bottom ?? null,
    ]))) ?: "Letní dobrodružství pro děti a rodiny. Hráči plní šifry a úkoly na palouku, sbírají suroviny, staví mraveniště a postupně odemykají další části příběhu.\n\nNa cestě potkají obyvatele palouku, napraví staré chyby a pomohou mravenčí výpravě najít nový domov.";
@endphp
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
                <button class="help-dot" type="button" aria-label="{{ $loginHelpTitle }}" data-login-help-open>?</button>
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
        <a href="mailto:shmhra2025@gmail.com">shmhra2025@gmail.com</a>
        <a href="https://krucemburk.shm.cz/" target="_blank" rel="noopener">SHM Krucemburk</a>
    </div>
</div>

<div class="modal-backdrop" id="login-help-modal" hidden>
    <div class="modal-window story-window" role="dialog" aria-modal="true" aria-labelledby="login-help-title">
        <div class="help-modal-title">
            <h2 id="login-help-title">{{ $loginHelpTitle }}</h2>
            <button class="icon-close" type="button" aria-label="Zavřít" data-login-help-close>×</button>
        </div>
        <p>{!! nl2br(e($loginHelpText)) !!}</p>
        <p><button class="primary" type="button" data-login-help-close>Rozumím</button></p>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('login-help-modal');
    const open = () => modal?.removeAttribute('hidden');
    const close = () => modal?.setAttribute('hidden', 'hidden');

    document.querySelector('[data-login-help-open]')?.addEventListener('click', open);
    document.querySelectorAll('[data-login-help-close]').forEach(button => button.addEventListener('click', close));
    modal?.addEventListener('click', event => {
        if (event.target === modal) close();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') close();
    });
})();
</script>
@endsection
