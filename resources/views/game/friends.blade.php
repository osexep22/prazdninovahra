@extends('layouts.app')
@section('content')
<h1>Přátelé</h1>
<div class="panel">
    <h2>Tvůj kód</h2>
    <p>Pro přidání přítele mu předej tento kód. Kód zůstává stejný.</p>
    <div class="stat" style="font-size:28px;font-weight:900;letter-spacing:.08em">{{ auth()->user()->friend_code }}</div>
</div>
<div class="panel" style="margin-top:12px">
    <h2>Přidat přítele</h2>
    <form method="post" action="/pratele">@csrf
        <label>Kód přítele</label>
        <input name="friend_code" placeholder="Např. A4P8K2" maxlength="6" style="text-transform:uppercase">
        <p><button class="primary">Přidat</button></p>
    </form>
</div>
<h2>Moji přátelé</h2>
<table>
    <tr><th>Jméno</th><th>Prestiž</th><th>Úroveň</th><th>Akce</th></tr>
    @forelse($friends as $friend)
        <tr>
            <td>{{ $friend->display_name }} <span class="small muted">({{ $friend->username }})</span></td>
            <td>{{ $friend->prestige }}</td>
            <td>{{ $friend->colony_level }}</td>
            <td class="row">
                <a class="btn" href="/pratele/{{ $friend->id }}/mraveniste">Mraveniště</a>
                <a class="btn" href="/zpravy?recipient={{ $friend->id }}">Napsat</a>
            </td>
        </tr>
    @empty
        <tr><td colspan="4">Zatím tu nejsou žádní přátelé.</td></tr>
    @endforelse
</table>
@endsection
