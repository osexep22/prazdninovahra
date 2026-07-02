@extends('layouts.app')
@section('content')
@php
    $completionStory = session('completion_story');
    $storyText = $state === 'completed' && filled($location->story_completed ?? null)
        ? $location->story_completed
        : $location->story;
    $locationImage = $state === 'completed'
        ? (($location->completed_image_path ?? null) ?: $location->story_image_path ?: $location->image_path ?: $location->svg_completed)
        : ($location->story_image_path ?: $location->image_path ?: $location->svg_available);
@endphp

@if($completionStory)
    <div class="modal-backdrop">
        <div class="modal-window story-window">
            <h2>{{ $completionStory['title'] ?? 'Stanoviště splněno' }}</h2>
            @if(! empty($completionStory['image']))
                <img src="{{ $completionStory['image'] }}" alt="">
            @endif
            @if(! empty($completionStory['body']))
                <p>{!! nl2br(e($completionStory['body'])) !!}</p>
            @endif
            <p><button class="primary" type="button" onclick="this.closest('.modal-backdrop').remove()">Pokračovat</button></p>
        </div>
    </div>
@endif

<div class="panel">
    <h1>{{ $location->name }}</h1>
    <div class="reward-compact">
        <strong>Odměna</strong>
        <div><b>{{ $location->reward_prestige }}</b> prestiž</div>
        <div><b>{{ $location->reward_resources }}</b> surovin</div>
        <div><b>{{ $location->reward_colony_level }}</b> úroveň kolonie</div>
    </div>

    @if($locationImage)
        <p><img src="{{ $locationImage }}" alt="" style="max-width:100%;width:100%;border-radius:8px"></p>
    @endif

    @if($storyText)
        <p>{!! nl2br(e($storyText)) !!}</p>
    @endif
</div>

<h2 class="task-list-title">Úkoly</h2>
@foreach($tasks as $task)
    @php
        $taskCompleted = ($progress[$task->id] ?? '') === 'completed';
        $hintUsed = in_array($task->id, $hintedTasks ?? [], true);
        $effectivePrestige = $hintUsed ? (int) floor($task->reward_prestige / 2) : (int) $task->reward_prestige;
    @endphp
    <div class="card" style="margin-top:12px">
        <h3>{{ $task->title }} @if($taskCompleted) ✓ @endif</h3>
        <div class="task-reward-row">
            <span>Odměna za úkol:</span>
            @if($hintUsed)
                <span class="prestige-reduced"><s>{{ $task->reward_prestige }}</s> <b>{{ $effectivePrestige }}</b> prestiž</span>
                <span>{{ $task->reward_resources }} surovin</span>
            @else
                <span><b>{{ $task->reward_prestige }}</b> prestiž</span>
                <span>{{ $task->reward_resources }} surovin</span>
            @endif
        </div>
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

        @php $visibleHints = ($hints[$task->id] ?? collect())->filter(fn($hint) => trim((string) $hint->text) !== ''); @endphp
        @if($visibleHints->isNotEmpty())
        <h3>Nápovědy</h3>
        @foreach($visibleHints as $hint)
            @if(in_array($hint->id, $purchased))
                <p class="small hint-revealed">{{ $hint->text }}</p>
            @else
                <form method="post" action="/hints/{{ $hint->id }}/buy" class="inline" onsubmit="return confirm('Použití nápovědy sníží maximální prestiž získanou za tento úkol na polovinu.\n\nOpravdu chceš zobrazit nápovědu?');">
                    @csrf
                    <button>Nápověda</button>
                </form>
            @endif
        @endforeach
        @endif

        <p><a href="/zpravy?subject=Pomoc:%20{{ urlencode($location->name) }}">Požádat admina o pomoc</a></p>
    </div>
@endforeach
@endsection
