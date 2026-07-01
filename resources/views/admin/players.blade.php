@extends('layouts.app')
@section('content')
<h1>Hráči</h1>
<form method="get" class="row"><input name="q" value="{{ request('q') }}" placeholder="Hledat" style="max-width:260px"><button>Filtrovat</button></form>
<table>
    <tr><th>Přezdívka</th><th>Uživatelské jméno</th><th>Stav</th><th>Prestiž</th><th></th></tr>
    @foreach($players as $player)
        <tr><td>{{ $player->display_name }}</td><td>{{ $player->username }}</td><td>{{ $player->status }}</td><td>{{ $player->prestige }}</td><td><a href="/admin/hraci/{{ $player->id }}">detail</a></td></tr>
    @endforeach
</table>
{{ $players->links() }}
@endsection
