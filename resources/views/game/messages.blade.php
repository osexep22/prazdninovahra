@extends('layouts.app')
@section('content')
@php
    $threadOptions = collect();
    $adminThread = $messages->firstWhere('thread_type', 'admin');
    if ($adminThread) {
        $threadOptions->push((object) ['key' => 'admin', 'label' => 'Admini', 'message' => $adminThread]);
    }

    foreach ($friends as $friend) {
        $message = $messages->first(function ($message) use ($friend) {
            return $message->thread_type === 'direct'
                && ((int) $message->user_id === (int) $friend->id || (int) $message->recipient_user_id === (int) $friend->id);
        });
        if ($message) {
            $threadOptions->push((object) ['key' => (string) $friend->id, 'label' => $friend->display_name, 'message' => $message]);
        }
    }

    if (! $threadOptions->firstWhere('key', (string) $activeKey)) {
        $requestedFriend = $friends->firstWhere('id', (int) $activeKey);
        if ($requestedFriend) {
            $threadOptions->push((object) ['key' => (string) $requestedFriend->id, 'label' => $requestedFriend->display_name, 'message' => null]);
        } elseif ($activeKey === 'admin') {
            $threadOptions->push((object) ['key' => 'admin', 'label' => 'Admini', 'message' => null]);
        }
    }
    $activeThread = $threadOptions->firstWhere('key', (string) $activeKey);
    if (! $activeThread) {
        $activeThread = (object) ['key' => 'admin', 'label' => 'Admini', 'message' => null];
        $activeKey = 'admin';
    }
    $activeMessage = $activeThread->message;
    $activeEntries = $activeMessage ? ($entries[$activeMessage->id] ?? collect()) : collect();
@endphp

<div class="panel" style="margin-bottom:12px">
    <h1>Zprávy</h1>
    <form method="get" action="/zpravy" class="row">
        <label style="margin:0;min-width:220px">Nová zpráva
            <select name="recipient">
                <option value="admin">Adminům</option>
                @foreach($friends as $friend)
                    <option value="{{ $friend->id }}">{{ $friend->display_name }}</option>
                @endforeach
            </select>
        </label>
        <button class="primary">Otevřít chat</button>
    </form>
</div>

<div class="chat-shell">
    <aside class="panel thread-list">
        <h2>Chaty</h2>
        @forelse($threadOptions->filter(fn($thread) => $thread->message) as $thread)
            @php
                $latest = $thread->message ? ($entries[$thread->message->id] ?? collect())->last() : null;
                $readAt = $thread->message ? ($reads[$thread->message->id] ?? null) : null;
                $hasUnread = $latest
                    && (int) $latest->user_id !== auth()->id()
                    && (! $readAt || \Illuminate\Support\Carbon::parse($readAt)->lt(\Illuminate\Support\Carbon::parse($latest->created_at)));
            @endphp
            <a class="thread-link {{ (string) $activeKey === (string) $thread->key ? 'active' : '' }} {{ $hasUnread ? 'unread' : '' }}" href="/zpravy?thread={{ $thread->key }}">
                @if($hasUnread)<span class="unread-dot" title="Nová zpráva"></span>@endif
                {{ $thread->label }}
            </a>
        @empty
            <p class="small muted">Zatím žádné chaty.</p>
        @endforelse
    </aside>

    <section class="panel chat-panel">
        <h2>{{ $activeThread->label }}</h2>
        <div id="chat-scroll" class="chat chat-scroll">
            @forelse($activeEntries->take(-30) as $entry)
                @php($mine = (int) $entry->user_id === auth()->id())
                <div class="bubble {{ $mine ? 'player' : 'admin' }}">
                    <b>{{ $mine ? 'Ty' : $activeThread->label }}</b>
                    <div>{{ $entry->body }}</div>
                    <div class="time">{{ \Illuminate\Support\Carbon::parse($entry->created_at)->format('d.m.Y H:i') }}</div>
                </div>
            @empty
                <p class="muted">Zatím tu nejsou žádné zprávy.</p>
            @endforelse
        </div>

        <form class="chat-compose" method="post" action="{{ $activeMessage ? '/zpravy/' . $activeMessage->id . '/reply' : '/zpravy' }}">
            @csrf
            @unless($activeMessage)
                <input type="hidden" name="recipient" value="{{ $activeThread->key }}">
            @endunless
            <label>Napsat zprávu</label>
            <textarea name="body" rows="3" placeholder="Napiš zprávu..."></textarea>
            <p><button class="primary">Odeslat</button></p>
        </form>
    </section>
</div>
<script>
    const chatScroll = document.getElementById('chat-scroll');
    if (chatScroll) chatScroll.scrollTop = chatScroll.scrollHeight;
</script>
@endsection
