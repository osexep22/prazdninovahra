@extends('layouts.app')
@section('content')
@php
    $customizationConfig = $customizationConfig ?? [];
    $unlocked = $unlocks->whereIn('id', $userUnlocks);
    $detailText = trim((string) (($building->detail_text ?? null) ?: $building->description));
@endphp

<div class="building-detail-shell">
    <section class="building-hero panel">
        <div>
            <h1>{{ $building->name }}</h1>
            <p>{{ $detailText ?: 'Tahle místnost je součástí tvého mraveniště. Plněním jejích úkolů můžeš odemykat nové možnosti vzhledu.' }}</p>
        </div>
        <div id="svg-preview" class="building-preview building-preview-large" data-src="{{ $building->svg_asset_path }}"></div>
    </section>

    <section class="building-tabs panel">
        <div class="building-tab-switch" role="tablist" aria-label="Režim detailu budovy">
            <button type="button" class="active" data-building-tab="tasks">Úkoly</button>
            <button type="button" data-building-tab="customization">Úprava vzhledu</button>
        </div>

        <div class="building-tab-panel active" data-building-panel="tasks">
            <h2>Úkoly</h2>
            @forelse($tasks as $task)
                <article class="card compact-task-card">
                    <div class="row">
                        <h3>{{ $task->title }} @if(($progress[$task->id] ?? '') === 'completed') <span class="completed-mark">✓ Splněno</span> @endif</h3>
                        <span class="reward-pill">+{{ $task->reward_prestige }} prestiže</span>
                    </div>
                    <p>{!! nl2br(e($task->body)) !!}</p>
                    @if(($task->unlock_description ?? null) || ($task->unlock_key ?? null))
                        <p class="small muted">Odemkne: {{ $task->unlock_description ?: 'novou možnost vzhledu této místnosti' }}</p>
                    @endif
                    @if($task->pdf_path ?? null)
                        <p><a class="btn" href="{{ $task->pdf_path }}" download>Stáhnout PDF k podúkolu</a></p>
                    @endif
                    @if(($progress[$task->id] ?? '') !== 'completed')
                        <form method="post" action="/building-tasks/{{ $task->id }}">
                            @csrf
                            <label>Kód</label>
                            <input name="answer">
                            <p><button class="primary">Odeslat</button></p>
                        </form>
                    @endif
                </article>
            @empty
                <p class="muted">V této místnosti zatím nejsou žádné doplňkové úkoly.</p>
            @endforelse
        </div>

        <div class="building-tab-panel" data-building-panel="customization">
            <h2>Úprava vzhledu</h2>
            <p class="muted">Další možnosti vzhledu odemkneš plněním doplňkových úkolů v jednotlivých místnostech Mraveniště.</p>
            @if($unlocked->isEmpty())
                <p>Zatím nemáš odemčenou žádnou úpravu vzhledu pro tuto místnost.</p>
            @else
                <form method="post" action="/buildings/{{ $building->id }}/customization">
                    @csrf
                    <div class="grid">
                        @foreach($unlocked as $unlock)
                            <div>
                                <label>{{ $unlock->label }}</label>
                                @if($unlock->type === 'color')
                                    <input class="custom-control" data-kind="color" data-key="{{ $unlock->key }}" type="color" name="colors[{{ $unlock->key }}]" value="{{ data_get($customizationConfig, 'colors.' . $unlock->key, '#b93535') }}">
                                @elseif($unlock->type === 'variant')
                                    <select class="custom-control" data-kind="variant" data-key="{{ $unlock->key }}" name="variants[{{ $unlock->key }}]">
                                        @foreach(json_decode($unlock->options, true) ?? [] as $option)
                                            <option value="{{ $option }}" @selected(data_get($customizationConfig, 'variants.' . $unlock->key) === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <p><button class="primary">Uložit vzhled</button></p>
                </form>
            @endif
        </div>
    </section>
</div>

<script>
    const preview = document.getElementById('svg-preview');
    const applyCustomization = () => {
        document.querySelectorAll('.custom-control').forEach(control => {
            const kind = control.dataset.kind;
            const key = control.dataset.key;
            if (kind === 'color') {
                preview.style.setProperty(`--${key}`, control.value);
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

    fetch(preview.dataset.src)
        .then(response => response.text())
        .then(svg => {
            preview.innerHTML = svg;
            document.querySelectorAll('.custom-control').forEach(control => control.addEventListener('input', applyCustomization));
            applyCustomization();
        });

    document.querySelectorAll('[data-building-tab]').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-building-tab]').forEach(tab => tab.classList.toggle('active', tab === button));
            document.querySelectorAll('[data-building-panel]').forEach(panel => {
                panel.classList.toggle('active', panel.dataset.buildingPanel === button.dataset.buildingTab);
            });
        });
    });
</script>
@endsection
