@extends('layouts.app')
@section('content')
<div id="floating-tooltip" class="floating-tooltip"></div>
<div class="anthill-scene">
    <div class="anthill-title">
        <h1>{{ ($readonly ?? false) ? 'Mraveniště: ' . $owner->display_name : 'Mraveniště' }}</h1>
        <button class="title-help" type="button" aria-label="Co je mraveniště?">?</button>
        <p>Mraveniště je tvoje základna. Rozšiřuj ho, stav místnosti a plň jejich vnitřní úkoly.</p>
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
                @endphp
                @if(!$locked)
                    <div class="room slot"
                        data-description="{{ $placedBuilding ? $placedBuilding->name . ' už je připravená na práci.' : ($isOwned ? 'Prázdná komůrka čeká na novou místnost.' : 'Tahle komůrka půjde koupit za ' . $slot->cost_resources . ' surovin.') }}"
                        @if(!$placedBuilding && !($readonly ?? false)) data-modal="slot-modal-{{ $slot->id }}" tabindex="0" role="button" @endif
                        style="left:{{ $slot->layout_x }}%; top:{{ $slot->layout_y }}%; width:{{ $slot->layout_w ?? 12 }}%; height:{{ $slot->layout_h ?? 12 }}%;">
                        @if($placedBuilding)
                            @if($readonly ?? false)
                                <img src="{{ $placedBuilding->svg_asset_path }}" alt="{{ $placedBuilding->name }}">
                            @else
                                <a href="/budovy/{{ $placedBuilding->slug }}" aria-label="{{ $placedBuilding->name }}"><img src="{{ $placedBuilding->svg_asset_path }}" alt="{{ $placedBuilding->name }}"></a>
                            @endif
                        @elseif($isOwned)
                            <img src="/assets/game/rooms/prazdna-mistnost.svg" alt="Prázdný slot">
                        @else
                            <img src="/assets/placeholders/slot-available.svg" alt="Dostupný slot">
                        @endif
                    </div>
                    @if(!$placedBuilding && !($readonly ?? false))
                        <div class="action-modal" id="slot-modal-{{ $slot->id }}" aria-hidden="true">
                            <div class="modal-window">
                                <h2>Komůrka {{ $slot->slot_number }}</h2>
                                @if($isOwned)
                                    <p>Tenhle prostor je koupený a prázdný. Vyber, co sem postavit.</p>
                                    @if(count($ownedBuildingIds) < $buildings->count())
                                        <form method="post" action="/mraveniste/build">
                                            @csrf
                                            <input type="hidden" name="slot_id" value="{{ $slot->id }}">
                                            <label>Budova</label>
                                            <select name="building_id">
                                                @foreach($buildings as $building)
                                                    @if(!in_array($building->id, $ownedBuildingIds))
                                                        <option value="{{ $building->id }}">{{ $building->name }} ({{ $building->cost_resources }} surovin, od úrovně kolonie {{ $building->min_colony_level }})</option>
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
</script>
@endsection
