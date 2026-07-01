@extends('layouts.app')
@section('content')
<div class="panel">
    <h1>Tudy zatím cesta nevede</h1>
    <p>{{ $exception->getMessage() ?: 'Na tuhle část hry zatím nemáš přístup.' }}</p>
    <p><a class="btn primary" href="/palouk">Zpět na Palouk</a></p>
</div>
@endsection
