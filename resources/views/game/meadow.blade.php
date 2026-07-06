@extends('layouts.app')
@section('content')
@php
    $welcomeTitle = $onboardingWelcome->title ?? 'Vítej v Prázdninové hře';
    $welcomeText = trim(implode("\n\n", array_filter([
        $onboardingWelcome->body_top ?? null,
        $onboardingWelcome->body_middle ?? null,
        $onboardingWelcome->body_bottom ?? null,
    ]))) ?: "Hraješ za mravenčí výpravu, která se ocitla na velkém palouku. Čekají tě šifry, stanoviště v okolí a postupné budování vlastního mraveniště.\n\nKaždé splněné stanoviště ti přinese suroviny, prestiž a posune příběh dál.";
@endphp
@if($intro)
    <div class="modal-backdrop">
        <div class="modal-window story-window">
            <h2>{{ $intro->title }}</h2>
            @if($intro->body_top)<p>{!! nl2br(e($intro->body_top)) !!}</p>@endif
            @if($intro->image_path)<img src="{{ $intro->image_path }}" alt="Úvodní příběh">@endif
            @if(($intro->body_middle ?? null))<p>{!! nl2br(e($intro->body_middle)) !!}</p>@endif
            @if(($intro->image_path_2 ?? null))<img src="{{ $intro->image_path_2 }}" alt="Mravenci na paloučku">@endif
            @if($intro->body_bottom)<p>{!! nl2br(e($intro->body_bottom)) !!}</p>@endif
            <form method="post" action="/intro/seen">@csrf<button class="primary">Vydat se na palouk</button></form>
        </div>
    </div>
@endif

<div id="floating-tooltip" class="floating-tooltip"></div>
<div class="modal-backdrop" id="welcome-help-modal" @if(!$showOnboarding) hidden @endif data-show-onboarding="{{ $showOnboarding ? '1' : '0' }}">
    <div class="modal-window story-window" role="dialog" aria-modal="true" aria-labelledby="welcome-help-title">
        <div class="help-modal-title">
            <h2 id="welcome-help-title">{{ $welcomeTitle }}</h2>
            <button class="icon-close" type="button" aria-label="Zavřít" data-welcome-help-close>×</button>
        </div>
        <p>{!! nl2br(e($welcomeText)) !!}</p>
        <p><button class="primary" type="button" data-welcome-help-close>{{ $showOnboarding ? 'Pokračovat' : 'Rozumím' }}</button></p>
    </div>
</div>
@if($showOnboarding)
    <div class="onboarding-backdrop" data-step="2" hidden>
        <div class="onboarding-card">
            <div class="onboarding-step active" data-step="2">
                <h2>Toto je Palouk</h2>
                <p>Tady začíná tvoje výprava. Hledej viditelná stanoviště na mapě, najeď na ně pro nápovědu a kliknutím otevři úkol.</p>
            </div>
            <div class="onboarding-step" data-step="3">
                <h2>Tvoje kolonie</h2>
                <p>Vpravo nahoře je mravenec s tvým jménem. Tady najdeš úroveň kolonie, prestiž, suroviny, nové zprávy a odznáčky.</p>
            </div>
            <div class="onboarding-step" data-step="4">
                <h2>Horní menu</h2>
                <p>Menu tě vezme do žebříčku, k přátelům, do zpráv a později také do mraveniště.</p>
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
        <p>Najdi některé ze stanovišť na palouku a klikni na něj. Když si nejsi jistý, nápověda ti dostupná místa zvýrazní žlutým kroužkem. Když si nebudeš vědět rady, napiš na shmhra2025@gmail.com.</p>
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
                    $tooltip = $location->state === 'completed' && ($location->tooltip_completed ?? null)
                        ? $location->tooltip_completed
                        : ($location->tooltip ?: $location->description);
                @endphp
                <div class="loc loc-{{ $location->slug }} {{ $location->state }}" data-description="{{ $tooltip }}" style="left:{{ $location->map_x }}%; top:{{ $location->map_y }}%;">
                    <a href="/palouk/{{ $location->slug }}"><img src="{{ $asset }}" alt="{{ $location->name }}"><span>{{ $location->name }}</span></a>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
(() => {
    const locationInfo = document.getElementById('floating-tooltip');
    const meadowMap = document.querySelector('.meadow-map');
    if (!locationInfo) return;

    const moveTooltip = (event) => {
        const width = locationInfo.offsetWidth || 300;
        const left = Math.min(event.clientX + 14, window.innerWidth - width - 12);
        const top = Math.max(12, event.clientY - 16);
        locationInfo.style.left = `${left}px`;
        locationInfo.style.top = `${top}px`;
    };

    const showTooltip = (location, event) => {
        locationInfo.replaceChildren();
        const text = document.createElement('p');
        text.textContent = location.dataset.description || '';
        locationInfo.appendChild(text);
        locationInfo.classList.add('visible');
        if (event) moveTooltip(event);
    };

    const hideTooltip = () => locationInfo.classList.remove('visible');

    document.querySelectorAll('.loc, .anthill-hotspot').forEach(location => {
        location.addEventListener('mouseenter', (event) => showTooltip(location, event));
        location.addEventListener('mousemove', moveTooltip);
        location.addEventListener('mouseleave', hideTooltip);
    });

    const isTouchLike = window.matchMedia('(hover: none), (pointer: coarse)').matches;
    if (isTouchLike) {
        document.querySelectorAll('.loc.available a, .loc.completed a').forEach(link => {
            link.addEventListener('click', event => {
                const location = link.closest('.loc');
                if (!location.classList.contains('tap-ready')) {
                    event.preventDefault();
                    document.querySelectorAll('.loc.tap-ready').forEach(item => item.classList.remove('tap-ready'));
                    location.classList.add('tap-ready');
                    const rect = location.getBoundingClientRect();
                    showTooltip(location, { clientX: rect.left + rect.width / 2, clientY: rect.top + rect.height / 2 });
                }
            });
        });
    }

    const welcomeHelpModal = document.getElementById('welcome-help-modal');
    let startGuideAfterWelcome = welcomeHelpModal?.dataset.showOnboarding === '1';
    const highlightStations = () => meadowMap?.classList.add('highlight-stations');
    const unhighlightStations = () => {
        if (welcomeHelpModal?.hasAttribute('hidden')) {
            meadowMap?.classList.remove('highlight-stations');
        }
    };
    const openWelcomeHelp = (continueToGuide = false) => {
        startGuideAfterWelcome = continueToGuide;
        meadowMap?.classList.add('highlight-stations');
        welcomeHelpModal?.removeAttribute('hidden');
    };
    const closeWelcomeHelp = () => {
        welcomeHelpModal?.setAttribute('hidden', 'hidden');
        meadowMap?.classList.remove('highlight-stations');
        if (startGuideAfterWelcome) {
            startGuideAfterWelcome = false;
            document.dispatchEvent(new CustomEvent('start-meadow-onboarding'));
        }
    };
    const paloukHelpButton = document.querySelector('[data-palouk-help-open]');
    const paloukTitle = document.querySelector('.meadow-title');
    paloukHelpButton?.addEventListener('click', () => openWelcomeHelp(false));
    paloukTitle?.addEventListener('mouseenter', highlightStations);
    paloukTitle?.addEventListener('focusin', highlightStations);
    paloukTitle?.addEventListener('mouseleave', unhighlightStations);
    paloukTitle?.addEventListener('focusout', unhighlightStations);
    document.querySelectorAll('[data-welcome-help-close]').forEach(button => button.addEventListener('click', closeWelcomeHelp));
    welcomeHelpModal?.addEventListener('click', event => {
        if (event.target === welcomeHelpModal) closeWelcomeHelp();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !welcomeHelpModal?.hasAttribute('hidden')) closeWelcomeHelp();
    });
})();
</script>

@if($showOnboarding)
<script>
(() => {
    const backdrop = document.querySelector('.onboarding-backdrop');
    const side = document.querySelector('.side');
    const onboardingSteps = Array.from(document.querySelectorAll('.onboarding-step'));
    const onboardingNext = document.querySelector('[data-onboarding-next]');
    let onboardingIndex = 0;

    const applyStepState = () => {
        const step = onboardingSteps[onboardingIndex]?.dataset.step || '1';
        backdrop?.setAttribute('data-step', step);
        side?.classList.toggle('force-open', step === '3');
        document.body.classList.toggle('onboarding-stats-focus', step === '3');
        document.body.classList.toggle('onboarding-menu-focus', step === '4');
        onboardingNext.textContent = onboardingIndex === onboardingSteps.length - 1 ? 'Dokončit' : 'Další';
    };

    onboardingNext?.addEventListener('click', () => {
        onboardingSteps[onboardingIndex]?.classList.remove('active');
        onboardingIndex += 1;
        if (onboardingIndex >= onboardingSteps.length) {
            onboardingNext.closest('form').submit();
            return;
        }
        onboardingSteps[onboardingIndex]?.classList.add('active');
        applyStepState();
    });

    document.addEventListener('start-meadow-onboarding', () => {
        backdrop?.removeAttribute('hidden');
        applyStepState();
    });

    applyStepState();
})();
</script>
@endif
@endsection
