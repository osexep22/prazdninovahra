@extends('layouts.app')
@section('content')
<h1>Zprávy administrátorům</h1>
<p class="row">
    <a class="btn" href="/zpravy">Zprávy</a>
    <a class="btn primary" href="/admin/zpravy">Zprávy admin</a>
</p>

<div class="panel" style="margin-bottom:12px">
    <h2>Nový administrátorský chat</h2>
    <form method="post" action="/admin/zpravy">
        @csrf
        <div class="grid">
            <label>Hráč
                <select name="player_id" required>
                    <option value="">Vyber hráče...</option>
                    @foreach($players as $player)
                        <option value="{{ $player->id }}">{{ $player->display_name }} ({{ $player->username }}) - {{ $player->status }}</option>
                    @endforeach
                </select>
            </label>
            <label>Zpráva
                <textarea name="body" rows="3" required placeholder="Napiš zprávu jako administrátor..."></textarea>
            </label>
        </div>
        <p><button class="primary">Odeslat jako admin</button></p>
    </form>
</div>

@forelse($messages as $message)
    @php($statusLabel = ['new' => 'Nová', 'read' => 'Přečtená', 'answered' => 'Odpovězená', 'closed' => 'Uzavřená'][$message->status] ?? $message->status)
    <div class="card" style="margin-bottom:12px">
        <h3>Vlákno s adminy <span class="small">od {{ $message->display_name }} | {{ $statusLabel }}</span></h3>
        <div class="chat">
            @foreach(($entries[$message->id] ?? collect()) as $entry)
                <div class="bubble {{ $entry->sender_role === 'admin' ? 'admin' : 'player' }}">
                    <b>{{ $entry->sender_role === 'admin' ? 'Admin' : $message->display_name }}</b>
                    <div>{{ $entry->body }}</div>
                    <div class="time">{{ \Illuminate\Support\Carbon::parse($entry->created_at)->format('d.m.Y H:i') }}</div>
                </div>
            @endforeach
        </div>
        <form method="post" action="/admin/zpravy/{{ $message->id }}">@csrf
            <label>Nová odpověď</label><textarea name="admin_reply" rows="3"></textarea>
            <label>Stav</label><select name="status">
                <option value="new" @selected($message->status === 'new')>Nová</option>
                <option value="read" @selected($message->status === 'read')>Přečtená</option>
                <option value="answered" @selected($message->status === 'answered')>Odpovězená</option>
                <option value="closed" @selected($message->status === 'closed')>Uzavřená</option>
            </select>
            <p><button class="primary">Odeslat / uložit</button></p>
        </form>
    </div>
@empty
    <div class="panel">
        <p>Zatím tu nejsou žádné zprávy pro administrátory.</p>
    </div>
@endforelse
@endsection
