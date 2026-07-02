@extends('layouts.app')
@section('content')
<div class="panel">
    <h1>Ladění mraveniště</h1>
    <p>Táhni komůrky po mapě, dolaď rozměry a ulož. Stejné souřadnice se použijí i v hráčském mraveništi.</p>
    @if($savedAt)
        <p class="small muted">Naposledy uloženo: {{ $savedAt }}</p>
    @endif
</div>

<div class="panel anthill-editor-panel">
    <div class="anthill-editor-stage" style="--editor-map:url('{{ $map['image'] }}'); aspect-ratio:{{ $map['width'] }} / {{ $map['height'] }};">
        @foreach($items as $item)
            <button class="anthill-editor-room"
                type="button"
                data-slot="{{ $item['slot'] }}"
                style="left:{{ $item['x'] }}%; top:{{ $item['y'] }}%; width:{{ $item['w'] }}%; height:{{ $item['h'] }}%;"
                aria-label="Komůrka {{ $item['slot'] }}">
                <img src="{{ $item['asset'] }}" alt="">
            </button>
        @endforeach
    </div>

    <div class="anthill-editor-controls">
        <div><b>Vybraná komůrka:</b> <span data-selected-label>1</span></div>
        <label>X %<input type="number" step="0.01" data-field="x"></label>
        <label>Y %<input type="number" step="0.01" data-field="y"></label>
        <label>Šířka %<input type="number" step="0.01" min="1" data-field="w"></label>
        <label>Výška %<input type="number" step="0.01" min="1" data-field="h"></label>
        <p class="row">
            <button class="primary" type="button" data-save>Uložit pozice</button>
            <button type="button" data-copy-size>Použít velikost pro všechny</button>
            <span class="small muted" data-save-state></span>
        </p>
    </div>
</div>

<style>
    .anthill-editor-panel { display:grid; gap:14px; }
    .anthill-editor-stage { position:relative; width:min(100%, 1160px); margin:0 auto; overflow:hidden; border-radius:8px; background:#8b6741 var(--editor-map) center/100% 100% no-repeat; box-shadow:0 18px 50px rgba(40,25,12,.24); touch-action:none; }
    .anthill-editor-room { position:absolute; transform:translate(-50%, -50%); border:0; padding:0; margin:0; min-height:0; background:transparent; border-radius:0; cursor:grab; display:block; }
    .anthill-editor-room:active { cursor:grabbing; }
    .anthill-editor-room.is-selected { outline:2px solid rgba(255,212,59,.9); outline-offset:2px; }
    .anthill-editor-room img { display:block; width:100%; height:100%; object-fit:contain; pointer-events:none; filter:drop-shadow(0 8px 12px rgba(79,55,30,.24)); }
    .anthill-editor-controls { display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:10px; align-items:end; }
    .anthill-editor-controls .row { grid-column:1 / -1; }
</style>

<script>
    (() => {
        const stage = document.querySelector('.anthill-editor-stage');
        const rooms = [...document.querySelectorAll('.anthill-editor-room')];
        const fields = Object.fromEntries([...document.querySelectorAll('[data-field]')].map(input => [input.dataset.field, input]));
        const selectedLabel = document.querySelector('[data-selected-label]');
        const saveState = document.querySelector('[data-save-state]');
        let selected = rooms[0];
        let drag = null;

        const dataFor = (room) => ({
            slot: Number(room.dataset.slot),
            asset: '/assets/game/rooms/prazdna-mistnost.svg',
            x: Number.parseFloat(room.style.left),
            y: Number.parseFloat(room.style.top),
            w: Number.parseFloat(room.style.width),
            h: Number.parseFloat(room.style.height),
        });

        const setSelected = (room) => {
            selected?.classList.remove('is-selected');
            selected = room;
            selected.classList.add('is-selected');
            selectedLabel.textContent = selected.dataset.slot;
            const item = dataFor(selected);
            Object.entries(fields).forEach(([key, input]) => input.value = item[key].toFixed(2));
        };

        const applyFields = () => {
            selected.style.left = `${Number(fields.x.value || 0)}%`;
            selected.style.top = `${Number(fields.y.value || 0)}%`;
            selected.style.width = `${Math.max(1, Number(fields.w.value || 1))}%`;
            selected.style.height = `${Math.max(1, Number(fields.h.value || 1))}%`;
        };

        const pointToPercent = (event) => {
            const rect = stage.getBoundingClientRect();
            return {
                x: ((event.clientX - rect.left) / rect.width) * 100,
                y: ((event.clientY - rect.top) / rect.height) * 100,
            };
        };

        rooms.forEach(room => {
            room.addEventListener('pointerdown', (event) => {
                event.preventDefault();
                setSelected(room);
                room.setPointerCapture(event.pointerId);
                drag = { pointerId: event.pointerId };
            });
            room.addEventListener('pointermove', (event) => {
                if (!drag || drag.pointerId !== event.pointerId || room !== selected) return;
                const point = pointToPercent(event);
                fields.x.value = point.x.toFixed(2);
                fields.y.value = point.y.toFixed(2);
                applyFields();
            });
            room.addEventListener('pointerup', () => drag = null);
        });

        Object.values(fields).forEach(input => input.addEventListener('input', applyFields));
        document.querySelector('[data-copy-size]').addEventListener('click', () => {
            const w = Math.max(1, Number(fields.w.value || 1));
            const h = Math.max(1, Number(fields.h.value || 1));
            rooms.forEach(room => {
                room.style.width = `${w}%`;
                room.style.height = `${h}%`;
            });
            saveState.textContent = 'Velikost zkopírována do všech komůrek.';
        });
        document.querySelector('[data-save]').addEventListener('click', async () => {
            saveState.textContent = 'Ukládám...';
            const response = await fetch('/admin/ladeni-mraveniste', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ items: rooms.map(dataFor) }),
            });
            saveState.textContent = response.ok ? 'Uloženo.' : 'Uložení se nepovedlo.';
        });

        setSelected(selected);
    })();
</script>
@endsection
