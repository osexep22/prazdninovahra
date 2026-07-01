@extends('layouts.app')
@section('content')
<div class="panel">
    <h1>Žebříček</h1>
    <p>Na stromě jsou nejlepší hráči, ty a tvoji přátelé. Čím výš na větvi, tím víc prestiže kolonie získala.</p>
</div>

<div class="leader-tree" aria-label="Žebříček hráčů">
    @forelse($visible as $player)
        @php
            $isMe = $player->id === auth()->id();
            $isFriend = in_array($player->id, $friendIds, true);
            $branchWidth = min(44, 18 + max(0, (int) $player->prestige / 40));
        @endphp
        <div class="leader-branch" style="--branch-width: {{ $branchWidth }}vw;">
            <div class="leader-leaf {{ $isMe ? 'rank-me' : '' }}">
                <span><b>{{ $player->rank }}.</b> {{ $player->display_name }}</span>
                <span class="small">
                    {{ $player->prestige }} prestiž
                    @if($isMe)
                        · ty
                    @elseif($isFriend)
                        · přítel
                    @endif
                </span>
            </div>
        </div>
    @empty
        <div class="leader-branch">
            <div class="leader-leaf">
                <span>Zatím tu nikdo neroste.</span>
                <span class="small">0 prestiž</span>
            </div>
        </div>
    @endforelse
</div>

@if($current)
    <div class="panel">
        <h2>Tvoje pozice</h2>
        <p>Jsi na {{ $current->rank }}. místě s hodnotou {{ $current->prestige }} prestiže.</p>
    </div>
@endif
@endsection
