@extends('layouts.app')
@section('content')
<h1>{{ $player->display_name }}</h1>
<p class="row">
    <form method="post" action="/admin/hraci/{{ $player->id }}/impersonate" class="inline">@csrf<button class="primary">Zobrazit jako hráč</button></form>
</p>
<div class="grid">
    <div class="panel">
        <h2>Ověření hráče</h2>
        <p>Zdroj registrace:
            <b>
                @if($player->registration_source)
                    QR {{ $player->registration_source }}
                @else
                    neznámý
                @endif
            </b>
        </p>
        <p class="small">Kód pro komunikaci s adminy slouží k rychlému ověření, že mluvíme se správným hráčem.</p>
        <div class="stat">
            @if($player->admin_contact_code_plain)
                <b><code>{{ $player->admin_contact_code_plain }}</code></b>
            @else
                <span class="muted">Kód není dostupný u staršího účtu. Nově registrovaní hráči ho už budou mít uložený.</span>
            @endif
        </div>
    </div>
    <div class="panel">
        <form method="post" action="/admin/hraci/{{ $player->id }}">@csrf
            <label>Stav<select name="status">
                <option value="pending_approval" @selected($player->status==='pending_approval')>Čeká na schválení</option>
                <option value="active" @selected($player->status==='active')>Schválený</option>
                <option value="blocked" @selected($player->status==='blocked')>Zablokovaný</option>
            </select></label>
            <label>Role<select name="role">
                <option value="player" @selected($player->role==='player')>Hráč</option>
                <option value="admin" @selected($player->role==='admin')>Admin</option>
            </select></label>
            <p><button class="primary">Uložit status a roli</button></p>
        </form>
    </div>
</div>
<div class="panel" style="margin-top:12px">
    <h2>Ruční úprava hodnot</h2>
    <p class="flash err">Pozor: ruční změna prestiže nebo surovin ovlivní hru a audit log. Úroveň se tady záměrně nemění.</p>
    <form method="post" action="/admin/hraci/{{ $player->id }}/adjust">@csrf
        <div class="grid">
            <label>Prestiž<input name="prestige" type="number" value="{{ $player->prestige }}"></label>
            <label>Suroviny<input name="resources" type="number" value="{{ $player->resources }}"></label>
            <div class="stat"><b>{{ $player->colony_level }}</b><br>úroveň pouze pro čtení</div>
        </div>
        <label><input type="checkbox" name="confirm_adjustment" value="1" style="width:auto"> Rozumím, chci ručně upravit hodnoty.</label>
        <p><button>Uložit hodnoty</button></p>
    </form>
</div>
<h2>Admin poznámky</h2>
<div class="panel">
    <form method="post" action="/admin/hraci/{{ $player->id }}/notes">@csrf
        <label>Nová poznámka</label><textarea name="note" rows="3"></textarea>
        <p><button>Přidat poznámku</button></p>
    </form>
</div>
@foreach($notes as $note)
    <div class="card" style="margin-top:8px"><b>{{ $note->admin_name }}</b> <span class="small muted">{{ \Illuminate\Support\Carbon::parse($note->created_at)->format('d.m.Y H:i') }}</span><p>{{ $note->note }}</p></div>
@endforeach
<h2>Odznáčky</h2>
<form method="post" action="/admin/hraci/{{ $player->id }}/badges" class="row">@csrf
    <select name="badge_id">@foreach($allBadges as $badge)<option value="{{ $badge->id }}">{{ $badge->name }}</option>@endforeach</select>
    <button>Přidat odznáček</button>
</form>
@foreach($badges as $badge)
    <div class="card row" style="margin-top:8px">
        @if($badge->icon_path)
            <span class="badge-tip" tabindex="0" data-tooltip="{{ $badge->description ?: $badge->name }}"><img class="badge-icon" src="{{ $badge->icon_path }}" alt=""></span>
        @endif
        {{ $badge->name }} - {{ $badge->awarded_at }}
    </div>
@endforeach
<h2>Budovy</h2>
@foreach($buildings as $building)
    <div class="card row" style="margin-bottom:8px">
        <span>{{ $building->name }}</span>
        <a class="btn" href="/admin/nahled/budovy/{{ $building->slug }}?player_id={{ $player->id }}">Náhled jako tento hráč</a>
    </div>
@endforeach
<h2>Úkoly</h2>
@foreach($tasks as $task)<div class="card" style="margin-bottom:8px">Úkol #{{ $task->location_task_id }} - {{ $task->status }}</div>@endforeach
<h2>Zprávy</h2>
@foreach($messages as $message)
    @php($statusLabel = ['new' => 'Nová', 'read' => 'Přečtená', 'answered' => 'Odpovězená', 'closed' => 'Uzavřená'][$message->status] ?? $message->status)
    <div class="card" style="margin-bottom:8px">{{ $message->subject }} - {{ $statusLabel }}</div>
@endforeach
<h2>Audit</h2>
@foreach($audit as $item)<div class="card" style="margin-bottom:8px">{{ $item->created_at }} - {{ $item->action }}</div>@endforeach
<h2>Smazání hráče</h2>
<div class="panel">
    <form method="post" action="/admin/hraci/{{ $player->id }}" data-confirm-delete="Opravdu smazat hráče {{ $player->display_name }}? Tahle akce smaže i jeho postup, zprávy a budovy.">
        @csrf @method('DELETE')
        <label><input type="checkbox" name="confirm_delete" value="1" style="width:auto"> Potvrzuji smazání hráče.</label>
        <p><button>Smazat hráče</button></p>
    </form>
</div>
<script>
document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!confirm(form.dataset.confirmDelete || 'Opravdu smazat?')) {
            event.preventDefault();
        }
    });
});
</script>
@endsection
