@extends('layouts.app')
@section('content')
<div class="panel auth-card">
    <h1>Registrace</h1>
    @if($errors->any()) <div class="flash err">{{ $errors->first() }}</div> @endif
    <form method="post" action="/register">
        @csrf
        <input type="hidden" name="src" value="{{ $src }}">
        <div style="position:absolute;left:-9999px"><label>Web</label><input name="website" tabindex="-1"></div>
        <label>Přezdívka</label>
        <input name="display_name" value="{{ old('display_name') }}" required autofocus>
        <label>Heslo</label>
        <input name="password" type="password" required>
        <label>Heslo znovu</label>
        <input name="password_confirmation" type="password" required>
        <label>Potvrzovací kód pro komunikaci s adminem</label>
        <input name="admin_contact_code" value="{{ old('admin_contact_code') }}" required>
        <p class="small">Tenhle kód použiješ, když budeš admina žádat o změnu účtu nebo opravu údajů. Kód není tajné heslo do hry, piš ho čitelně.</p>
        <label>Antispam: {{ $captchaQuestion }}</label>
        <input name="antispam" inputmode="numeric" required>
        <p><button class="primary">Vytvořit kolonii</button> <a class="btn" href="/login">Mám účet</a></p>
    </form>
</div>
@endsection
