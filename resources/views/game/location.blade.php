@extends('layouts.app')
@section('content')
<div class="panel">
    <h1>{{ $location->name }}</h1>
    <div class="panel" style="background:#fff8e6;margin-bottom:14px">
        <h2>Odmena</h2>
        <div class="stats">
            <div class="stat"><b>{{ $location->reward_prestige }}</b><br>prestiz</div>
            <div class="stat"><b>{{ $location->reward_resources }}</b><br>suroviny</div>
            <div class="stat"><b>{{ $location->reward_colony_level }}</b><br>level kolonie</div>
        </div>
    </div>
    @php $locationImage = $location->story_image_path ?: $location->image_path ?: ($state === 'completed' ? $location->svg_completed : $location->svg_available); @endphp
    @if($locationImage)
        <p><img src="{{ $locationImage }}" alt="" style="max-width:100%;width:100%;border-radius:8px"></p>
    @endif
    @if($location->story)<p>{!! nl2br(e($location->story)) !!}</p>@endif
</div>
<h2>Úkoly</h2>
@foreach($tasks as $task)
    <div class="card" style="margin-top:12px">
        <h3>{{ $task->title }} @if(($progress[$task->id] ?? '') === 'completed') ✓ @endif</h3>
        <p>{!! nl2br(e($task->body)) !!}</p>
        @if($task->pdf_path)
            <p><a class="btn" href="{{ $task->pdf_path }}" download>Stáhnout PDF se zadáním</a></p>
        @endif
        @if(($progress[$task->id] ?? '') !== 'completed')
            <form method="post" action="/tasks/{{ $task->id }}">
                @csrf
                @if($task->type !== 'info')<label>Odpověď</label><input name="answer">@endif
                <p><button class="primary">Odeslat</button></p>
            </form>
        @endif
        <h3>Nápovědy</h3>
        @foreach(($hints[$task->id] ?? collect()) as $hint)
            @if(in_array($hint->id, $purchased))
                <p class="small">{{ $hint->text }}</p>
            @else
                <form method="post" action="/hints/{{ $hint->id }}/buy" class="inline">@csrf<button>Koupit za {{ $hint->cost_resources }}</button></form>
            @endif
        @endforeach
        <p><a href="/zpravy?subject=Pomoc:%20{{ urlencode($location->name) }}">Požádat admina o pomoc</a></p>
    </div>
@endforeach
@endsection
