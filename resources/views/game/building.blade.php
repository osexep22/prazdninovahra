@extends('layouts.app')
@section('content')
@php
    $customizationConfig = $customizationConfig ?? [];
    $isAdminPreview = $isAdminPreview ?? false;
    $previewPlayer = $previewPlayer ?? null;
    $unlocked = $unlocks->whereIn('id', $userUnlocks);
    $detailText = trim((string) (($building->detail_text ?? null) ?: $building->description));
@endphp

<div class="building-detail-shell">
    @if($isAdminPreview)
        <div class="panel">
            @if($previewPlayer)
                <b>Admin náhled hráče {{ $previewPlayer->display_name }}</b>
                <p class="muted">Zobrazuje se skutečný stav této budovy pro vybraného hráče: splněné úkoly, odemčené úpravy a uložený vzhled. V náhledu nejde nic odesílat ani měnit.</p>
            @else
                <b>Obsahový náhled budovy</b>
                <p class="muted">Tento náhled slouží ke kontrole textů a úkolů. Nepředstírá postup konkrétního hráče, proto v něm nejsou odemčené hráčské úpravy vzhledu.</p>
            @endif
        </div>
    @endif

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
                    @if(!$isAdminPreview && ($progress[$task->id] ?? '') !== 'completed')
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
                @if($isAdminPreview)
                    <div class="grid">
                        @foreach($unlocked as $unlock)
                            <div class="stat">
                                <b>{{ $unlock->label }}</b><br>
                                <span class="small muted">{{ $unlock->type === 'color' ? 'Barva' : 'Varianta' }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                <form method="post" action="/buildings/{{ $building->id }}/customization">
                    @csrf
                    <div class="grid">
                        @foreach($unlocked as $unlock)
                            @php
                                $savedColor = data_get($customizationConfig, 'colors.' . $unlock->key);
                                $access = $customizationAccess[$unlock->id] ?? ['mode' => 'full', 'palette' => []];
                                $palette = collect($access['palette'] ?? []);
                                $defaultPaletteColor = $palette->first()['value'] ?? '#ffffff';
                                $options = collect(json_decode($unlock->options, true) ?? [])->map(function ($option) {
                                    return is_array($option)
                                        ? ['value' => $option['value'] ?? '', 'label' => $option['label'] ?? ($option['value'] ?? '')]
                                        : ['value' => $option, 'label' => $option];
                                })->filter(fn ($option) => $option['value'] !== '')->values();
                            @endphp
                            <div>
                                <label>{{ $unlock->label }}</label>
                                @if($unlock->type === 'color')
                                    @if(($access['mode'] ?? 'full') === 'basic' && $palette->isNotEmpty())
                                        <select class="custom-control" data-kind="color" data-key="{{ $unlock->key }}" data-saved="1" data-apply-default="1" name="colors[{{ $unlock->key }}]">
                                            @foreach($palette as $color)
                                                <option value="{{ $color['value'] }}" @selected(($savedColor ?: $defaultPaletteColor) === $color['value'])>{{ $color['label'] }}</option>
                                            @endforeach
                                        </select>
                                        <p class="small muted">Další odstíny odemkne druhý úkol.</p>
                                    @else
                                        <input class="custom-control" data-kind="color" data-key="{{ $unlock->key }}" data-saved="{{ $savedColor ? '1' : '0' }}" type="color" name="colors[{{ $unlock->key }}]" value="{{ $savedColor ?: '#ffffff' }}">
                                    @endif
                                @elseif($unlock->type === 'variant')
                                    <select class="custom-control" data-kind="variant" data-key="{{ $unlock->key }}" name="variants[{{ $unlock->key }}]">
                                        @foreach($options as $option)
                                            <option value="{{ $option['value'] }}" @selected(data_get($customizationConfig, 'variants.' . $unlock->key) === $option['value'])>{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                @elseif($unlock->type === 'pattern')
                                    <select class="custom-control" data-kind="pattern" data-key="{{ $unlock->key }}" name="patterns[{{ $unlock->key }}]">
                                        @foreach($options as $option)
                                            <option value="{{ $option['value'] }}" @selected(data_get($customizationConfig, 'patterns.' . $unlock->key) === $option['value'])>{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <p><button class="primary">Uložit vzhled</button></p>
                </form>
                @endif
            @endif
        </div>
    </section>
</div>

<script>
    const preview = document.getElementById('svg-preview');
    const setSvgFill = (target, value) => {
        const paint = (element) => {
            element.setAttribute('fill', value);
            element.style.fill = value;
        };
        paint(target);
        target.querySelectorAll('*').forEach(element => {
            const inlineStyle = element.getAttribute('style') || '';
            if (element.hasAttribute('fill') || inlineStyle.includes('fill:')) {
                paint(element);
            }
        });
    };
    const applyCustomization = () => {
        document.querySelectorAll('.custom-control').forEach(control => {
            const kind = control.dataset.kind;
            const key = control.dataset.key;
            if (kind === 'color') {
                if (control.dataset.saved === '1' || control.dataset.dirty === '1' || control.dataset.applyDefault === '1') {
                    preview.style.setProperty(`--${key}`, control.value);
                    const target = preview.querySelector('#edit_color__' + CSS.escape(key));
                    if (target) setSvgFill(target, control.value);
                }
            }
            if (kind === 'variant') {
                const values = Array.from(control.options).map(option => option.value).filter(value => value !== '__off');
                values.forEach(value => {
                    const target = preview.querySelector('#' + CSS.escape(value));
                    if (target) target.style.display = 'none';
                });
                if (control.value !== '__off') {
                    const target = preview.querySelector('#' + CSS.escape(control.value));
                    if (target) target.style.display = 'inline';
                }
            }
            if (kind === 'pattern') {
                const values = Array.from(control.options).map(option => option.value).filter(value => value !== '__off');
                values.forEach(value => {
                    const target = preview.querySelector('#' + CSS.escape(value));
                    if (target) target.style.display = 'none';
                });
                if (control.value !== '__off') {
                    const target = preview.querySelector('#' + CSS.escape(control.value));
                    if (target) target.style.display = 'inline';
                }
            }
        });
    };

    fetch(preview.dataset.src)
        .then(response => response.text())
        .then(svg => {
            preview.innerHTML = svg;
            document.querySelectorAll('.custom-control').forEach(control => control.addEventListener('input', () => {
                control.dataset.dirty = '1';
                applyCustomization();
            }));
            applyCustomization();
        });

    document.querySelector('form[action="/buildings/{{ $building->id }}/customization"]')?.addEventListener('submit', () => {
        document.querySelectorAll('.custom-control[data-kind="color"]').forEach(control => {
            if (control.dataset.saved !== '1' && control.dataset.dirty !== '1') {
                control.disabled = true;
            }
        });
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
