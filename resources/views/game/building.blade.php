@extends('layouts.app')
@section('content')
<div class="panel">
    <h1>{{ $building->name }}</h1>
    <div id="svg-preview" data-src="{{ $building->svg_asset_path }}" style="max-width:360px;width:100%"></div>
    <p>
        @if($completed === 0) Komora se teprve probouzi.
        @elseif($completed < $tasks->count()) Komůrka už má první vylepšení a čeká na další práci.
        @else Komora je ve sve prvni prototypove sile.
        @endif
    </p>
</div>
<h2>Speciální úkoly</h2>
@foreach($tasks as $task)
    <div class="card" style="margin-top:12px">
        <h3>{{ $task->title }} @if(($progress[$task->id] ?? '') === 'completed') ✓ @endif</h3>
        <p>{!! nl2br(e($task->body)) !!}</p>
        @if($task->pdf_path ?? null)
            <p><a class="btn" href="{{ $task->pdf_path }}" download>Stáhnout PDF k podúkolu</a></p>
        @endif
        @if(($progress[$task->id] ?? '') !== 'completed')
            <form method="post" action="/building-tasks/{{ $task->id }}">@csrf<label>Kód</label><input name="answer"><p><button class="primary">Odeslat</button></p></form>
        @endif
    </div>
@endforeach
<h2>Upravy vzhledu</h2>
<div class="panel">
    <form method="post" action="/buildings/{{ $building->id }}/customization">
        @csrf
        @foreach($unlocks as $unlock)
            @if(in_array($unlock->id, $userUnlocks))
                <label>{{ $unlock->label }}</label>
                @if($unlock->type === 'color')
                    <input class="custom-control" data-kind="color" data-key="{{ $unlock->key }}" type="color" name="colors[{{ $unlock->key }}]" value="#b93535">
                @elseif($unlock->type === 'variant')
                    <select class="custom-control" data-kind="variant" data-key="{{ $unlock->key }}" name="variants[{{ $unlock->key }}]">
                        @foreach(json_decode($unlock->options, true) ?? [] as $option)<option>{{ $option }}</option>@endforeach
                    </select>
                @endif
            @endif
        @endforeach
        <p><button class="primary">Uložit vzhled</button></p>
    </form>
</div>
<script>
    const preview = document.getElementById('svg-preview');
    fetch(preview.dataset.src)
        .then(response => response.text())
        .then(svg => {
            preview.innerHTML = svg;
            const update = () => {
                document.querySelectorAll('.custom-control').forEach(control => {
                    const kind = control.dataset.kind;
                    const key = control.dataset.key;
                    if (kind === 'color') {
                        const target = preview.querySelector('#edit_color__' + key);
                        if (target) target.setAttribute('fill', control.value);
                    }
                    if (kind === 'variant') {
                        preview.querySelectorAll('[id^="edit_variant__' + key + '__"]').forEach(el => el.style.opacity = '0');
                        const target = preview.querySelector('#edit_variant__' + key + '__' + control.value);
                        if (target) target.style.opacity = '1';
                    }
                });
            };
            document.querySelectorAll('.custom-control').forEach(control => control.addEventListener('input', update));
            update();
        });
</script>
@endsection
