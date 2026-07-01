@extends('layouts.app')
@section('content')
<h1>Přehled administrace</h1>
<div class="stats">
    <div class="stat"><b>{{ $players }}</b><br>hráčů</div>
    <div class="stat"><b>{{ $pending }}</b><br>čeká</div>
    <div class="stat"><b>{{ $newMessages }}</b><br>nové zprávy</div>
</div>
<p>Úkoly čekající na kontrolu: <b>{{ $manualTasks }}</b></p>
<p class="row"><a class="btn" href="/admin/hraci">Hráči</a><a class="btn" href="/admin/zpravy">Zprávy</a><a class="btn" href="/admin/novinky">Novinky</a><a class="btn" href="/admin/obsah">Herní obsah</a></p>
<h2>Zdroje registrací</h2>
<div class="grid">
    @for($source = 1; $source <= 7; $source++)
        <div class="card">
            <h3>QR {{ $source }}</h3>
            <p><b>{{ $sourceStats[(string) $source] ?? 0 }}</b> hráčů</p>
            <p class="small"><code>{{ url('/login?src=' . $source) }}</code></p>
        </div>
    @endfor
    <div class="card">
        <h3>Neznámý zdroj</h3>
        <p><b>{{ $sourceStats[''] ?? $sourceStats[null] ?? 0 }}</b> hráčů</p>
        <p class="small">Registrace bez QR parametru.</p>
    </div>
</div>
<h2>Poslední aktivita</h2>
@foreach($activity as $item)
    <div class="card" style="margin-bottom:8px">
        <b>{{ $item->action }}</b>
        <div class="small muted">{{ \Illuminate\Support\Carbon::parse($item->created_at)->format('d.m.Y H:i') }}</div>
        <p>
            Kdo: {{ $item->actor_name ?? 'system' }} @if($item->actor_username) ({{ $item->actor_username }}) @endif<br>
            Cíl: {{ $item->target_name ?? 'bez hráče' }} @if($item->target_username) ({{ $item->target_username }}) @endif<br>
            Objekt: {{ $item->entity_type ?? '-' }} #{{ $item->entity_id ?? '-' }}
        </p>
        @if($item->note)<p class="small">Poznámka: {{ $item->note }}</p>@endif
    </div>
@endforeach
@endsection
