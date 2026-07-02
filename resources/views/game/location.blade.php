@extends('layouts.app')
@section('content')
@php
    $storyText = $state === 'completed' ? null : $location->story;
    $locationImage = $state === 'completed'
        ? (($location->completed_image_path ?? null) ?: $location->story_image_path ?: $location->image_path ?: $location->svg_completed)
        : ($location->story_image_path ?: $location->image_path ?: $location->svg_available);
    $shownLocationPrestige = $effectiveLocationPrestige ?? $location->reward_prestige;
@endphp

<div class="panel">
    <h1>Úkol {{ $location->name }}</h1>
    <div class="reward-compact">
        <strong>{{ $state === 'completed' ? 'Získaná odměna' : 'Odměna za dokončení stanoviště' }}</strong>
        <div><b>{{ $shownLocationPrestige }}</b> prestiž</div>
        <div><b>{{ $location->reward_resources }}</b> surovin</div>
        <div><b>{{ $location->reward_colony_level }}</b> úroveň kolonie</div>
    </div>

    @if($state === 'completed')
        @php($completedPdf = $tasks->first(fn($task) => filled($task->pdf_path ?? null)))
        @if($completedPdf)
            <p><a class="btn" href="{{ $completedPdf->pdf_path }}" download>Stáhnout PDF se zadáním</a></p>
        @endif
    @else
        @if($locationImage)
            <p><img src="{{ $locationImage }}" alt="" style="max-width:100%;width:100%;border-radius:8px"></p>
        @endif

        @if($storyText)
            <p>{!! nl2br(e($storyText)) !!}</p>
        @endif
    @endif
</div>

@if($state !== 'completed')
    <h2 class="task-list-title">Úkoly</h2>
    @foreach($tasks as $task)
        @php($taskCompleted = ($progress[$task->id] ?? '') === 'completed')
        <div class="card" style="margin-top:12px">
            <h3>{{ $task->title }} @if($taskCompleted) ✓ @endif</h3>
            <p>{!! nl2br(e($task->body)) !!}</p>

            @if($task->pdf_path)
                @if(($task->pdf_intro ?? null) && trim((string) $task->pdf_intro) !== trim((string) $task->body))
                    <p class="small">{!! nl2br(e($task->pdf_intro)) !!}</p>
                @endif
                <p><a class="btn" href="{{ $task->pdf_path }}" download>Stáhnout PDF se zadáním</a></p>
            @endif

            @unless($taskCompleted)
                <form method="post" action="/tasks/{{ $task->id }}">
                    @csrf
                    @if($task->type !== 'info')
                        <label>Odpověď</label>
                        <input name="answer">
                    @endif
                    <p><button class="primary">Odeslat</button></p>
                </form>
            @endunless

            @php($visibleHints = ($hints[$task->id] ?? collect())->filter(fn($hint) => trim((string) $hint->text) !== ''))
            @if($visibleHints->isNotEmpty())
                <h3>Nápovědy</h3>
                @foreach($visibleHints as $hint)
                    @if(in_array($hint->id, $purchased))
                        <p class="small hint-revealed">{{ $hint->text }}</p>
                    @else
                        <form method="post" action="/hints/{{ $hint->id }}/buy" class="inline" onsubmit="return confirm('Použití nápovědy sníží maximální prestiž za celé stanoviště.\n\nOpravdu chceš zobrazit nápovědu?');">
                            @csrf
                            <button>Nápověda</button>
                        </form>
                    @endif
                @endforeach
            @endif

            <p><a href="/zpravy?subject=Pomoc:%20{{ urlencode($location->name) }}">Požádat admina o pomoc</a></p>
        </div>
    @endforeach
@endif
@endsection
