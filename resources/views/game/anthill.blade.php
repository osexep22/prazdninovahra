@extends('layouts.app')
@section('content')
<div id="floating-tooltip" class="floating-tooltip"></div>
<div class="anthill-scene">
    <div class="anthill-title">
        <h1>{{ ($readonly ?? false) ? 'Mraveniště: ' . $owner->display_name : 'Mraveniště' }}</h1>
        <button class="title-help" type="button" aria-label="Co je mraveniště?">?</button>
        <p>V Mraveništi najdeš jednotlivé místnosti s doplňkovými úkoly. Jejich plněním získáš nové barvy a další kosmetické úpravy svého mraveniště.</p>
        @if($readonly ?? false)
            <p class="muted">Nahlížíš na mraveniště přítele. Je jen pro čtení.</p>
        @endif
    </div>

    <div class="anthill-board" style="--anthill-variant:url('{{ $anthillVariant }}'); --anthill-scale:{{ $anthillScale ?? 1 }};">
        <div class="anthill-map">
            @foreach($slots as $slot)
                @php
                    $isOwned = in_array($slot->id, $ownedSlots);
                    $placedBuilding = $placed[$slot->id] ?? null;
                    $locked = $slot->required_colony_level > auth()->user()->colony_level;
                    $placedConfig = $placedBuilding ? (json_decode((string) ($placedBuilding->customization_json ?? ''), true) ?: []) : [];
                    $placedColors = json_encode($placedConfig['colors'] ?? []);
                    $placedVariants = json_encode($placedConfig['variants'] ?? []);
                @endphp
                @if(!$locked)
                    <div class="room slot"
                        data-description="{{ $placedBuilding ? (($placedBuilding->tooltip ?? null) ?: $placedBuilding->name . ' už je připravená na práci.') : ($isOwned ? 'V této komůrce zatím nesídlí žádný mravenec. Za nasbírané suroviny zde můžeš vybudovat nové zázemí.' : 'Tahle komůrka půjde koupit za ' . $slot->cost_resources . ' surovin.') }}"
                        @if(!$placedBuilding && !($readonly ?? false)) data-modal="slot-modal-{{ $slot->id }}" tabindex="0" role="button" @endif
                        style="left:{{ $slot->layout_x }}%; top:{{ $slot->layout_y }}%; width:{{ $slot->layout_w ?? 12 }}%; height:{{ $slot->layout_h ?? 12 }}%;">
                        @if($placedBuilding)
                            @if($readonly ?? false)
                                <span class="room-svg" role="img" aria-label="{{ $placedBuilding->name }}" data-src="{{ $placedBuilding->svg_asset_path }}" data-colors='{{ $placedColors }}' data-variants='{{ $placedVariants }}'></span>
                            @else
                                <a href="/budovy/{{ $placedBuilding->slug }}" aria-label="{{ $placedBuilding->name }}"><span class="room-svg" data-src="{{ $placedBuilding->svg_asset_path }}" data-colors='{{ $placedColors }}' data-variants='{{ $placedVariants }}'></span></a>
                            @endif
                        @elseif($isOwned)
                            <img src="/assets/game/rooms/prazdna-mistnost.svg" alt="Prázdná komůrka">
                        @else
                            <img src="/assets/placeholders/slot-available.svg" alt="Dostupná komůrka">
                        @endif
                    </div>
                    @if(!$placedBuilding && !($readonly ?? false))
                        <div class="action-modal" id="slot-modal-{{ $slot->id }}" aria-hidden="true">
                            <div class="modal-window">
                                <h2>Prázdná komůrka</h2>
                                @if($isOwned)
                                    <p>V této komůrce zatím nesídlí žádný mravenec. Za nasbírané suroviny zde můžeš vybudovat nové zázemí a pozvat dalšího obyvatele Mraveniště.</p>
                                    <p class="small muted">Další komůrky odemkneš postupným plněním hlavního příběhu.</p>
                                    @if(count($ownedBuildingIds) < $buildings->count())
                                        <form method="post" action="/mraveniste/build">
                                            @csrf
                                            <input type="hidden" name="slot_id" value="{{ $slot->id }}">
                                            <label>Budova</label>
                                            <select name="building_id">
                                                @foreach($buildings as $building)
                                                    @if(!in_array($building->id, $ownedBuildingIds))
                                                        <option value="{{ $building->id }}">{{ $building->name }} ({{ $building->cost_resources }} surovin)</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <p class="row"><button class="primary">Postavit</button><button type="button" data-close-modal>Zavřít</button></p>
                                        </form>
                                    @else
                                        <p>Všechny typy budov už jsou postavené.</p>
                                        <p><button type="button" data-close-modal>Zavřít</button></p>
                                    @endif
                                @else
                                    <p>Komůrka stojí <b>{{ $slot->cost_resources }}</b> surovin. Po koupi na ní půjde postavit jedna budova.</p>
                                    <form method="post" action="/mraveniste/slots/{{ $slot->id }}/buy">
                                        @csrf
                                        <p class="row"><button class="primary">Koupit komůrku</button><button type="button" data-close-modal>Zavřít</button></p>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
            @endforeach
        </div>
    </div>

    @if(!($readonly ?? false))
        <div class="anthill-economy-panel">
            <b>Kapacita: {{ $capacity ?? 3 }} komůrek</b>
            @if(!empty($availableExpansions))
                <div class="row">
                    @foreach($availableExpansions as $target)
                        <form method="post" action="/mraveniste/rozsireni/{{ $target }}" class="inline">
                            @csrf
                            <button type="submit">Rozšířit na {{ $target }} za {{ $expansionCosts[$target] ?? 0 }} surovin</button>
                        </form>
                    @endforeach
                </div>
            @else
                <span class="small muted">Mraveniště má největší dostupné rozšíření.</span>
            @endif
        </div>
    @endif
</div>
<script>
    const anthillInfo = document.getElementById('floating-tooltip');
    const moveAnthillTooltip = (event) => {
        const width = anthillInfo.offsetWidth || 300;
        anthillInfo.style.left = `${Math.min(event.clientX + 14, window.innerWidth - width - 12)}px`;
        anthillInfo.style.top = `${Math.max(12, event.clientY - 16)}px`;
    };
    document.querySelectorAll('.room').forEach(room => {
        room.addEventListener('mouseenter', (event) => {
            anthillInfo.replaceChildren();
            const text = document.createElement('p');
            text.textContent = room.dataset.description || '';
            anthillInfo.appendChild(text);
            anthillInfo.classList.add('visible');
            moveAnthillTooltip(event);
        });
        room.addEventListener('mousemove', moveAnthillTooltip);
        room.addEventListener('mouseleave', () => anthillInfo.classList.remove('visible'));
        room.addEventListener('click', (event) => {
            if (event.target.closest('a')) return;
            const modal = document.getElementById(room.dataset.modal);
            if (modal) {
                anthillInfo.classList.remove('visible');
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
            }
        });
        room.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                room.click();
            }
        });
    });
    document.querySelectorAll('.action-modal').forEach(modal => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal || event.target.matches('[data-close-modal]')) {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            }
        });
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            document.querySelectorAll('.action-modal.open').forEach(modal => {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            });
        }
    });
    const parseJsonData = (value) => {
        try {
            return JSON.parse(value || '{}');
        } catch (error) {
            return {};
        }
    };
    const escapeRegExp = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const findByOriginalId = (root, originalId) => Array.from(root.querySelectorAll('[data-original-id]'))
        .find(element => element.dataset.originalId === originalId);
    const findByOriginalPrefix = (root, originalPrefix) => Array.from(root.querySelectorAll('[data-original-id]'))
        .filter(element => element.dataset.originalId.startsWith(originalPrefix));
    const namespaceInlineSvgIds = (root, prefix) => {
        const idMap = new Map();
        root.querySelectorAll('[id]').forEach(element => {
            const originalId = element.id;
            if (!idMap.has(originalId)) {
                idMap.set(originalId, `${prefix}${originalId}`);
            }
            element.dataset.originalId = originalId;
        });

        if (!idMap.size) return;

        root.querySelectorAll('[id]').forEach(element => {
            element.id = idMap.get(element.dataset.originalId);
        });

        const ids = Array.from(idMap.keys()).sort((a, b) => b.length - a.length);
        root.querySelectorAll('*').forEach(element => {
            Array.from(element.attributes).forEach(attribute => {
                if (attribute.name === 'id' || attribute.name === 'data-original-id') return;
                let value = attribute.value;
                ids.forEach(originalId => {
                    const namespacedId = idMap.get(originalId);
                    value = value.replace(
                        new RegExp(`url\\((["']?)#${escapeRegExp(originalId)}\\1\\)`, 'g'),
                        `url($1#${namespacedId}$1)`
                    );
                    if (value === `#${originalId}`) {
                        value = `#${namespacedId}`;
                    }
                });
                if (value !== attribute.value) {
                    element.setAttribute(attribute.name, value);
                }
            });
        });

        root.querySelectorAll('style').forEach(style => {
            let css = style.textContent;
            ids.forEach(originalId => {
                const namespacedId = idMap.get(originalId);
                css = css.replace(
                    new RegExp(`url\\((["']?)#${escapeRegExp(originalId)}\\1\\)`, 'g'),
                    `url($1#${namespacedId}$1)`
                );
            });
            style.textContent = css;
        });
    };

    document.querySelectorAll('.room-svg').forEach((target, index) => {
        fetch(target.dataset.src)
            .then(response => response.text())
            .then(svg => {
                target.innerHTML = svg;
                namespaceInlineSvgIds(target, `anthillRoom${index}__`);
                const colors = parseJsonData(target.dataset.colors);
                Object.entries(colors).forEach(([key, value]) => {
                    target.style.setProperty(`--${key}`, value);
                    const editTarget = findByOriginalId(target, 'edit_color__' + key);
                    if (editTarget) editTarget.setAttribute('fill', value);
                });
                const variants = parseJsonData(target.dataset.variants);
                Object.entries(variants).forEach(([key, value]) => {
                    findByOriginalPrefix(target, 'edit_variant__' + key + '__').forEach(el => el.style.opacity = '0');
                    const variantTarget = findByOriginalId(target, 'edit_variant__' + key + '__' + value);
                    if (variantTarget) variantTarget.style.opacity = '1';
                });
            });
    });
</script>
@endsection
