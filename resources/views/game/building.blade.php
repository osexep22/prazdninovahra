@extends('layouts.app')
@section('content')
@php
    $customizationConfig = $customizationConfig ?? [];
    $isAdminPreview = $isAdminPreview ?? false;
    $previewPlayer = $previewPlayer ?? null;
    $unlocked = $unlocks->whereIn('id', $userUnlocks);
    $detailText = trim((string) (($building->detail_text ?? null) ?: $building->description));
@endphp
<style>
    .customization-list { display:grid; gap:12px; max-width:620px; }
    .customization-control-row { border:1px solid var(--line); border-radius:8px; background:#fbfdff; padding:12px; }
    .customization-control-row[hidden] { display:none !important; }
    .customization-form-grid { display:grid; gap:12px; max-width:620px; }
    .color-picker-row { display:flex; align-items:center; gap:10px; min-height:42px; }
    .color-picker-button { position:relative; display:inline-flex; align-items:center; cursor:pointer; }
    .color-picker-button input[type="color"], .color-picker-button select {
        position:absolute;
        inset:0;
        width:100%;
        height:100%;
        opacity:0;
        cursor:pointer;
    }
    .color-swatch { width:82px; height:38px; border-radius:8px; border:2px solid rgba(23,32,51,.24); box-shadow:inset 0 0 0 1px rgba(255,255,255,.48), 0 5px 12px rgba(23,32,51,.12); background:var(--swatch-color, #ffffff); }
    .color-picker-button:focus-within .color-swatch { outline:3px solid rgba(45,126,84,.25); outline-offset:2px; }
</style>

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
                    <div class="customization-list">
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
                    <div class="customization-form-grid">
                        @foreach($unlocked as $unlock)
                            @php
                                $savedColor = data_get($customizationConfig, 'colors.' . $unlock->key);
                                $access = $customizationAccess[$unlock->id] ?? ['mode' => 'full', 'palette' => []];
                                $palette = collect($access['palette'] ?? []);
                                $defaultPaletteColor = $palette->first()['value'] ?? '#ffffff';
                                $dependsOnVariantKey = null;
                                $dependsOnVariantValue = null;
                                if ($unlock->type === 'color' && preg_match('/^6__lustr__(\d+)$/', (string) $unlock->key, $lustrColorMatch)) {
                                    $dependsOnVariantKey = '6__lustr';
                                    $dependsOnVariantValue = 'edit_variant__6__lustr__' . $lustrColorMatch[1];
                                }
                                $options = collect(json_decode($unlock->options, true) ?? [])->map(function ($option) {
                                    return is_array($option)
                                        ? ['value' => $option['value'] ?? '', 'label' => $option['label'] ?? ($option['value'] ?? '')]
                                        : ['value' => $option, 'label' => $option];
                                })->filter(fn ($option) => $option['value'] !== '')->values();
                                if (!empty($access['allowed_values'])) {
                                    $options = $options->whereIn('value', $access['allowed_values'])->values();
                                }
                                if ($unlock->type === 'variant' && $options->count() === 1 && $options->first()['value'] !== '__off') {
                                    $options = collect([['value' => '__off', 'label' => 'Vypnuto']])->merge($options)->values();
                                }
                            @endphp
                            <div class="customization-control-row" @if($dependsOnVariantKey) data-depends-variant-key="{{ $dependsOnVariantKey }}" data-depends-variant-value="{{ $dependsOnVariantValue }}" @endif>
                                <label>{{ $unlock->label }}</label>
                                @if($unlock->type === 'color')
                                    @if(($access['mode'] ?? 'full') === 'basic' && $palette->isNotEmpty())
                                        <div class="color-picker-row">
                                            <span class="color-picker-button" title="Změnit barvu">
                                                <span class="color-swatch" style="--swatch-color: {{ $savedColor ?: $defaultPaletteColor }}"></span>
                                                <select class="custom-control" data-kind="color" data-key="{{ $unlock->key }}" data-saved="1" data-apply-default="1" name="colors[{{ $unlock->key }}]" aria-label="{{ $unlock->label }}">
                                                    @foreach($palette as $color)
                                                        <option value="{{ $color['value'] }}" @selected(($savedColor ?: $defaultPaletteColor) === $color['value'])>{{ $color['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            </span>
                                        </div>
                                        <p class="small muted">Další odstíny odemkne druhý úkol.</p>
                                    @else
                                        <div class="color-picker-row">
                                            <span class="color-picker-button" title="Změnit barvu">
                                                <span class="color-swatch" style="--swatch-color: {{ $savedColor ?: '#ffffff' }}"></span>
                                                <input class="custom-control" data-kind="color" data-key="{{ $unlock->key }}" data-saved="{{ $savedColor ? '1' : '0' }}" type="color" name="colors[{{ $unlock->key }}]" value="{{ $savedColor ?: '#ffffff' }}" aria-label="{{ $unlock->label }}">
                                            </span>
                                        </div>
                                    @endif
                                @elseif($unlock->type === 'variant')
                                    <select class="custom-control" data-kind="variant" data-key="{{ $unlock->key }}" name="variants[{{ $unlock->key }}]">
                                        @foreach($options as $option)
                                            <option value="{{ $option['value'] }}" @selected((data_get($customizationConfig, 'variants.' . $unlock->key) ?? '__off') === $option['value'])>{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                @elseif($unlock->type === 'pattern')
                                    <select class="custom-control" data-kind="pattern" data-key="{{ $unlock->key }}" name="patterns[{{ $unlock->key }}]">
                                        @foreach($options as $option)
                                            <option value="{{ $option['value'] }}" @selected((data_get($customizationConfig, 'patterns.' . $unlock->key) ?? '__off') === $option['value'])>{{ $option['label'] }}</option>
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
    const hideOptionalSvgParts = (root) => {
        root.querySelectorAll('[id^="edit_variant__"], [id^="edit_pattern__"]').forEach(target => {
            target.style.display = 'none';
        });
    };
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
    const normalizeHexColor = (value) => {
        if (!value) return null;
        const trimmed = value.trim();
        const hex = trimmed.match(/^#([0-9a-f]{6})$/i);
        if (hex) return `#${hex[1].toLowerCase()}`;
        const shortHex = trimmed.match(/^#([0-9a-f]{3})$/i);
        if (shortHex) {
            return `#${shortHex[1].split('').map(part => part + part).join('').toLowerCase()}`;
        }
        const rgb = trimmed.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
        if (rgb) {
            return '#' + [rgb[1], rgb[2], rgb[3]].map(part => Math.max(0, Math.min(255, Number(part))).toString(16).padStart(2, '0')).join('');
        }
        return null;
    };
    const readSvgFill = (target) => {
        const candidates = [target, ...target.querySelectorAll('*')];
        for (const element of candidates) {
            const inlineStyle = element.getAttribute('style') || '';
            const styleFill = inlineStyle.match(/(?:^|;)\s*fill\s*:\s*([^;]+)/i)?.[1];
            const fill = normalizeHexColor(element.getAttribute('fill')) || normalizeHexColor(styleFill);
            if (fill && fill !== '#000000' && fill !== 'none') {
                return fill;
            }
        }
        return null;
    };
    const updateColorSwatch = (control) => {
        const row = control.closest('.color-picker-row');
        if (!row) return;
        row.querySelector('.color-swatch')?.style.setProperty('--swatch-color', control.value);
    };
    const syncInitialColorControls = () => {
        document.querySelectorAll('.custom-control[data-kind="color"]').forEach(control => {
            if (control.dataset.saved === '1' || control.dataset.applyDefault === '1') {
                updateColorSwatch(control);
                return;
            }
            const target = preview.querySelector('#edit_color__' + CSS.escape(control.dataset.key));
            const svgFill = target ? readSvgFill(target) : null;
            if (svgFill) {
                control.value = svgFill;
            }
            updateColorSwatch(control);
        });
    };
    const updateDependentControls = () => {
        document.querySelectorAll('[data-depends-variant-key]').forEach(row => {
            const variantControl = Array.from(document.querySelectorAll('.custom-control[data-kind="variant"]'))
                .find(control => control.dataset.key === row.dataset.dependsVariantKey);
            const visible = variantControl && variantControl.value === row.dataset.dependsVariantValue;
            row.hidden = !visible;
            row.querySelectorAll('.custom-control').forEach(control => {
                control.disabled = !visible;
            });
        });
    };
    const applyCustomization = () => {
        updateDependentControls();
        document.querySelectorAll('.custom-control').forEach(control => {
            if (control.disabled) return;
            const kind = control.dataset.kind;
            const key = control.dataset.key;
            if (kind === 'color') {
                if (control.dataset.saved === '1' || control.dataset.dirty === '1' || control.dataset.applyDefault === '1') {
                    updateColorSwatch(control);
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
            hideOptionalSvgParts(preview);
            document.querySelectorAll('.custom-control').forEach(control => control.addEventListener('input', () => {
                control.dataset.dirty = '1';
                updateColorSwatch(control);
                applyCustomization();
            }));
            syncInitialColorControls();
            updateDependentControls();
            applyCustomization();
        });

    document.querySelector('form[action="/buildings/{{ $building->id }}/customization"]')?.addEventListener('submit', () => {
        document.querySelectorAll('.custom-control[data-kind="color"]').forEach(control => {
            if (control.disabled || (control.dataset.saved !== '1' && control.dataset.dirty !== '1' && control.dataset.applyDefault !== '1')) {
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
