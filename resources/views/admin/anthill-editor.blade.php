@extends('layouts.app')
@section('content')
<div class="panel">
    <h1>Ladění mraveniště</h1>
    <p>Přepni variantu mraveniště, vyber komůrku, táhni ji po mapě nebo ji roztahuj za žluté úchyty. Uložené pozice se rovnou použijí v hráčském mraveništi pro danou velikost.</p>
    @if($savedAt)
        <p class="small muted">Naposledy uloženo: {{ $savedAt }}</p>
    @endif
</div>

<div class="panel anthill-editor-panel">
    <div class="anthill-editor-tabs" role="tablist" aria-label="Varianty mraveniště">
        @foreach([3, 5, 7, 10] as $rooms)
            <button type="button" data-variant-tab="{{ $rooms }}" @class(['primary' => $loop->first])>{{ $rooms }} komůrky</button>
        @endforeach
    </div>

    <div class="anthill-editor-stage">
        <div class="anthill-editor-resize-box" hidden>
            @foreach(['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'] as $handle)
                <button type="button" class="resize-handle handle-{{ $handle }}" data-resize="{{ $handle }}" aria-label="Změnit velikost"></button>
            @endforeach
        </div>
    </div>

    <div class="anthill-editor-controls">
        <div><b>Varianta:</b> <span data-variant-label>3 komůrky</span></div>
        <div><b>Vybraná komůrka:</b> <span data-selected-label>1</span></div>
        <label>X %<input type="number" step="0.01" data-field="x"></label>
        <label>Y %<input type="number" step="0.01" data-field="y"></label>
        <label>Šířka %<input type="number" step="0.01" min="1" data-field="w"></label>
        <label>Výška %<input type="number" step="0.01" min="1" data-field="h"></label>
        <p class="row">
            <button class="primary" type="button" data-save>Uložit pozice</button>
            <button type="button" data-copy-size>Použít velikost pro všechny v této variantě</button>
            <span class="small muted" data-save-state></span>
        </p>
    </div>
</div>

<style>
    .anthill-editor-panel { display:grid; gap:14px; }
    .anthill-editor-tabs { display:flex; flex-wrap:wrap; gap:8px; justify-content:center; }
    .anthill-editor-stage { position:relative; width:min(100%, 1160px); margin:0 auto; overflow:hidden; border-radius:8px; background:#8b6741 var(--editor-map) center/100% 100% no-repeat; box-shadow:0 18px 50px rgba(40,25,12,.24); touch-action:none; }
    .anthill-editor-room { position:absolute; transform:translate(-50%, -50%); border:0; padding:0; margin:0; min-height:0; background:transparent; border-radius:0; cursor:grab; display:block; }
    .anthill-editor-room:active { cursor:grabbing; }
    .anthill-editor-room.is-selected { outline:2px solid rgba(255,212,59,.9); outline-offset:2px; }
    .anthill-editor-room img { display:block; width:100%; height:100%; object-fit:contain; pointer-events:none; filter:drop-shadow(0 8px 12px rgba(79,55,30,.24)); }
    .anthill-editor-resize-box { position:absolute; z-index:20; pointer-events:none; border:2px solid rgba(255,212,59,.95); box-shadow:0 0 0 9999px rgba(0,0,0,.02); transform:translate(-50%, -50%); }
    .resize-handle { position:absolute; width:16px; height:16px; min-height:16px; padding:0; border-radius:50%; border:2px solid #7a4b00; background:#ffd43b; pointer-events:auto; box-shadow:0 3px 10px rgba(0,0,0,.22); }
    .handle-nw { left:-9px; top:-9px; cursor:nwse-resize; }
    .handle-n { left:50%; top:-9px; transform:translateX(-50%); cursor:ns-resize; }
    .handle-ne { right:-9px; top:-9px; cursor:nesw-resize; }
    .handle-e { right:-9px; top:50%; transform:translateY(-50%); cursor:ew-resize; }
    .handle-se { right:-9px; bottom:-9px; cursor:nwse-resize; }
    .handle-s { left:50%; bottom:-9px; transform:translateX(-50%); cursor:ns-resize; }
    .handle-sw { left:-9px; bottom:-9px; cursor:nesw-resize; }
    .handle-w { left:-9px; top:50%; transform:translateY(-50%); cursor:ew-resize; }
    .anthill-editor-controls { display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:10px; align-items:end; }
    .anthill-editor-controls .row { grid-column:1 / -1; }
</style>

<script>
    (() => {
        const variants = @json($variants, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        const stage = document.querySelector('.anthill-editor-stage');
        const resizeBox = document.querySelector('.anthill-editor-resize-box');
        const fields = Object.fromEntries([...document.querySelectorAll('[data-field]')].map(input => [input.dataset.field, input]));
        const selectedLabel = document.querySelector('[data-selected-label]');
        const variantLabel = document.querySelector('[data-variant-label]');
        const saveState = document.querySelector('[data-save-state]');
        const tabs = [...document.querySelectorAll('[data-variant-tab]')];
        let activeVariant = '3';
        let selected = null;
        let drag = null;

        const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
        const itemFor = (room) => variants[activeVariant].items.find(item => Number(item.slot) === Number(room.dataset.slot));

        const syncStageBackground = () => {
            const map = variants[activeVariant].map;
            stage.style.setProperty('--editor-map', `url('${map.image}')`);
            stage.style.aspectRatio = `${map.width} / ${map.height}`;
        };

        const dataFor = (room) => ({
            slot: Number(room.dataset.slot),
            asset: '/assets/game/rooms/prazdna-mistnost.svg',
            x: Number.parseFloat(room.style.left),
            y: Number.parseFloat(room.style.top),
            w: Number.parseFloat(room.style.width),
            h: Number.parseFloat(room.style.height),
        });

        const updateItemFromRoom = (room) => Object.assign(itemFor(room), dataFor(room));

        const pointToPercent = (event) => {
            const rect = stage.getBoundingClientRect();
            return {
                x: ((event.clientX - rect.left) / rect.width) * 100,
                y: ((event.clientY - rect.top) / rect.height) * 100,
            };
        };

        const syncResizeBox = () => {
            if (!selected) {
                resizeBox.hidden = true;
                return;
            }
            resizeBox.hidden = false;
            resizeBox.style.left = selected.style.left;
            resizeBox.style.top = selected.style.top;
            resizeBox.style.width = selected.style.width;
            resizeBox.style.height = selected.style.height;
        };

        const setSelected = (room) => {
            selected?.classList.remove('is-selected');
            selected = room;
            selected.classList.add('is-selected');
            selectedLabel.textContent = selected.dataset.slot;
            const item = dataFor(selected);
            Object.entries(fields).forEach(([key, input]) => input.value = item[key].toFixed(2));
            syncResizeBox();
        };

        const applyFields = () => {
            if (!selected) return;
            selected.style.left = `${clamp(Number(fields.x.value || 0), 0, 100)}%`;
            selected.style.top = `${clamp(Number(fields.y.value || 0), 0, 100)}%`;
            selected.style.width = `${Math.max(1, Number(fields.w.value || 1))}%`;
            selected.style.height = `${Math.max(1, Number(fields.h.value || 1))}%`;
            updateItemFromRoom(selected);
            syncResizeBox();
        };

        const renderVariant = (variant) => {
            activeVariant = String(variant);
            syncStageBackground();
            stage.querySelectorAll('.anthill-editor-room').forEach(room => room.remove());
            variants[activeVariant].items.forEach(item => {
                const room = document.createElement('button');
                room.type = 'button';
                room.className = 'anthill-editor-room';
                room.dataset.slot = item.slot;
                room.setAttribute('aria-label', `Komůrka ${item.slot}`);
                room.style.left = `${item.x}%`;
                room.style.top = `${item.y}%`;
                room.style.width = `${item.w}%`;
                room.style.height = `${item.h}%`;
                room.innerHTML = `<img src="${item.asset}" alt="">`;
                stage.insertBefore(room, resizeBox);
                attachRoomEvents(room);
            });
            variantLabel.textContent = `${activeVariant} komůrky`;
            tabs.forEach(tab => tab.classList.toggle('primary', tab.dataset.variantTab === activeVariant));
            setSelected(stage.querySelector('.anthill-editor-room'));
        };

        const attachRoomEvents = (room) => {
            room.addEventListener('pointerdown', (event) => {
                event.preventDefault();
                setSelected(room);
                room.setPointerCapture(event.pointerId);
                const item = dataFor(room);
                const start = pointToPercent(event);
                drag = { type: 'move', pointerId: event.pointerId, start, item };
            });
            room.addEventListener('pointermove', (event) => {
                if (!drag || drag.pointerId !== event.pointerId || drag.type !== 'move' || room !== selected) return;
                const point = pointToPercent(event);
                fields.x.value = clamp(drag.item.x + point.x - drag.start.x, 0, 100).toFixed(2);
                fields.y.value = clamp(drag.item.y + point.y - drag.start.y, 0, 100).toFixed(2);
                applyFields();
            });
            room.addEventListener('pointerup', () => drag = null);
            room.addEventListener('pointercancel', () => drag = null);
        };

        resizeBox.querySelectorAll('[data-resize]').forEach(handle => {
            handle.addEventListener('pointerdown', (event) => {
                if (!selected) return;
                event.preventDefault();
                event.stopPropagation();
                handle.setPointerCapture(event.pointerId);
                drag = {
                    type: 'resize',
                    pointerId: event.pointerId,
                    handle: handle.dataset.resize,
                    start: pointToPercent(event),
                    item: dataFor(selected),
                };
            });
            handle.addEventListener('pointermove', (event) => {
                if (!drag || drag.pointerId !== event.pointerId || drag.type !== 'resize') return;
                const point = pointToPercent(event);
                const dx = point.x - drag.start.x;
                const dy = point.y - drag.start.y;
                let { x, y, w, h } = drag.item;

                if (drag.handle.includes('e')) w += dx;
                if (drag.handle.includes('w')) {
                    w -= dx;
                    x += dx / 2;
                }
                if (drag.handle.includes('s')) h += dy;
                if (drag.handle.includes('n')) {
                    h -= dy;
                    y += dy / 2;
                }
                if (drag.handle.includes('e')) x += dx / 2;
                if (drag.handle.includes('s')) y += dy / 2;

                fields.x.value = clamp(x, 0, 100).toFixed(2);
                fields.y.value = clamp(y, 0, 100).toFixed(2);
                fields.w.value = Math.max(1, w).toFixed(2);
                fields.h.value = Math.max(1, h).toFixed(2);
                applyFields();
            });
            handle.addEventListener('pointerup', () => drag = null);
            handle.addEventListener('pointercancel', () => drag = null);
        });

        Object.values(fields).forEach(input => input.addEventListener('input', applyFields));
        tabs.forEach(tab => tab.addEventListener('click', () => renderVariant(tab.dataset.variantTab)));
        document.querySelector('[data-copy-size]').addEventListener('click', () => {
            const w = Math.max(1, Number(fields.w.value || 1));
            const h = Math.max(1, Number(fields.h.value || 1));
            stage.querySelectorAll('.anthill-editor-room').forEach(room => {
                room.style.width = `${w}%`;
                room.style.height = `${h}%`;
                updateItemFromRoom(room);
            });
            syncResizeBox();
            saveState.textContent = 'Velikost zkopírována do všech komůrek v této variantě.';
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
                body: JSON.stringify({ variants }),
            });
            saveState.textContent = response.ok ? 'Uloženo.' : 'Uložení se nepovedlo.';
        });

        renderVariant(activeVariant);
    })();
</script>
@endsection
