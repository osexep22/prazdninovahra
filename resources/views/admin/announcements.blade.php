@extends('layouts.app')
@section('content')
<h1>Novinky</h1>
<div class="panel">
    <form method="post" action="/admin/novinky">@csrf
        <label>Titulek</label><input name="title">
        <label>Text</label><textarea name="body"></textarea>
        <label>Priorita</label><select name="priority"><option value="normal">Normální</option><option value="high">Vysoká</option><option value="low">Nízká</option></select>
        <label><input type="checkbox" name="is_active" value="1" style="width:auto" checked> Aktivní</label>
        <p><button class="primary">Vytvořit</button></p>
    </form>
</div>
@foreach($announcements as $announcement)
    <div class="card" style="margin-top:8px"><b>{{ $announcement->title }}</b><p>{{ $announcement->body }}</p></div>
@endforeach
@endsection
