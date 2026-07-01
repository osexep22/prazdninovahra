@extends('layouts.app')
@section('content')
@if($intro)
    <div class="modal-backdrop">
        <div class="modal-window story-window">
            <h2>{{ $intro->title }}</h2>
            @if($intro->body_top)<p>{!! nl2br(e($intro->body_top)) !!}</p>@endif
            @if($intro->image_path)<img src="{{ $intro->image_path }}" alt="Úvodní příběh">@endif
            @if($intro->body_bottom)<p>{!! nl2br(e($intro->body_bottom)) !!}</p>@endif
            <form method="post" action="/intro/seen">@csrf<button class="primary">Vydat se na palouk</button></form>
        </div>
    </div>
@endif
@if($announcement)
    <div class="modal-backdrop">
        <div class="modal-window">
            <h2>{{ $announcement->title }}</h2>
            <p>{{ $announcement->body }}</p>
            <form method="post" action="/announcements/{{ $announcement->id }}/seen">@csrf<button class="primary">Rozumím</button></form>
        </div>
    </div>
@endif
<div id="floating-tooltip" class="floating-tooltip"></div>
@if($showOnboarding)
    <div class="onboarding-backdrop">
        <div class="onboarding-card">
            <div class="onboarding-step active" data-step="1">
                <h2>Toto je Palouk</h2>
                <p>Zde budeš plnit úkoly a sledovat svůj postup hrou.</p>
            </div>
            <div class="onboarding-step" data-step="2">
                <span class="onboarding-arrow profile" aria-hidden="true">➜</span>
                <h2>Tvoje kolonie</h2>
                <p>Toto je tvůj profil hráče. Zde uvidíš své statistiky a informace o své kolonii.</p>
            </div>
            <div class="onboarding-step" data-step="3">
                <span class="onboarding-arrow menu" aria-hidden="true">➜</span>
                <h2>Horní menu</h2>
                <p>Toto je hlavní menu. Najdeš zde přátele, zprávy, komunikaci s administrátory a další důležité části hry.</p>
            </div>
            <form method="post" action="/onboarding/palouk/seen" class="row">
                @csrf
                <button type="button" class="primary" data-onboarding-next>Další</button>
                <button>Přeskočit</button>
            </form>
        </div>
    </div>
@endif
<div class="meadow-hero">
    <div class="meadow-title">
        <h1>Palouk</h1>
        <button class="title-help" type="button" aria-label="Co je palouk?" data-palouk-help-open>?</button>
        <p>Palouk je mapa příběhu. Objevuj stanoviště, sbírej stopy a pomáhej mravenčí výpravě postavit nové zázemí.</p>
    </div>
    <div class="modal-backdrop" id="palouk-help-modal" hidden>
        <div class="modal-window">
            <div class="help-modal-title">
                <h2>Co je Palouk?</h2>
                <button class="icon-close" type="button" aria-label="Zavřít" data-palouk-help-close>×</button>
            </div>
            <p>Toto je Palouk - hlavní místo Prázdninové hry. Zde budeš postupně objevovat nová místa, plnit úkoly a sledovat svůj postup.</p>
            <p>Pokud narazíš na problém nebo si nebudeš vědět rady, kontaktuj nás přes administrátorské zprávy. Případně nám můžeš napsat na e-mail: xxx.</p>
            <p><button class="primary" type="button" data-palouk-help-close>Rozumím</button></p>
        </div>
    </div>
    <div class="meadow-board">
        <div class="meadow-map">
            @if($anthillUnlocked)
                <a class="anthill-hotspot" href="/mraveniste" aria-label="Přejít do mraveniště" data-description="Vchod do tvého mraveniště je otevřený. Pojď stavět nové místnosti."></a>
            @else
                <div class="anthill-hotspot locked" data-description="Mraveniště ještě není v obyvatelném stavu. Nejdřív bude potřeba sehnat dřevo a připravit první místnosti."></div>
            @endif
            @foreach($locations as $location)
                @continue($location->state === 'locked')
                @php
                    $asset = $location->image_path ?: ($location->state === 'completed' ? $location->svg_completed : $location->svg_available);
                @endphp
                <div class="loc loc-{{ $location->slug }} {{ $location->state }}" data-description="{{ $location->tooltip ?: $location->description }}" style="left:{{ $location->map_x }}%; top:{{ $location->map_y }}%;">
                    <a href="/palouk/{{ $location->slug }}"><img src="{{ $asset }}" alt="{{ $location->name }}"><span>{{ $location->name }}</span></a>
                </div>
            @endforeach
        </div>
    </div>
</div>
<script>
    const locationInfo = document.getElementById('floating-tooltip');
    const moveTooltip = (event) => {
        const width = locationInfo.offsetWidth || 300;
        const left = Math.min(event.clientX + 14, window.innerWidth - width - 12);
        const top = Math.max(12, event.clientY - 16);
        locationInfo.style.left = `${left}px`;
        locationInfo.style.top = `${top}px`;
    };
    document.querySelectorAll('.loc, .anthill-hotspot').forEach(location => {
        location.addEventListener('mouseenter', (event) => {
            locationInfo.innerHTML = `<p>${location.dataset.description}</p>`;
            locationInfo.classList.add('visible');
            moveTooltip(event);
        });
        location.addEventListener('mousemove', moveTooltip);
        location.addEventListener('mouseleave', () => locationInfo.classList.remove('visible'));
    });
</script>
<script>
    const paloukHelpModal = document.getElementById('palouk-help-modal');
    const openPaloukHelp = () => paloukHelpModal?.removeAttribute('hidden');
    const closePaloukHelp = () => paloukHelpModal?.setAttribute('hidden', 'hidden');
    document.querySelector('[data-palouk-help-open]')?.addEventListener('click', openPaloukHelp);
    document.querySelectorAll('[data-palouk-help-close]').forEach(button => button.addEventListener('click', closePaloukHelp));
    paloukHelpModal?.addEventListener('click', event => {
        if (event.target === paloukHelpModal) closePaloukHelp();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closePaloukHelp();
    });
</script>
@if($showOnboarding)
<script>
    const onboardingSteps = Array.from(document.querySelectorAll('.onboarding-step'));
    const onboardingNext = document.querySelector('[data-onboarding-next]');
    let onboardingIndex = 0;
    onboardingNext?.addEventListener('click', () => {
        onboardingSteps[onboardingIndex]?.classList.remove('active');
        onboardingIndex += 1;
        if (onboardingIndex >= onboardingSteps.length) {
            onboardingNext.closest('form').submit();
            return;
        }
        onboardingSteps[onboardingIndex]?.classList.add('active');
        onboardingNext.textContent = onboardingIndex === onboardingSteps.length - 1 ? 'Dokončit' : 'Další';
    });
</script>
@endif
@endsection
