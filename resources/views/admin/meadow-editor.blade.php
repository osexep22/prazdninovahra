<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ladění palouku</title>
    <style>
        :root { --ink:#1f2933; --muted:#65758b; --line:#d6dee8; --panel:#fffaf0; --leaf:#276749; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:system-ui, Segoe UI, sans-serif; color:var(--ink); background:#edf4e4; }
        header { position:sticky; top:0; z-index:20; display:flex; gap:12px; align-items:center; justify-content:space-between; padding:10px 14px; background:rgba(255,255,255,.94); border-bottom:1px solid var(--line); backdrop-filter:blur(10px); }
        h1 { margin:0; font-size:20px; }
        a, button { border:1px solid var(--line); background:white; color:#235c80; padding:8px 10px; border-radius:7px; font-weight:800; text-decoration:none; cursor:pointer; }
        button.primary { background:var(--leaf); color:white; border-color:var(--leaf); }
        .wrap { display:grid; grid-template-columns:minmax(0, 1fr) 330px; gap:12px; padding:12px; align-items:start; }
        .stage-shell { overflow:auto; border:1px solid #abc49a; background:#cfeeb8; border-radius:8px; max-height:calc(100vh - 78px); }
        .stage { position:relative; width:1774px; height:887px; transform-origin:top left; background:url('{{ $map['image'] }}') 0 0 / 1774px 887px no-repeat; }
        .item { position:absolute; border:0; cursor:move; touch-action:none; }
        .item.selected { outline:0; z-index:10; }
        .item img { width:100%; height:100%; display:block; object-fit:fill; pointer-events:none; user-select:none; }
        .item .label { display:none; }
        .handle { position:absolute; right:-8px; bottom:-8px; width:16px; height:16px; border-radius:50%; background:#f6b12a; border:2px solid #5a3b04; cursor:nwse-resize; }
        aside { position:sticky; top:64px; display:grid; gap:10px; max-height:calc(100vh - 78px); overflow:auto; }
        .panel { background:white; border:1px solid var(--line); border-radius:8px; padding:12px; }
        .row { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        label { display:block; font-weight:800; margin:8px 0 4px; }
        input, textarea, select { width:100%; border:1px solid var(--line); border-radius:7px; padding:8px; font:inherit; background:white; }
        .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
        .small { color:var(--muted); font-size:13px; line-height:1.4; }
        .list { display:grid; gap:5px; }
        .list button { width:100%; justify-content:flex-start; text-align:left; color:var(--ink); }
        .list button.active { background:#fff3ba; border-color:#d8a928; }
        textarea { min-height:170px; font-family:Consolas, monospace; font-size:12px; }
        .status { min-height:20px; font-weight:800; color:#276749; }
        @media (max-width: 980px) { .wrap { grid-template-columns:1fr; } aside { position:static; max-height:none; } }
    </style>
</head>
<body>
<header>
    <div class="row">
        <h1>Ladění stanovišť na palouku</h1>
        <span class="small">Mapa {{ $map['width'] }} × {{ $map['height'] }} px</span>
    </div>
    <div class="row">
        <a href="/palouk">Palouk</a>
        <a href="/admin">Admin</a>
    </div>
</header>
<div class="wrap">
    <div class="stage-shell" id="stage-shell">
        <div class="stage" id="stage" data-map-width="{{ $map['width'] }}" data-map-height="{{ $map['height'] }}">
            @foreach($items as $item)
                <div class="item" data-slug="{{ $item['slug'] }}" style="left:{{ $item['x'] }}px; top:{{ $item['y'] }}px; width:{{ $item['w'] }}px; height:{{ $item['h'] }}px;">
                    <span class="label">{{ $item['name'] }}</span>
                    <img src="{{ $item['asset'] }}" alt="{{ $item['name'] }}">
                    <span class="handle" aria-hidden="true"></span>
                </div>
            @endforeach
        </div>
    </div>
    <aside>
        <section class="panel">
            <div class="row">
                <button class="primary" type="button" id="save">Uložit na server</button>
                <button type="button" id="copy">Kopírovat JSON</button>
            </div>
            <p class="status" id="status">{{ $savedAt ? 'Naposledy uloženo: '.$savedAt : '' }}</p>
            <p class="small">Klikni na stanoviště a táhni ho myší. Velikost změníš žlutým bodem vpravo dole. Šipky posouvají o 1 px, Shift + šipka o 10 px.</p>
        </section>
        <section class="panel">
            <label>Vybrané stanoviště</label>
            <select id="selected"></select>
            <div class="grid2">
                <div><label>X</label><input id="x" type="number" step="1"></div>
                <div><label>Y</label><input id="y" type="number" step="1"></div>
                <div><label>Šířka</label><input id="w" type="number" step="1" min="1"></div>
                <div><label>Výška</label><input id="h" type="number" step="1" min="1"></div>
            </div>
            <div class="row" style="margin-top:10px">
                <button type="button" id="apply">Použít čísla</button>
                <button type="button" id="reset-local">Smazat lokální paměť</button>
            </div>
        </section>
        <section class="panel">
            <label>Stanoviště</label>
            <div class="list" id="list"></div>
        </section>
        <section class="panel">
            <label>Výstup pro Codex</label>
            <textarea id="json" readonly></textarea>
        </section>
    </aside>
</div>
<script>
const initialItems = @json($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
const csrf = @json(csrf_token());
const storageKey = 'meadow-layout-draft-edgefade-v1';
const stage = document.getElementById('stage');
const statusEl = document.getElementById('status');
const listEl = document.getElementById('list');
const selectEl = document.getElementById('selected');
const jsonEl = document.getElementById('json');
const inputs = ['x','y','w','h'].reduce((all, id) => ({...all, [id]: document.getElementById(id)}), {});
let selected = null;
let action = null;

function itemElements() { return Array.from(document.querySelectorAll('.item')); }
function readItems() {
    return itemElements().map(el => {
        const original = initialItems.find(item => item.slug === el.dataset.slug) || {};
        return {
            slug: el.dataset.slug,
            name: original.name || el.dataset.slug,
            asset: original.asset || el.querySelector('img')?.getAttribute('src') || '',
            x: Math.round(parseFloat(el.style.left) * 100) / 100,
            y: Math.round(parseFloat(el.style.top) * 100) / 100,
            w: Math.round(parseFloat(el.style.width) * 100) / 100,
            h: Math.round(parseFloat(el.style.height) * 100) / 100,
        };
    });
}
function payload() {
    return { map: { width: 1774, height: 887, image: '{{ $map['image'] }}' }, items: readItems() };
}
function refreshOutput() {
    const data = payload();
    localStorage.setItem(storageKey, JSON.stringify(data));
    jsonEl.value = JSON.stringify(data, null, 2);
    if (selected) {
        inputs.x.value = Math.round(parseFloat(selected.style.left));
        inputs.y.value = Math.round(parseFloat(selected.style.top));
        inputs.w.value = Math.round(parseFloat(selected.style.width));
        inputs.h.value = Math.round(parseFloat(selected.style.height));
        selectEl.value = selected.dataset.slug;
    }
}
function selectItem(el) {
    selected?.classList.remove('selected');
    selected = el;
    selected?.classList.add('selected');
    listEl.querySelectorAll('button').forEach(button => button.classList.toggle('active', button.dataset.slug === selected?.dataset.slug));
    refreshOutput();
}
function setRect(el, rect) {
    el.style.left = `${rect.x}px`;
    el.style.top = `${rect.y}px`;
    el.style.width = `${Math.max(1, rect.w)}px`;
    el.style.height = `${Math.max(1, rect.h)}px`;
    refreshOutput();
}
function stagePoint(event) {
    const rect = stage.getBoundingClientRect();
    const scaleX = stage.offsetWidth / rect.width;
    const scaleY = stage.offsetHeight / rect.height;
    return { x: (event.clientX - rect.left) * scaleX, y: (event.clientY - rect.top) * scaleY };
}
function buildControls() {
    initialItems.forEach(item => {
        const option = document.createElement('option');
        option.value = item.slug;
        option.textContent = `${item.slug} - ${item.name}`;
        selectEl.appendChild(option);
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.slug = item.slug;
        button.textContent = `${item.slug} - ${item.name}`;
        button.addEventListener('click', () => selectItem(document.querySelector(`.item[data-slug="${item.slug}"]`)));
        listEl.appendChild(button);
    });
    selectEl.addEventListener('change', () => selectItem(document.querySelector(`.item[data-slug="${selectEl.value}"]`)));
}
function restoreLocal() {
    const raw = localStorage.getItem(storageKey);
    if (!raw) return;
    try {
        const data = JSON.parse(raw);
        (data.items || []).forEach(item => {
            const el = document.querySelector(`.item[data-slug="${item.slug}"]`);
            if (el) setRect(el, item);
        });
        statusEl.textContent = 'Načteno z lokální paměti prohlížeče.';
    } catch {}
}

itemElements().forEach(el => {
    el.addEventListener('pointerdown', event => {
        selectItem(el);
        const point = stagePoint(event);
        const start = { x: parseFloat(el.style.left), y: parseFloat(el.style.top), w: parseFloat(el.style.width), h: parseFloat(el.style.height), pointerX: point.x, pointerY: point.y };
        action = { type: event.target.classList.contains('handle') ? 'resize' : 'move', el, start };
        el.setPointerCapture(event.pointerId);
        event.preventDefault();
    });
    el.addEventListener('pointermove', event => {
        if (!action || action.el !== el) return;
        const point = stagePoint(event);
        const dx = point.x - action.start.pointerX;
        const dy = point.y - action.start.pointerY;
        if (action.type === 'move') {
            setRect(el, { x: action.start.x + dx, y: action.start.y + dy, w: action.start.w, h: action.start.h });
        } else {
            const ratio = event.shiftKey ? action.start.w / action.start.h : null;
            let w = action.start.w + dx;
            let h = ratio ? w / ratio : action.start.h + dy;
            setRect(el, { x: action.start.x, y: action.start.y, w, h });
        }
    });
    el.addEventListener('pointerup', () => action = null);
});

document.getElementById('apply').addEventListener('click', () => {
    if (!selected) return;
    setRect(selected, { x: Number(inputs.x.value), y: Number(inputs.y.value), w: Number(inputs.w.value), h: Number(inputs.h.value) });
});
document.getElementById('copy').addEventListener('click', async () => {
    await navigator.clipboard.writeText(jsonEl.value);
    statusEl.textContent = 'JSON zkopírován do schránky.';
});
document.getElementById('save').addEventListener('click', async () => {
    const response = await fetch('/admin/ladeni-palouku', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: JSON.stringify({ items: readItems() }),
    });
    const data = await response.json();
    statusEl.textContent = data.ok ? `Uloženo na server: ${data.saved_at}` : 'Uložení se nepovedlo.';
});
document.getElementById('reset-local').addEventListener('click', () => {
    localStorage.removeItem(storageKey);
    statusEl.textContent = 'Lokální paměť smazána. Obnov stránku pro serverová data.';
});
document.addEventListener('keydown', event => {
    if (!selected || ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;
    const step = event.shiftKey ? 10 : 1;
    const rect = { x: parseFloat(selected.style.left), y: parseFloat(selected.style.top), w: parseFloat(selected.style.width), h: parseFloat(selected.style.height) };
    if (event.key === 'ArrowLeft') rect.x -= step;
    else if (event.key === 'ArrowRight') rect.x += step;
    else if (event.key === 'ArrowUp') rect.y -= step;
    else if (event.key === 'ArrowDown') rect.y += step;
    else return;
    event.preventDefault();
    setRect(selected, rect);
});

buildControls();
restoreLocal();
selectItem(document.querySelector('.item[data-slug="ukol-1"]') || document.querySelector('.item'));
refreshOutput();
</script>
</body>
</html>