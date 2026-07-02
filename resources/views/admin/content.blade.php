@extends('layouts.app')
@section('content')
@php
    $imageFiles = $gameFiles->filter(fn($file) => str_starts_with((string) $file->mime_type, 'image/'));
    $pdfFiles = $gameFiles->filter(fn($file) => $file->mime_type === 'application/pdf');
@endphp
<h1>Úprava hry</h1>
<p class="muted">Rychlá správa textů, úkolů, odměn a souborů. Změny se projeví hned po uložení.</p>

<div class="panel" style="margin-bottom:16px">
    <h2>Souborová knihovna</h2>
    <form method="post" action="/admin/soubory" enctype="multipart/form-data">
        @csrf
        <div class="grid">
            <div><label>Nahrát nový soubor</label><input name="file" type="file" accept="image/png,image/jpeg,image/webp,application/pdf" required></div>
            <div><label>Kategorie</label><select name="category"><option value="task">Úkol</option><option value="location">Lokace</option><option value="story">Příběh</option><option value="badge">Odznáček</option><option value="general">Obecné</option></select></div>
        </div>
        <p><button class="primary">Nahrát soubor</button></p>
    </form>
    <p class="small">Uložené soubory pak vybírej níže v samostatném poli „Vybrat uložený soubor“.</p>
</div>

<div class="panel" style="margin-bottom:16px">
    <h2>Úvodní příběh</h2>
    <form method="post" action="/admin/obsah/texty/intro_story" enctype="multipart/form-data">
        @csrf
        <label>Nadpis</label>
        <input name="title" value="{{ $intro->title ?? 'Jak to celé začalo' }}" required>
        <label>Text před obrázkem</label>
        <textarea name="body_top" rows="5">{{ $intro->body_top ?? '' }}</textarea>
        <label>Nahrát první obrázek mezi texty</label>
        <input name="image" type="file" accept="image/png,image/jpeg,image/webp">
        <label>Vybrat první uložený obrázek</label>
        <select name="existing_image_path"><option value="">Ponechat aktuální</option>@foreach($imageFiles as $file)<option value="{{ $file->public_path }}">{{ $file->original_name }} - {{ $file->public_path }}</option>@endforeach</select>
        @if($intro?->image_path)<p class="small">Aktuální: {{ $intro->image_path }}</p>@endif
        <label>Text mezi obrázky</label>
        <textarea name="body_middle" rows="5">{{ $intro->body_middle ?? '' }}</textarea>
        <label>Nahrát druhý obrázek mezi texty</label>
        <input name="image_2" type="file" accept="image/png,image/jpeg,image/webp">
        <label>Vybrat druhý uložený obrázek</label>
        <select name="existing_image_path_2"><option value="">Ponechat aktuální</option>@foreach($imageFiles as $file)<option value="{{ $file->public_path }}">{{ $file->original_name }} - {{ $file->public_path }}</option>@endforeach</select>
        @if(($intro->image_path_2 ?? null))<p class="small">Aktuální: {{ $intro->image_path_2 }}</p>@endif
        <label>Text po druhém obrázku</label>
        <textarea name="body_bottom" rows="5">{{ $intro->body_bottom ?? '' }}</textarea>
        <p><button class="primary">Uložit úvodní příběh</button></p>
    </form>
</div>

<h2>Lokace na palouku</h2>
@foreach($locations as $location)
    <details class="card" style="margin-bottom:10px">
        <summary><b>{{ $location->sort_order ?? $location->id }}. {{ $location->name }}</b> ({{ $location->slug }})</summary>
        <form method="post" action="/admin/obsah/lokace/{{ $location->id }}" enctype="multipart/form-data">
            @csrf
            <div class="grid">
                <div><label>Název</label><input name="name" value="{{ $location->name }}" required></div>
                <div><label>Popis odemčené lokace</label><input name="tooltip" value="{{ $location->tooltip ?? '' }}"></div>
                <div><label>Popis po splnění lokace</label><input name="tooltip_completed" value="{{ $location->tooltip_completed ?? '' }}"></div>
                <div><label>X na mapě</label><input name="map_x" type="number" min="0" max="100" value="{{ $location->map_x }}" required></div>
                <div><label>Y na mapě</label><input name="map_y" type="number" min="0" max="100" value="{{ $location->map_y }}" required></div>
                <div><label>Pořadí</label><input name="sort_order" type="number" min="1" value="{{ $location->sort_order ?? $location->id }}" required></div>
                <div><label>Prestiž</label><input name="reward_prestige" type="number" min="0" value="{{ $location->reward_prestige }}" required></div>
                <div><label>Suroviny</label><input name="reward_resources" type="number" min="0" value="{{ $location->reward_resources }}" required></div>
                <div><label>Úroveň kolonie</label><input name="reward_colony_level" type="number" min="0" value="{{ $location->reward_colony_level }}" required></div>
            </div>
            <label>Popis uzamčené lokace</label>
            <textarea name="description" rows="3" required>{{ $location->description }}</textarea>
            <label>Příběh lokace</label>
            <textarea name="story" rows="5">{{ $location->story }}</textarea>
            <label>Příběh po úspěšném splnění</label>
            <textarea name="story_completed" rows="5">{{ $location->story_completed ?? '' }}</textarea>
            <div class="grid">
                <div><label>Nahrát nové PNG stanoviště</label><input name="image" type="file" accept="image/png,image/jpeg,image/webp"><label>Vybrat uložený obrázek stanoviště</label><select name="existing_image_path"><option value="">Ponechat aktuální</option>@foreach($imageFiles as $file)<option value="{{ $file->public_path }}">{{ $file->original_name }}</option>@endforeach</select><p class="small">{{ $location->image_path }}</p></div>
                <div><label>Nahrát nový obrázek v detailu</label><input name="story_image" type="file" accept="image/png,image/jpeg,image/webp"><label>Vybrat uložený obrázek v detailu</label><select name="existing_story_image_path"><option value="">Ponechat aktuální</option>@foreach($imageFiles as $file)<option value="{{ $file->public_path }}">{{ $file->original_name }}</option>@endforeach</select><p class="small">{{ $location->story_image_path }}</p></div>
                <div><label>Nahrát obrázek po splnění</label><input name="completed_image" type="file" accept="image/png,image/jpeg,image/webp"><label>Vybrat uložený obrázek po splnění</label><select name="existing_completed_image_path"><option value="">Ponechat aktuální</option>@foreach($imageFiles as $file)<option value="{{ $file->public_path }}">{{ $file->original_name }}</option>@endforeach</select><p class="small">{{ $location->completed_image_path ?? '' }}</p></div>
            </div>
            <p class="row"><button class="primary">Uložit lokaci</button><a class="btn" href="/admin/nahled/lokace/{{ $location->slug }}">Náhled</a></p>
        </form>
    </details>
@endforeach

<h2>Úkoly z palouku</h2>
@foreach($tasks as $task)
    @php($hint = ($taskHints[$task->id] ?? collect())->first())
    <details class="card" style="margin-bottom:10px">
        <summary><b>{{ $task->location_name }}:</b> {{ $task->title }}</summary>
        <form method="post" action="/admin/obsah/ukoly/{{ $task->id }}" enctype="multipart/form-data">
            @csrf
            <div class="grid">
                <div><label>Nadpis</label><input name="title" value="{{ $task->title }}" required></div>
                <div><label>Typ</label><select name="type"><option value="code" @selected($task->type === 'code')>Kód</option><option value="manual" @selected($task->type === 'manual')>Ruční kontrola</option><option value="info" @selected($task->type === 'info')>Jen informace</option></select></div>
                <div><label>Pořadí</label><input name="sort_order" type="number" min="1" value="{{ $task->sort_order ?? 1 }}" required></div>
                <div><label>Prestiž</label><input name="reward_prestige" type="number" min="0" value="{{ $task->reward_prestige }}" required></div>
                <div><label>Suroviny</label><input name="reward_resources" type="number" min="0" value="{{ $task->reward_resources }}" required></div>
            </div>
            <label>Text úkolu</label>
            <textarea name="body" rows="5" required>{{ $task->body }}</textarea>
            <label>Doplňující text nad tlačítkem Stáhnout PDF</label>
            <textarea name="pdf_intro" rows="3">{{ $task->pdf_intro ?? '' }}</textarea>
            <label>Nápověda</label>
            <textarea name="hint_text" rows="3">{{ $hint->text ?? '' }}</textarea>
            <label>Nová správná odpověď / kód (prázdné = neměnit)</label>
            <input name="answer">
            <label><input name="required_for_completion" type="checkbox" value="1" style="width:auto" @checked($task->required_for_completion)> Povinné pro splnění stanoviště</label>
            <label>Nahrát nové PDF k úkolu</label>
            <input name="pdf" type="file" accept="application/pdf">
            <label>Vybrat uložené PDF k úkolu</label>
            <select name="existing_pdf_path"><option value="">Ponechat aktuální</option>@foreach($pdfFiles as $file)<option value="{{ $file->public_path }}">{{ $file->original_name }} - {{ $file->public_path }}</option>@endforeach</select>
            @if($task->pdf_path)<p class="small">Aktuální PDF: <a href="{{ $task->pdf_path }}">{{ $task->pdf_path }}</a></p>@endif
            <p><button class="primary">Uložit úkol</button></p>
        </form>
    </details>
@endforeach

<h2>Podúkoly v mraveništi</h2>
@foreach($buildingTasks as $task)
    <details class="card" style="margin-bottom:10px">
        <summary><b>{{ $task->building_name }}:</b> {{ $task->title }}</summary>
        <form method="post" action="/admin/obsah/budovy/ukoly/{{ $task->id }}" enctype="multipart/form-data">
            @csrf
            <div class="grid">
                <div><label>Nadpis</label><input name="title" value="{{ $task->title }}" required></div>
                <div><label>Pořadí</label><input name="sort_order" type="number" min="1" value="{{ $task->sort_order ?? 1 }}" required></div>
                <div><label>Prestiž</label><input name="reward_prestige" type="number" min="0" value="{{ $task->reward_prestige }}" required></div>
                <div><label>Suroviny</label><input name="reward_resources" type="number" min="0" value="{{ $task->reward_resources }}" required></div>
            </div>
            <label>Text podúkolu</label>
            <textarea name="body" rows="5" required>{{ $task->body }}</textarea>
            <label>Nová správná odpověď / kód (prázdné = neměnit)</label>
            <input name="answer">
            <label>Nahrát nové PDF k podúkolu</label>
            <input name="pdf" type="file" accept="application/pdf">
            <label>Vybrat uložené PDF k podúkolu</label>
            <select name="existing_pdf_path"><option value="">Ponechat aktuální</option>@foreach($pdfFiles as $file)<option value="{{ $file->public_path }}">{{ $file->original_name }} - {{ $file->public_path }}</option>@endforeach</select>
            @if($task->pdf_path)<p class="small">Aktuální PDF: <a href="{{ $task->pdf_path }}">{{ $task->pdf_path }}</a></p>@endif
            <p><button class="primary">Uložit podúkol</button></p>
        </form>
    </details>
@endforeach

<h2>Budovy</h2>
@foreach($buildings as $building)
    <div class="card" style="margin-bottom:8px"><b>{{ $building->name }}</b> - úroveň {{ $building->min_colony_level }}<p>{{ $building->description }}</p><p class="row"><a class="btn" href="/admin/nahled/budovy/{{ $building->slug }}">Náhled jako hráč</a></p></div>
@endforeach

<h2>Odznáčky</h2>
@foreach($badges as $badge)
    <details class="card" style="margin-bottom:10px">
        <summary class="row">
            @if($badge->icon_path)
                <span class="badge-tip" tabindex="0" data-tooltip="{{ $badge->description ?: $badge->name }}"><img class="badge-icon" src="{{ $badge->icon_path }}" alt=""></span>
            @endif
            <b>{{ $badge->name }}</b>
        </summary>
        <form method="post" action="/admin/odznacky/{{ $badge->id }}" enctype="multipart/form-data">
            @csrf
            <div class="grid">
                <div><label>Název</label><input name="name" value="{{ $badge->name }}" required></div>
                <div><label>Bonus prestiže</label><input name="prestige_bonus" type="number" min="0" value="{{ $badge->prestige_bonus }}" required></div>
            </div>
            <label>Popis pro tooltip</label>
            <textarea name="description" rows="3" required>{{ $badge->description }}</textarea>
            <div class="grid">
                <div>
                    <label>Nahrát novou ikonu</label>
                    <input name="icon" type="file" accept="image/png,image/jpeg,image/webp">
                </div>
                <div>
                    <label>Vybrat uloženou ikonu</label>
                    <select name="existing_icon_path">
                        <option value="">Ponechat aktuální</option>
                        @foreach($imageFiles as $file)
                            <option value="{{ $file->public_path }}">{{ $file->original_name }} - {{ $file->public_path }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if($badge->icon_path)<p class="small">Aktuální ikona: {{ $badge->icon_path }}</p>@endif
            <p><button class="primary">Uložit odznáček</button></p>
        </form>
    </details>
@endforeach
@endsection
