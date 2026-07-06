@extends('layouts.app')
@section('content')
<h1>Hráči</h1>
<form method="get" class="row">
    <input name="q" value="{{ request('q') }}" placeholder="Hledat podle přezdívky" style="max-width:260px">
    <button>Filtrovat</button>
</form>
<table>
    <tr>
        <th>Přezdívka</th>
        <th>Uživatelské jméno</th>
        <th>Zdroj</th>
        <th>Ověřovací kód</th>
        <th>Stav</th>
        <th>Typ</th>
        <th>Prestiž</th>
        <th></th>
    </tr>
    @foreach($players as $player)
        <tr>
            <td>{{ $player->display_name }}</td>
            <td>{{ $player->username }}</td>
            <td>
                @if($player->registration_source)
                    QR {{ $player->registration_source }}
                @else
                    <span class="small muted">neznámý</span>
                @endif
            </td>
            <td>
                @if($player->admin_contact_code_plain)
                    <code>{{ $player->admin_contact_code_plain }}</code>
                @else
                    <span class="small muted">není dostupné u staršího účtu</span>
                @endif
            </td>
            <td>{{ $player->status }}</td>
            <td>
                @if($player->is_test ?? false)
                    <span class="small">testovací</span>
                @else
                    <span class="small muted">ostrý</span>
                @endif
            </td>
            <td>{{ $player->prestige }}</td>
            <td><a href="/admin/hraci/{{ $player->id }}">detail</a></td>
        </tr>
    @endforeach
</table>
{{ $players->links() }}
@endsection
