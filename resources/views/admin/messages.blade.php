@extends('layouts.app')
@section('content')
<h1>Zprávy administrátorům</h1>
@foreach($messages as $message)
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
@endforeach
@endsection
