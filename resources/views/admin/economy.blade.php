@extends('layouts.app')
@section('content')
<h1>Ekonomika hry</h1>
<p class="muted">Rychlá správa odměn, cen a bonusů. Tato stránka mění jen číselné hodnoty ekonomiky, ne příběhové texty.</p>

<form method="post" action="/admin/ekonomika">
    @csrf

    <div class="panel" style="margin-bottom:14px">
        <h2>Stanoviště</h2>
        <table>
            <thead><tr><th>Stanoviště</th><th>Suroviny</th><th>Prestiž</th></tr></thead>
            <tbody>
            @foreach($locations as $location)
                <tr>
                    <td><b>{{ $location->name }}</b><br><span class="small muted">{{ $location->slug }}</span></td>
                    <td><input type="number" min="0" name="locations[{{ $location->id }}][reward_resources]" value="{{ $location->reward_resources }}" required></td>
                    <td><input type="number" min="0" name="locations[{{ $location->id }}][reward_prestige]" value="{{ $location->reward_prestige }}" required></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="panel" style="margin-bottom:14px">
        <h2>Rozšíření Mraveniště</h2>
        <div class="grid">
            @foreach([5, 7, 10] as $rooms)
                @php($key = 'anthill.expansion.' . $rooms . '.cost_resources')
                <div>
                    <label>Rozšíření na {{ $rooms }} komůrek</label>
                    <input type="number" min="0" name="settings[{{ $key }}]" value="{{ $settings[$key] ?? 0 }}" required>
                </div>
            @endforeach
        </div>
    </div>

    <div class="panel" style="margin-bottom:14px">
        <h2>Stavby</h2>
        <table>
            <thead><tr><th>Budova</th><th>Cena v surovinách</th></tr></thead>
            <tbody>
            @foreach($buildings as $building)
                <tr>
                    <td><b>{{ $building->name }}</b><br><span class="small muted">{{ $building->slug }}</span></td>
                    <td><input type="number" min="0" name="buildings[{{ $building->id }}][cost_resources]" value="{{ $building->cost_resources }}" required></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="panel" style="margin-bottom:14px">
        <h2>Vedlejší úkoly</h2>
        <p class="small muted">Zatím mají typicky 150 prestiže a 0 surovin, ale jde upravit každý samostatně.</p>
        <table>
            <thead><tr><th>Podúkol</th><th>Prestiž</th><th>Suroviny</th></tr></thead>
            <tbody>
            @foreach($buildingTasks as $task)
                <tr>
                    <td><b>{{ $task->building_name }}</b><br><span class="small">{{ $task->title }}</span></td>
                    <td><input type="number" min="0" name="building_tasks[{{ $task->id }}][reward_prestige]" value="{{ $task->reward_prestige }}" required></td>
                    <td><input type="number" min="0" name="building_tasks[{{ $task->id }}][reward_resources]" value="{{ $task->reward_resources }}" required></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="panel" style="margin-bottom:14px">
        <h2>Odznáčky</h2>
        <table>
            <thead><tr><th>Odznáček</th><th>Bonus prestiže</th></tr></thead>
            <tbody>
            @foreach($badges as $badge)
                <tr>
                    <td><b>{{ $badge->name }}</b><br><span class="small muted">{{ $badge->slug }}</span></td>
                    <td><input type="number" min="0" name="badges[{{ $badge->id }}][prestige_bonus]" value="{{ $badge->prestige_bonus }}" required></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h2>Pravidla</h2>
        <div class="grid">
            <div>
                <label>Prestiž po použití nápovědy (%)</label>
                <input type="number" min="0" max="100" name="settings[hint.prestige_multiplier_percent]" value="{{ $settings['hint.prestige_multiplier_percent'] ?? 50 }}" required>
            </div>
            <div>
                <label>Prestiž za běžný odznáček</label>
                <input type="number" min="0" name="settings[badge.location.prestige_bonus]" value="{{ $settings['badge.location.prestige_bonus'] ?? 0 }}" required>
            </div>
            <div>
                <label>Prestiž za vedlejší odznáček</label>
                <input type="number" min="0" name="settings[badge.building_task.prestige_bonus]" value="{{ $settings['badge.building_task.prestige_bonus'] ?? 0 }}" required>
            </div>
            <div>
                <label>Prestiž za top 10 odznáček</label>
                <input type="number" min="0" name="settings[badge.top10.prestige_bonus]" value="{{ $settings['badge.top10.prestige_bonus'] ?? 25 }}" required>
            </div>
            <div>
                <label>Prestiž za speciální odznáček</label>
                <input type="number" min="0" name="settings[badge.special.prestige_bonus]" value="{{ $settings['badge.special.prestige_bonus'] ?? 50 }}" required>
            </div>
        </div>
    </div>

    <p><button class="primary">Uložit ekonomiku</button></p>
</form>
@endsection
