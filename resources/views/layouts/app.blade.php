@php
    $me = auth()->user();
    $newMessages = $me ? DB::table('messages')->where('user_id', $me->id)->where('status', 'answered')->count() : 0;
    $badges = $me ? DB::table('user_badges')->join('badges', 'badges.id', '=', 'user_badges.badge_id')->where('user_id', $me->id)->select('badges.name', 'badges.description', 'badges.icon_path')->orderByDesc('user_badges.awarded_at')->get() : collect();
    $anthillUnlocked = $me ? DB::table('user_location_progress')
        ->join('locations', 'locations.id', '=', 'user_location_progress.location_id')
        ->where('user_location_progress.user_id', $me->id)
        ->where('user_location_progress.status', 'completed')
        ->where('locations.slug', 'ukol-2')
        ->exists() : false;
@endphp
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Prázdninová hra' }}</title>
    <style>
        :root { color-scheme: light; --ink:#1f2933; --muted:#65758b; --line:#d6dee8; --leaf:#276749; --amber:#b7791f; --sky:#e8f4f8; --panel:#fffaf0; --soft:#fff8df; --topbar-h:64px; --meadow-ratio:2; }
        * { box-sizing: border-box; } body { margin:0; font-family: system-ui, Segoe UI, sans-serif; color:var(--ink); background:#f5f7ef; }
        a { color:#235c80; text-decoration:none; font-weight:700; } a:hover { text-decoration:underline; }
        .top { display:flex; gap:14px; align-items:center; justify-content:space-between; padding:12px 18px; background:rgba(255,255,255,.92); border-bottom:1px solid var(--line); position:sticky; top:0; z-index:30; backdrop-filter:blur(12px); }
        .brand { font-weight:900; letter-spacing:.02em; } .nav-toggle { position:absolute; opacity:0; pointer-events:none; } .hamburger { display:none; } .nav { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
        .nav a, button, .btn { border:1px solid var(--line); background:#fff; padding:8px 11px; border-radius:7px; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; gap:6px; min-height:38px; }
        button.primary, .btn.primary { background:var(--leaf); color:white; border-color:var(--leaf); }
        main { max-width:1220px; margin:0 auto; padding:18px; display:grid; grid-template-columns:minmax(0, 1fr) 290px; gap:18px; }
        body.is-meadow .top { position:fixed; left:0; right:0; top:0; }
        body.is-meadow main, body.is-anthill main { max-width:none; min-height:100vh; padding:0; display:block; }
        body.is-meadow main > section { min-height:100vh; }
        body.is-anthill main > section { min-height:100vh; }
        body.is-auth { min-height:100vh; background:#b9df94 url('/assets/game/login-meadow-v3.png') center/cover fixed no-repeat; }
        body.is-auth::before { content:''; position:fixed; inset:0; background:linear-gradient(180deg, rgba(255,252,231,.22), rgba(177,220,136,.10) 42%, rgba(37,86,39,.16)); pointer-events:none; }
        body.is-auth .full { position:relative; z-index:1; min-height:100vh; display:grid; align-items:center; margin:0 auto; }
        .auth-card { position:relative; background:rgba(255,253,242,.94); border-color:rgba(255,255,255,.78); box-shadow:0 24px 70px rgba(12,35,19,.28); backdrop-filter:blur(10px); }
        .full { max-width:820px; margin:40px auto; padding:18px; } .panel, .card { background:white; border:1px solid var(--line); border-radius:8px; padding:16px; }
        .login-shell { min-height:calc(100vh - 80px); display:grid; grid-template-rows:1fr auto; gap:18px; }
        .login-stack { align-self:center; display:grid; gap:14px; }
        .auth-game-title { text-align:center; color:#17351f; text-shadow:0 2px 0 rgba(255,255,255,.62), 0 10px 30px rgba(20,52,24,.22); }
        .auth-game-title h1 { font-size:clamp(38px, 7vw, 76px); margin:0; font-weight:950; }
        .auth-game-title p { margin:6px 0 0; color:#24492b; font-weight:800; }
        .auth-footer { align-self:end; display:flex; flex-wrap:wrap; justify-content:center; gap:10px 18px; padding:12px 16px; border-radius:8px; border:1px solid rgba(255,255,255,.72); background:rgba(255,253,242,.86); box-shadow:0 14px 34px rgba(20,52,24,.18); backdrop-filter:blur(8px); color:#385143; font-weight:750; }
        .auth-footer span { color:#65758b; font-weight:700; } .auth-footer a { color:#235c80; font-weight:900; }
        .side { background:var(--panel); border:1px solid #ead9b7; border-radius:8px; padding:14px; align-self:start; position:sticky; top:74px; }
        body.is-meadow .side, body.is-anthill .side { position:fixed; right:18px; top:86px; z-index:35; width:116px; min-height:116px; padding:10px; border-radius:999px; background:rgba(255,250,240,.92); box-shadow:0 18px 48px rgba(40,55,29,.22); overflow:hidden; transition:width .18s ease, border-radius .18s ease, padding .18s ease; }
        body.is-meadow .side:hover, body.is-meadow .side:focus-within, body.is-anthill .side:hover, body.is-anthill .side:focus-within { width:300px; border-radius:18px; padding:14px; overflow:visible; }
        body.is-meadow .side h3, body.is-anthill .side h3 { text-align:center; margin-bottom:2px; }
        body.is-meadow .side > p, body.is-meadow .side .stats, body.is-meadow .side h3:nth-of-type(2), body.is-meadow .side .small + p, body.is-anthill .side > p, body.is-anthill .side .stats, body.is-anthill .side h3:nth-of-type(2), body.is-anthill .side .small + p { opacity:0; max-height:0; overflow:hidden; margin:0; transition:opacity .18s ease, max-height .18s ease, margin .18s ease; }
        body.is-meadow .side:hover > p, body.is-meadow .side:hover .stats, body.is-meadow .side:hover h3:nth-of-type(2), body.is-meadow .side:hover .small + p, body.is-meadow .side:focus-within > p, body.is-meadow .side:focus-within .stats, body.is-meadow .side:focus-within h3:nth-of-type(2), body.is-meadow .side:focus-within .small + p, body.is-anthill .side:hover > p, body.is-anthill .side:hover .stats, body.is-anthill .side:hover h3:nth-of-type(2), body.is-anthill .side:hover .small + p, body.is-anthill .side:focus-within > p, body.is-anthill .side:focus-within .stats, body.is-anthill .side:focus-within h3:nth-of-type(2), body.is-anthill .side:focus-within .small + p { opacity:1; max-height:240px; margin-top:10px; }
        .ant-avatar { width:94px; height:94px; border-radius:50%; display:grid; place-items:center; overflow:hidden; margin:0 auto 12px; border:3px solid #c49a5f; background:#fff7e8; box-shadow:0 8px 20px rgba(82,55,28,.18); }
        .ant-avatar img { width:100%; height:100%; object-fit:cover; }
        h1,h2,h3 { margin:0 0 12px; line-height:1.15; } p { color:var(--muted); line-height:1.55; }
        input, textarea, select { width:100%; border:1px solid var(--line); border-radius:7px; padding:10px; font:inherit; background:white; }
        label { display:block; font-weight:800; margin:10px 0 5px; } form.inline { display:inline; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px; }
        .stats { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:10px; } .stat { background:#f7fbff; border:1px solid var(--line); border-radius:7px; padding:10px; }
        .stats-help-button { grid-column:1 / -1; justify-self:end; width:30px; height:30px; min-height:30px; padding:0; border-radius:50%; justify-content:center; background:#fff7d6; border-color:#d8a928; color:#5a3b04; font-weight:900; box-shadow:0 6px 14px rgba(90,59,4,.12); }
        .stats-help-button:hover, .stats-help-button:focus { background:#ffe68a; text-decoration:none; }
        .stat-help-list { display:grid; gap:10px; margin:14px 0 0; }
        .stat-help-item { border-left:4px solid #d8a928; border-radius:8px; background:#fffdf0; padding:10px 12px; }
        .stat-help-item b { display:block; margin-bottom:3px; color:#2f4f2f; }
        .stat-help-item p { margin:0; }
        .badge-strip { display:flex; flex-wrap:wrap; gap:7px; margin-top:8px; } .badge-icon { width:42px; height:42px; border-radius:50%; border:0; background:transparent; object-fit:contain; padding:0; filter:drop-shadow(0 5px 8px rgba(82,55,28,.18)); }
        .badge-tip { position:relative; display:inline-flex; align-items:center; justify-content:center; }
        .badge-tip::after { content:attr(data-tooltip); position:absolute; left:50%; bottom:calc(100% + 8px); z-index:1200; width:max-content; max-width:min(260px, calc(100vw - 24px)); padding:8px 10px; border-radius:7px; background:#172033; color:#eef6ff; border:1px solid #2e3a52; box-shadow:0 12px 28px rgba(0,0,0,.22); font-size:12px; line-height:1.35; opacity:0; pointer-events:none; transform:translate(-50%, 4px); transition:.12s ease; }
        .badge-tip::before { content:''; position:absolute; left:50%; bottom:calc(100% + 2px); z-index:1201; border:6px solid transparent; border-top-color:#172033; opacity:0; transform:translateX(-50%); transition:.12s ease; }
        .badge-tip:hover::after, .badge-tip:focus::after, .badge-tip:hover::before, .badge-tip:focus::before { opacity:1; transform:translate(-50%, 0); }
        .flash { margin:0 0 12px; padding:10px 12px; border-radius:7px; border:1px solid; } .ok { background:#ecfdf3; border-color:#a6d8b7; } .err { background:#fff1f2; border-color:#f0a7b3; }
        .login-title { position:relative; display:flex; align-items:center; gap:10px; }
        .help-dot { width:38px; height:38px; border-radius:50%; padding:0; justify-content:center; background:radial-gradient(circle at 35% 30%, #fff7ba, #f4b942); border-color:#b88016; color:#4a3006; box-shadow:0 8px 18px rgba(75,47,5,.18); }
        .auth-card .help-dot { position:absolute; right:14px; top:14px; }
        .help-popover { position:absolute; right:0; left:auto; top:46px; z-index:5; width:min(420px, calc(100vw - 48px)); padding:14px; border:1px solid #d8b24a; border-radius:8px; background:#fffdf2; box-shadow:0 18px 44px rgba(55,42,15,.18); opacity:0; pointer-events:none; transform:translateY(-4px); transition:.12s ease; }
        .help-dot:hover + .help-popover, .help-dot:focus + .help-popover, .help-popover:hover { opacity:1; pointer-events:auto; transform:none; }
        .map { position:relative; min-height:620px; background:linear-gradient(#e7f7d7, #d4ebca); border:1px solid #b6cba7; border-radius:8px; overflow:hidden; }
        .meadow-hero { height:100vh; min-height:620px; padding:0; background:#badf95; position:relative; overflow:hidden; }
        .meadow-hero::after { content:''; position:absolute; inset:0; background:linear-gradient(180deg, rgba(255,255,255,.12), rgba(35,65,30,.08)); pointer-events:none; }
        .meadow-title { position:absolute; left:24px; top:92px; z-index:4; width:max-content; max-width:min(520px, calc(100vw - 48px)); padding:10px 14px; border-radius:999px; background:rgba(255,255,255,.78); border:1px solid rgba(255,255,255,.7); box-shadow:0 14px 34px rgba(49,79,34,.18); display:flex; align-items:center; gap:8px; }
        .meadow-title h1 { margin:0; font-size:28px; }
        .meadow-title p { display:none; position:absolute; left:0; top:52px; width:min(380px, calc(100vw - 36px)); margin:0; padding:12px 14px; border-radius:8px; background:#172033; color:#eaf2f9; box-shadow:0 16px 40px rgba(0,0,0,.24); }
        .meadow-title:hover p, .meadow-title:focus-within p { display:block; }
        .title-help { width:28px; height:28px; min-height:28px; padding:0; border-radius:50%; justify-content:center; }
        .meadow-board { position:absolute; z-index:1; left:0; right:0; top:var(--topbar-h); width:100vw; height:calc(100vh - var(--topbar-h)); min-height:556px; overflow:hidden; border-radius:0; background:#cfeeb8; box-shadow:none; }
        .meadow-board::after { content:''; position:absolute; inset:0; box-shadow:inset 0 0 0 1px rgba(255,255,255,.22), inset 0 -70px 120px rgba(30,61,25,.08); pointer-events:none; }
        .meadow-map { position:absolute; z-index:2; left:50%; top:50%; width:max(100vw, calc((100vh - var(--topbar-h)) * var(--meadow-ratio))); aspect-ratio:2 / 1; transform:translate(-50%, -50%); background:url('/assets/game/meadow-map-v9.png') center/100% 100% no-repeat; }
        .anthill-hotspot { position:absolute; z-index:3; left:36.640%; top:52.142%; width:10.259%; height:15.671%; transform:translate(-50%, -50%); display:block; background:url('/assets/game/stations-final/anthill-final-edgefade.png') center/100% 100% no-repeat; }
        .anthill-hotspot:hover { text-decoration:none; transform:translate(-50%, -50%); }
        .anthill-hotspot.locked { cursor:help; opacity:1; filter:none; }
        .loc { position:absolute; width:160px; transform:translate(-50%, -50%); text-align:center; }
        .meadow-map .loc { width:112px; }
        .meadow-map .loc-ukol-1 { left:52.813% !important; top:90.519% !important; width:13.833%; height:19.729%; }
        .meadow-map .loc-ukol-2 { left:34.470% !important; top:70.631% !important; width:13.021%; height:21.984%; }
        .meadow-map .loc-ukol-3 { left:41.441% !important; top:33.383% !important; width:12.608%; height:18.439%; }
        .meadow-map .loc-ukol-4 { left:67.522% !important; top:16.272% !important; width:9.339%; height:27.133%; }
        .meadow-map .loc-ukol-5 { left:56.342% !important; top:14.092% !important; width:11.330%; height:22.548%; }
        .meadow-map .loc-ukol-6 { left:65.699% !important; top:87.430% !important; width:11.330%; height:21.082%; }
        .meadow-map .loc-ukol-7 { left:55.496% !important; top:47.351% !important; width:12.007%; height:19.617%; }
        .meadow-map .loc-ukol-8 { left:66.762% !important; top:48.613% !important; width:10.503%; height:21.421%; }
        .meadow-map .loc-ukol-9 { left:66.681% !important; top:68.076% !important; width:15.520%; height:17.129%; }
        .meadow-map .loc-ukol-10 { left:35.791% !important; top:92.644% !important; width:15.340%; height:20.718%; }
        .meadow-map .loc-ukol-1 img, .meadow-map .loc-ukol-2 img, .meadow-map .loc-ukol-3 img, .meadow-map .loc-ukol-4 img, .meadow-map .loc-ukol-5 img, .meadow-map .loc-ukol-6 img, .meadow-map .loc-ukol-7 img, .meadow-map .loc-ukol-8 img, .meadow-map .loc-ukol-9 img, .meadow-map .loc-ukol-10 img { width:100%; height:100%; object-fit:fill; filter:none; }
        .meadow-map .loc-ukol-1 a:hover img, .meadow-map .loc-ukol-2 a:hover img, .meadow-map .loc-ukol-3 a:hover img, .meadow-map .loc-ukol-4 a:hover img, .meadow-map .loc-ukol-5 a:hover img, .meadow-map .loc-ukol-6 a:hover img, .meadow-map .loc-ukol-7 a:hover img, .meadow-map .loc-ukol-8 a:hover img, .meadow-map .loc-ukol-9 a:hover img, .meadow-map .loc-ukol-10 a:hover img { transform:none; filter:none; }
        .loc img { width:100%; height:auto; filter:drop-shadow(0 10px 14px rgba(55,80,35,.28)); transition:transform .16s ease, filter .16s ease; }
        .loc a:hover img { transform:translateY(-5px) scale(1.04); filter:drop-shadow(0 16px 18px rgba(55,80,35,.35)); }
        .loc span, .loc b { display:none; }
        .loc.locked { opacity:.46; filter:grayscale(.45); }
        .floating-tooltip { position:fixed; left:0; top:0; z-index:50; width:min(300px, calc(100vw - 24px)); background:#172033; color:white; border-radius:8px; padding:12px 14px; border:1px solid #2e3a52; box-shadow:0 18px 50px rgba(0,0,0,.28); pointer-events:none; opacity:0; transform:translate(12px, -100%); transition:opacity .08s ease; }
        .floating-tooltip.visible { opacity:1; }
        .floating-tooltip p { color:#d9e4f1; margin:4px 0 0; }
        .onboarding-backdrop { position:fixed; inset:0; z-index:900; display:grid; place-items:end start; padding:110px 24px 24px; background:rgba(15,23,42,.22); pointer-events:none; }
        .onboarding-card { position:relative; width:min(380px, calc(100vw - 48px)); border-radius:8px; border:1px solid #d8c27c; background:#fffdf0; box-shadow:0 22px 60px rgba(23,32,51,.28); padding:16px; pointer-events:auto; }
        .onboarding-step { display:none; } .onboarding-step.active { display:block; }
        .onboarding-step p { margin-bottom:12px; }
        .onboarding-arrow { display:none; position:absolute; z-index:2; color:#f6b12a; font-size:82px; line-height:1; font-weight:900; text-shadow:0 4px 0 #5a3b04, 0 12px 28px rgba(90,59,4,.38); pointer-events:none; }
        .onboarding-step.active .onboarding-arrow { display:block; }
        .onboarding-arrow.profile { right:-96px; top:-84px; transform:rotate(-24deg); }
        .onboarding-arrow.menu { left:50%; top:-92px; transform:translateX(-50%) rotate(-90deg); }
        .loc[data-state="locked"] a { pointer-events:none; color:inherit; }
        .anthill-scene { min-height:100vh; padding:108px 24px 32px; background:#8b6741; position:relative; overflow:hidden; display:grid; grid-template-rows:auto minmax(0, 1fr); gap:14px; }
        .anthill-title { position:relative; z-index:4; width:max-content; max-width:min(540px, calc(100vw - 48px)); padding:10px 14px; border-radius:999px; background:rgba(255,247,226,.84); border:1px solid rgba(255,255,255,.52); box-shadow:0 14px 34px rgba(61,39,18,.2); display:flex; align-items:center; gap:8px; }
        .anthill-title h1 { margin:0; font-size:28px; }
        .anthill-title p:not(.muted) { display:none; position:absolute; left:0; top:52px; width:min(380px, calc(100vw - 36px)); margin:0; padding:12px 14px; border-radius:8px; background:#172033; color:#eaf2f9; box-shadow:0 16px 40px rgba(0,0,0,.24); }
        .anthill-title:hover p:not(.muted), .anthill-title:focus-within p:not(.muted) { display:block; }
        .anthill-title .muted { position:absolute; left:0; top:58px; width:min(520px, calc(100vw - 48px)); margin:0; color:#fff8dc; text-shadow:0 1px 2px rgba(0,0,0,.35); }
        .anthill-board { position:relative; z-index:1; width:min(calc(100vw - 48px), calc((100vh - 172px) * 1)); max-width:1220px; aspect-ratio:1 / 1; align-self:center; justify-self:center; overflow:hidden; border-radius:8px; background:#8b6741 url('/assets/game/anthill-map-v2.png') center/100% 100% no-repeat; box-shadow:0 18px 50px rgba(40,25,12,.26); }
        .anthill-map { position:absolute; inset:0; z-index:2; border:0; border-radius:0; overflow:hidden; background:transparent; min-height:0; }
        .room { position:absolute; width:150px; min-height:116px; text-align:center; }
        .room img { width:100%; max-height:96px; object-fit:contain; filter:drop-shadow(0 8px 12px rgba(79,55,30,.24)); }
        .slot { display:flex; flex-direction:column; gap:8px; justify-content:space-between; }
        .modal-backdrop { position:fixed; inset:0; background:rgba(24,31,42,.72); z-index:1000; display:grid; place-items:center; padding:18px; overflow:auto; }
        .modal-window { max-width:560px; width:100%; max-height:calc(100vh - 36px); overflow:auto; background:white; border-radius:8px; border:1px solid var(--line); padding:22px; box-shadow:0 24px 80px rgba(0,0,0,.34); }
        .story-window { max-width:760px; max-height:calc(100vh - 36px); overflow:auto; }
        .story-window img { display:block; width:100%; border-radius:8px; margin:14px 0; }
        [hidden] { display:none !important; }
        .help-modal-title { display:flex; align-items:center; justify-content:space-between; gap:16px; }
        .icon-close { width:36px; height:36px; min-height:36px; padding:0; justify-content:center; border-radius:50%; font-size:20px; line-height:1; }
        .action-modal { display:none; position:fixed; inset:0; background:rgba(24,31,42,.62); z-index:40; place-items:center; padding:18px; }
        .action-modal.open { display:grid; }
        body:has(.modal-backdrop:not([hidden])) .top, body:has(.modal-backdrop:not([hidden])) .side { filter:grayscale(.85); opacity:.42; pointer-events:none; }
        .chat { display:flex; flex-direction:column; gap:10px; margin-top:12px; }
        .chat-shell { display:grid; grid-template-columns:220px minmax(0, 1fr); gap:12px; min-height:620px; }
        .thread-list { display:flex; flex-direction:column; gap:8px; }
        .thread-link { display:block; border:1px solid var(--line); border-radius:7px; padding:10px; background:#fff; font-weight:800; }
        .thread-link.active { background:#fff7d6; border-color:#d1a736; color:#3d2a05; }
        .thread-link.unread { border-color:#d33f49; box-shadow:0 0 0 3px rgba(211,63,73,.12); animation:threadPulse 1.2s ease-in-out infinite; }
        .unread-dot { float:right; width:10px; height:10px; border-radius:50%; background:#d33f49; margin-top:5px; }
        @keyframes threadPulse { 0%,100% { background:#fff; } 50% { background:#fff1f2; } }
        .chat-panel { display:flex; flex-direction:column; min-height:620px; }
        .chat-scroll { flex:1; overflow-y:auto; max-height:440px; padding:12px; border:1px solid var(--line); border-radius:8px; background:#fbfdff; }
        .chat-compose { border-top:1px solid var(--line); margin-top:12px; padding-top:12px; }
        .bubble { max-width:78%; border:1px solid var(--line); border-radius:8px; padding:10px 12px; background:#f8fafc; }
        .bubble.admin { align-self:flex-start; background:#fff8e6; }
        .bubble.player { align-self:flex-end; background:#e8f4f8; }
        .bubble .time { color:var(--muted); font-size:12px; margin-top:5px; }
        .rank-me { background:#fff7d6; border-color:#d1a736; } .muted { color:var(--muted); } .small { font-size:13px; } .row { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
        .leader-tree { position:relative; min-height:720px; padding:22px 18px 28px 70px; border-radius:8px; background:linear-gradient(#e9f7dd, #f8fbef); border:1px solid #c6d7b7; overflow:hidden; }
        .leader-tree::before { content:''; position:absolute; left:26px; top:38px; bottom:0; width:26px; border-radius:18px 18px 0 0; background:linear-gradient(90deg, #6d431f, #9c6936 45%, #5c3517); box-shadow:inset 8px 0 rgba(255,255,255,.08); }
        .leader-branch { position:relative; min-height:54px; margin:0 0 12px; display:flex; align-items:center; }
        .leader-branch::before { content:''; position:absolute; left:-36px; top:50%; width:64px; height:12px; border-radius:999px; background:linear-gradient(#99632f, #6b3f1d); transform:translateY(-50%); box-shadow:0 6px 12px rgba(81,49,22,.18); }
        .leader-leaf { position:relative; z-index:1; margin-left:26px; width:min(560px, 100%); padding:10px 14px; border-radius:999px; background:#fffdf0; border:1px solid #d8c27c; box-shadow:0 10px 22px rgba(82,98,44,.14); display:flex; justify-content:space-between; gap:12px; align-items:center; }
        .leader-leaf.rank-me { background:#fff3ba; border-color:#d8a928; }
        table { width:100%; border-collapse:collapse; background:white; } th,td { padding:9px; border-bottom:1px solid var(--line); text-align:left; vertical-align:top; }
        @media (max-width: 860px) { .login-shell { min-height:calc(100vh - 36px); } .auth-game-title h1 { font-size:38px; } .auth-footer { justify-content:flex-start; } main { grid-template-columns:1fr; padding:12px; } .side { position:static; } .top { align-items:center; flex-direction:row; } .hamburger { display:inline-grid; place-items:center; width:42px; height:38px; border:1px solid var(--line); border-radius:7px; background:white; font-size:24px; line-height:1; cursor:pointer; } .nav { display:none; position:absolute; left:12px; right:12px; top:calc(100% + 8px); padding:10px; border:1px solid var(--line); border-radius:8px; background:rgba(255,255,255,.97); box-shadow:0 18px 40px rgba(31,41,51,.18); } .nav-toggle:checked ~ .nav { display:flex; flex-direction:column; align-items:stretch; } .nav a, .nav button { width:100%; justify-content:flex-start; } .leader-tree { padding-left:62px; } .leader-tree::before { left:24px; } .leader-branch::before { left:-28px; width:52vw; } .leader-leaf { margin-left:34vw; min-width:0; width:calc(100% - 34vw); border-radius:8px; align-items:flex-start; flex-direction:column; } .chat-shell { grid-template-columns:1fr; min-height:auto; } .chat-panel { min-height:520px; } }
        @media (max-width: 860px) {
            body.is-meadow main, body.is-anthill main { display:block; }
            body.is-meadow .side, body.is-anthill .side { position:relative; top:auto; right:auto; bottom:auto; width:calc(100vw - 24px); min-height:0; margin:12px; border-radius:18px; padding:12px; display:grid; grid-template-columns:64px minmax(0,1fr); gap:6px 12px; overflow:visible; transition:none; }
            body.is-meadow .side:hover, body.is-meadow .side:focus-within, body.is-anthill .side:hover, body.is-anthill .side:focus-within { width:calc(100vw - 24px); border-radius:18px; padding:12px; }
            body.is-meadow .side .ant-avatar, body.is-anthill .side .ant-avatar { width:64px; height:64px; grid-row:1 / span 2; margin:0; }
            body.is-meadow .side h3:first-of-type, body.is-anthill .side h3:first-of-type { align-self:end; text-align:left; margin:0; }
            body.is-meadow .side > p.small, body.is-anthill .side > p.small { align-self:start; margin:0; }
            body.is-meadow .side .stats, body.is-anthill .side .stats { grid-column:1 / -1; display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:8px; margin-top:8px; }
            body.is-meadow .side .stat, body.is-anthill .side .stat { min-height:58px; text-align:center; display:grid; place-content:center; padding:8px 6px; }
            body.is-meadow .side > p:not(.small), body.is-anthill .side > p:not(.small) { grid-column:1 / -1; margin:2px 0 0; }
            body.is-meadow .side h3:nth-of-type(2), body.is-anthill .side h3:nth-of-type(2) { grid-column:1 / -1; text-align:left; margin:6px 0 0; }
            body.is-meadow .side h3:nth-of-type(2) + p, body.is-anthill .side h3:nth-of-type(2) + p { grid-column:1 / -1; }
            body.is-meadow .side > p, body.is-meadow .side .stats, body.is-meadow .side h3:nth-of-type(2), body.is-meadow .side .small + p, body.is-anthill .side > p, body.is-anthill .side .stats, body.is-anthill .side h3:nth-of-type(2), body.is-anthill .side .small + p { opacity:1; max-height:none; overflow:visible; transition:none; }
            body.is-anthill .top { position:sticky; }
            body.is-anthill main > section { min-height:0; }
            .map { min-height:540px; }
            .anthill-scene { height:auto; min-height:0; padding:12px; overflow:visible; display:block; }
            .anthill-title { position:relative; left:auto; top:auto; z-index:4; width:100%; max-width:none; margin:0 0 12px; border-radius:8px; }
            .anthill-board { width:100%; max-height:none; align-self:start; order:1; }
            .anthill-title p:not(.muted) { width:100%; }
            .anthill-title .muted { top:58px; width:100%; }
            .room { width:clamp(54px, 16vw, 92px); min-height:0; }
            .onboarding-arrow.profile { right:6px; top:-58px; font-size:58px; }
            .onboarding-arrow.menu { top:-64px; font-size:58px; }
        }
    </style>
</head>
<body class="{{ request()->is('palouk') ? 'is-meadow' : ((request()->is('mraveniste') || request()->is('pratele/*/mraveniste')) ? 'is-anthill' : (auth()->guest() ? 'is-auth' : '')) }}">
    @auth
        <header class="top">
            <div class="brand">Prázdninová hra</div>
            <input class="nav-toggle" type="checkbox" id="nav-toggle" aria-label="Otevřít menu">
            <label class="hamburger" for="nav-toggle" aria-hidden="true">☰</label>
            <nav class="nav">
                <a href="/palouk">Palouk</a>
                @if($anthillUnlocked)<a href="/mraveniste">Mraveniště</a>@endif
                <a href="/zebricek">Žebříček</a>
                <a href="/pratele">Přátelé</a>
                <a href="/zpravy">Zprávy</a>
                @if($me->role === 'admin') <a href="/admin">Admin</a> @endif
                @if(session('impersonator_admin_id'))
                    <form method="post" action="/admin/stop-impersonating" class="inline">@csrf <button class="primary">Zpět do admin účtu</button></form>
                @endif
                <form method="post" action="/logout" class="inline">@csrf <button>Odhlásit</button></form>
            </nav>
        </header>
        <main>
            <section>
                @if(session('success')) <div class="flash ok">{{ session('success') }}</div> @endif
                @if(session('error')) <div class="flash err">{{ session('error') }}</div> @endif
                @yield('content')
            </section>
            <aside class="side">
                <div class="ant-avatar"><img src="/assets/placeholders/ant-avatar.svg" alt="Tvůj mravenec"></div>
                <h3>{{ $me->display_name }}</h3>

                <div class="stats">
                    <button class="stats-help-button" type="button" data-stats-help-open aria-label="Co znamenají statistiky?">?</button>
                    <div class="stat"><b>{{ $me->colony_level }}</b><br><span class="small">úroveň</span></div>
                    <div class="stat"><b>{{ $me->prestige }}</b><br><span class="small">prestiž</span></div>
                    <div class="stat"><b>{{ $me->resources }}</b><br><span class="small">suroviny</span></div>
                </div>
                <p>Nové zprávy: <b>{{ $newMessages }}</b></p>
                <h3>Odznáčky</h3>
                @if($badges->isNotEmpty())
                    <div class="badge-strip">
                        @foreach($badges as $badge)
                            <span class="badge-tip" tabindex="0" data-tooltip="{{ $badge->description ?: $badge->name }}">
                                <img class="badge-icon" src="{{ $badge->icon_path ?: '/assets/badges/default.png' }}" alt="{{ $badge->name }}">
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="small">Zatím žádné.</p>
                @endif
            </aside>
        </main>
        <div class="modal-backdrop" id="stats-help-modal" hidden>
            <div class="modal-window" role="dialog" aria-modal="true" aria-labelledby="stats-help-title">
                <div class="help-modal-title">
                    <h2 id="stats-help-title">Co znamenají statistiky?</h2>
                    <button class="icon-close" type="button" data-stats-help-close aria-label="Zavřít">×</button>
                </div>
                <div class="stat-help-list">
                    <div class="stat-help-item">
                        <b>Úroveň kolonie</b>
                        <p>Ukazuje, jak moc se tvé mraveniště rozvíjí. Vyšší úroveň může odemykat další části hry, místnosti a nové možnosti.</p>
                    </div>
                    <div class="stat-help-item">
                        <b>Prestiž</b>
                        <p>Jsou to body slávy za splněné úkoly, odznáčky a důležité objevy. Pomáhá porovnat postup v žebříčku.</p>
                    </div>
                    <div class="stat-help-item">
                        <b>Suroviny</b>
                        <p>Materiál, který sbíráš při úkolech. Používá se hlavně na stavění a vylepšování mraveniště.</p>
                    </div>
                    <div class="stat-help-item">
                        <b>Nové zprávy</b>
                        <p>Počet odpovědí a zpráv, které na tebe čekají v poště.</p>
                    </div>
                    <div class="stat-help-item">
                        <b>Odznáčky</b>
                        <p>Ocenění za splněné výzvy a speciální úspěchy. Některé mohou přidat prestiž nebo připomenout důležitý okamžik hry.</p>
                    </div>
                </div>
                <p><button class="primary" type="button" data-stats-help-close>Rozumím</button></p>
            </div>
        </div>
    @else
        <div class="full">
            @yield('content')
        </div>
    @endauth
    @auth
        <script>
            (() => {
                const modal = document.getElementById('stats-help-modal');
                if (!modal) return;

                const openModal = () => modal.removeAttribute('hidden');
                const closeModal = () => modal.setAttribute('hidden', 'hidden');

                document.querySelectorAll('[data-stats-help-open]').forEach((button) => {
                    button.addEventListener('click', openModal);
                });
                document.querySelectorAll('[data-stats-help-close]').forEach((button) => {
                    button.addEventListener('click', closeModal);
                });
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) closeModal();
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !modal.hasAttribute('hidden')) closeModal();
                });
            })();
        </script>
    @endauth
</body>
</html>
