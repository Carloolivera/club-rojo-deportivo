{{-- Public landing page. All copy lives in lang/{es,en,pt}/landing.php --}}
@php
  use Illuminate\Support\Str;

  $locale    = app()->getLocale();
  $founded   = config('club.founded');
  $years     = now()->year - $founded;
  $languages = ['es' => 'ES', 'en' => 'EN', 'pt' => 'PT'];

  // Football leads the grid; the rest keep alphabetical order.
  $sports = $sports->sortBy(fn ($s) => Str::slug($s->name) === 'futbol' ? 0 : 1)->values();
  $showMore = $sports->count() < 5;
  // The first card spans two columns only when that fills the last row of the 4-column grid.
  $featureWide = ($sports->count() + ($showMore ? 1 : 0)) % 4 === 3;

  $icons = [
    'futbol' => '⚽', 'hockey' => '🏑', 'basquet' => '🏀', 'natacion' => '🏊',
    'tenis' => '🎾', 'voley' => '🏐', 'rugby' => '🏉', 'atletismo' => '🏃',
  ];

  // Sport names are stored in Spanish; translate known ones, keep the rest as typed.
  $sportName = fn ($sport) => trans()->has('landing.sports.names.' . Str::slug($sport->name))
      ? __('landing.sports.names.' . Str::slug($sport->name))
      : $sport->name;
  $sportDesc = fn ($sport) => trans()->has('landing.sports.desc.' . Str::slug($sport->name))
      ? __('landing.sports.desc.' . Str::slug($sport->name))
      : __('landing.sports.desc.default');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="robots" content="noindex, nofollow">
  <title>{{ __('landing.meta_title') }}</title>
  <meta name="description" content="{{ __('landing.meta_description') }}">
  <meta name="theme-color" content="#d71f28">
  <link rel="icon" href="/icons/escudo.svg" type="image/svg+xml">
  <link rel="manifest" href="/manifest.json">
  <style>
    @font-face { font-family: 'Clash Display'; src: url('/fonts/clash-display/ClashDisplay-Semibold.woff2') format('woff2'); font-weight: 600; font-display: swap; }
    @font-face { font-family: 'Clash Display'; src: url('/fonts/clash-display/ClashDisplay-Bold.woff2') format('woff2'); font-weight: 700; font-display: swap; }
    @font-face { font-family: 'Satoshi'; src: url('/fonts/satoshi/Satoshi-Regular.woff2') format('woff2'); font-weight: 400; font-display: swap; }

    :root {
      --red: #d71f28;
      --red-hi: #f0323b;
      --red-dk: #8f0d14;
      --ink: #0c0c0e;
      --ink-2: #151518;
      --ink-3: #1d1d21;
      --paper: #f6f3ee;
      --white: #ffffff;
      --muted: rgba(255,255,255,.62);
      --line: rgba(255,255,255,.10);
      --display: 'Clash Display', 'Arial Black', Arial, sans-serif;
      --body: 'Satoshi', system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
      --radius: 18px;
      --wrap: min(1180px, 100% - 40px);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body { font-family: var(--body); background: var(--ink); color: var(--white); line-height: 1.55; overflow-x: hidden; -webkit-font-smoothing: antialiased; }
    a { color: inherit; text-decoration: none; }
    img, svg { display: block; max-width: 100%; }
    .wrap { width: var(--wrap); margin-inline: auto; }
    :focus-visible { outline: 3px solid var(--white); outline-offset: 3px; border-radius: 6px; }
    .sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

    /* Demo ribbon */
    .demo-bar { background: var(--white); color: var(--ink); font-size: 13px; text-align: center; padding: 7px 16px; font-weight: 600; letter-spacing: .01em; }

    /* Nav */
    .nav { position: sticky; top: 0; z-index: 50; background: rgba(12,12,14,.82); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border-bottom: 1px solid var(--line); }
    .nav-in { display: flex; align-items: center; gap: 22px; height: 70px; }
    .brand { display: flex; align-items: center; gap: 12px; font-family: var(--display); font-weight: 700; font-size: 19px; letter-spacing: .01em; }
    .brand img { width: 36px; height: 43px; }
    .brand small { display: none; font-family: var(--body); font-weight: 400; font-size: 11px; color: var(--muted); letter-spacing: .14em; text-transform: uppercase; }
    .links { display: flex; gap: 26px; margin-left: auto; font-size: 15px; }
    .links a { color: var(--muted); transition: color .2s; white-space: nowrap; }
    .links a:hover { color: var(--white); }
    .langs { display: flex; border: 1px solid var(--line); border-radius: 999px; padding: 3px; }
    .langs a { font-size: 12px; font-weight: 700; padding: 6px 10px; border-radius: 999px; color: var(--muted); letter-spacing: .06em; }
    .langs a[aria-current="true"] { background: var(--white); color: var(--ink); }
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 10px; font-weight: 700; font-size: 15px; padding: 14px 24px; border-radius: 999px; border: 0; cursor: pointer; transition: transform .2s, background .2s, box-shadow .2s; font-family: var(--body); }
    .btn-red { background: var(--red); color: var(--white); box-shadow: 0 10px 30px -10px rgba(215,31,40,.8); }
    .btn-red:hover { background: var(--red-hi); transform: translateY(-2px); }
    .btn-ghost { background: transparent; color: var(--white); border: 1.5px solid rgba(255,255,255,.35); }
    .btn-ghost:hover { border-color: var(--white); transform: translateY(-2px); }
    .nav .btn { padding: 11px 18px; font-size: 14px; white-space: nowrap; }
    @media (min-width: 1280px) { .brand small { display: block; } }
    .burger { display: none; margin-left: auto; background: none; border: 1px solid var(--line); color: var(--white); border-radius: 12px; width: 44px; height: 44px; font-size: 20px; cursor: pointer; }

    /* Hero */
    .hero { position: relative; overflow: hidden; padding: 72px 0 0; isolation: isolate; }
    .hero::before { content: ""; position: absolute; inset: -20% -10% auto auto; width: 70vw; height: 140%; background: repeating-linear-gradient(115deg, var(--red) 0 70px, var(--red-dk) 70px 140px); transform: skewX(-14deg) translateX(18%); z-index: -2; opacity: .95; }
    .hero::after { content: ""; position: absolute; inset: 0; background: radial-gradient(60% 70% at 15% 40%, var(--ink) 40%, transparent 75%); z-index: -1; }
    .hero-grid { display: grid; grid-template-columns: 1.15fr .85fr; gap: 40px; align-items: center; min-height: 560px; }
    .kicker { display: inline-flex; align-items: center; gap: 10px; font-size: 13px; letter-spacing: .16em; text-transform: uppercase; color: var(--muted); font-weight: 600; }
    .kicker::before { content: ""; width: 28px; height: 3px; background: var(--red); border-radius: 3px; }
    h1 { font-family: var(--display); font-weight: 700; font-size: clamp(54px, 9vw, 124px); line-height: .9; letter-spacing: -.02em; margin: 22px 0 26px; text-transform: uppercase; }
    h1 .r { color: var(--red); -webkit-text-stroke: 0; }
    h1 .o { color: transparent; -webkit-text-stroke: 2px var(--white); }
    .lead { font-size: clamp(17px, 1.6vw, 20px); color: var(--muted); max-width: 520px; }
    .ctas { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 34px; }
    .crest-stage { position: relative; display: grid; place-items: center; }
    .crest-stage img { width: min(360px, 70vw); height: auto; filter: drop-shadow(0 40px 60px rgba(0,0,0,.55)); animation: float 6s ease-in-out infinite; }
    .crest-ring { position: absolute; width: min(470px, 90vw); aspect-ratio: 1; border: 1.5px dashed rgba(255,255,255,.35); border-radius: 50%; animation: spin 40s linear infinite; }
    @keyframes float { 50% { transform: translateY(-14px) rotate(-1.5deg); } }
    @keyframes spin { to { transform: rotate(360deg); } }

    .stats { margin-top: 70px; display: grid; grid-template-columns: repeat(4, 1fr); background: var(--white); color: var(--ink); border-radius: var(--radius) var(--radius) 0 0; }
    .stat { padding: 28px 24px; border-right: 1px solid #e7e2da; }
    .stat:last-child { border-right: 0; }
    .stat b { display: block; font-family: var(--display); font-size: clamp(34px, 4vw, 52px); line-height: 1; color: var(--red); }
    .stat span { font-size: 14px; color: #55524d; }

    /* Marquee */
    .marquee { background: var(--red); overflow: hidden; border-block: 1px solid var(--red-dk); }
    .marquee-track { display: flex; gap: 46px; width: max-content; padding: 16px 0; animation: slide 30s linear infinite; font-family: var(--display); font-weight: 700; font-size: 22px; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
    .marquee-track span::after { content: "✦"; margin-left: 46px; opacity: .6; }
    @keyframes slide { to { transform: translateX(-50%); } }

    /* Sections */
    section { padding: 110px 0; }
    .tag { font-size: 13px; text-transform: uppercase; letter-spacing: .16em; color: var(--red-hi); font-weight: 700; }
    h2 { font-family: var(--display); font-weight: 700; font-size: clamp(36px, 5vw, 64px); line-height: 1; letter-spacing: -.015em; margin: 14px 0 18px; text-transform: uppercase; }
    .sub { color: var(--muted); max-width: 560px; font-size: 18px; }

    .sports-grid { margin-top: 52px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    .sport { position: relative; background: var(--ink-2); border: 1px solid var(--line); border-radius: var(--radius); padding: 26px 24px 28px; min-height: 230px; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; transition: transform .3s, border-color .3s, background .3s; }
    .sport::after { content: ""; position: absolute; right: -40px; top: -40px; width: 120px; height: 120px; background: var(--red); border-radius: 50%; opacity: .0; transition: opacity .3s, transform .4s; }
    .sport:hover { transform: translateY(-6px); border-color: rgba(215,31,40,.6); background: var(--ink-3); }
    .sport:hover::after { opacity: .18; transform: scale(1.4); }
    .sport .ico { font-size: 40px; line-height: 1; }
    .sport h3 { font-family: var(--display); font-size: 26px; text-transform: uppercase; margin: 22px 0 8px; letter-spacing: .01em; }
    .sport p { font-size: 14.5px; color: var(--muted); }
    .sport.feature.wide { grid-column: span 2; }
    .sport.feature { background: linear-gradient(135deg, var(--red) 0%, var(--red-dk) 100%); border-color: transparent; }
    .sport.feature p { color: rgba(255,255,255,.85); max-width: 360px; }
    .sport.feature.wide h3 { font-size: 40px; }
    .sport.more { border-style: dashed; background: transparent; }

    /* Join */
    .join { background: var(--paper); color: var(--ink); border-radius: 36px; margin-inline: 12px; }
    .join .sub { color: #55524d; }
    .join-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: start; margin-top: 10px; }
    .steps { list-style: none; display: grid; gap: 14px; margin-top: 34px; counter-reset: step; }
    .steps li { display: grid; grid-template-columns: 56px 1fr; gap: 16px; align-items: start; background: var(--white); border-radius: 16px; padding: 18px; counter-increment: step; }
    .steps li::before { content: counter(step, decimal-leading-zero); font-family: var(--display); font-weight: 700; font-size: 22px; color: var(--white); background: var(--red); border-radius: 12px; display: grid; place-items: center; height: 56px; }
    .steps b { display: block; font-size: 17px; }
    .steps span { color: #55524d; font-size: 15px; }
    .form { background: var(--ink); color: var(--white); border-radius: 26px; padding: 34px; box-shadow: 0 30px 60px -30px rgba(0,0,0,.5); }
    .field { display: grid; gap: 8px; margin-bottom: 18px; }
    .field label { font-size: 14px; font-weight: 600; }
    .field input, .field select { width: 100%; font: inherit; font-size: 16px; color: var(--white); background: var(--ink-3); border: 1.5px solid var(--line); border-radius: 12px; padding: 14px 16px; transition: border-color .2s; }
    .field input:focus, .field select:focus { outline: none; border-color: var(--red-hi); }
    .field select option { color: var(--ink); }
    .form .btn { width: 100%; margin-top: 6px; font-size: 16px; padding: 16px; }
    .form .btn[disabled] { opacity: .6; cursor: wait; }
    .msg { margin-top: 16px; padding: 14px 16px; border-radius: 12px; font-size: 15px; display: none; }
    .msg.ok { display: block; background: rgba(46, 160, 67, .18); border: 1px solid rgba(46,160,67,.6); }
    .msg.err { display: block; background: rgba(215, 31, 40, .18); border: 1px solid rgba(215,31,40,.6); }

    /* Portal */
    .portal-card { position: relative; overflow: hidden; display: grid; grid-template-columns: 1.3fr .7fr; gap: 30px; align-items: center; background: var(--ink-2); border: 1px solid var(--line); border-radius: 30px; padding: 56px; }
    .portal-card::before { content: ""; position: absolute; right: -80px; bottom: -120px; width: 380px; height: 380px; background: repeating-linear-gradient(115deg, var(--red) 0 26px, transparent 26px 52px); opacity: .25; border-radius: 50%; }
    .dni { position: relative; justify-self: end; background: var(--white); color: var(--ink); border-radius: 20px; padding: 22px 24px; width: 100%; max-width: 300px; transform: rotate(3deg); box-shadow: 0 30px 50px -20px rgba(0,0,0,.6); }
    .dni small { display: block; font-size: 12px; color: #77736c; letter-spacing: .1em; text-transform: uppercase; }
    .dni b { display: block; font-family: var(--display); font-size: 34px; letter-spacing: .06em; margin: 6px 0 14px; }
    .dni .ok { display: inline-flex; gap: 8px; align-items: center; background: #e6f4ea; color: #1e7b34; font-weight: 700; padding: 8px 12px; border-radius: 999px; font-size: 14px; }

    /* History */
    .timeline { margin-top: 50px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; border-top: 2px solid var(--red); }
    .timeline li { list-style: none; padding: 26px 22px 0 0; position: relative; }
    .timeline li::before { content: ""; position: absolute; top: -9px; left: 0; width: 16px; height: 16px; background: var(--ink); border: 3px solid var(--red); border-radius: 50%; }
    .timeline b { display: block; font-family: var(--display); font-size: 44px; color: var(--white); line-height: 1; margin-bottom: 10px; }
    .timeline span { color: var(--muted); font-size: 15px; }

    /* Footer */
    footer { border-top: 1px solid var(--line); padding: 60px 0 40px; color: var(--muted); font-size: 15px; }
    .foot-grid { display: grid; grid-template-columns: 1.4fr 1fr 1fr; gap: 40px; }
    footer h4 { color: var(--white); font-size: 14px; text-transform: uppercase; letter-spacing: .12em; margin-bottom: 14px; }
    footer ul { list-style: none; display: grid; gap: 8px; }
    footer a:hover { color: var(--white); }
    .foot-bottom { margin-top: 48px; padding-top: 22px; border-top: 1px solid var(--line); display: flex; justify-content: space-between; gap: 20px; flex-wrap: wrap; font-size: 13px; }

    /* Reveal on scroll */
    .reveal { opacity: 0; transform: translateY(26px); transition: opacity .7s ease, transform .7s ease; }
    .reveal.in { opacity: 1; transform: none; }
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { animation: none !important; transition: none !important; }
      .reveal { opacity: 1; transform: none; }
    }

    /* Responsive */
    @media (max-width: 1080px) {
      .links, .nav .btn-red { display: none; }
      .burger { display: block; }
      .nav.open .links { display: flex; position: absolute; top: 70px; left: 0; right: 0; flex-direction: column; gap: 0; background: var(--ink); border-bottom: 1px solid var(--line); padding: 8px 20px 20px; }
      .nav.open .links a { padding: 14px 0; border-bottom: 1px solid var(--line); font-size: 18px; color: var(--white); }
      .hero-grid, .join-grid, .portal-card, .foot-grid { grid-template-columns: 1fr; }
      .hero::before { width: 120vw; opacity: .55; }
      .hero { padding-top: 34px; }
      .hero-grid { min-height: 0; gap: 8px; }
      .crest-stage { order: -1; justify-items: start; }
      .crest-stage img { width: 112px; animation: none; }
      .crest-ring { display: none; }
      .stats { margin-top: 44px; }
      .stats { grid-template-columns: repeat(2, 1fr); }
      .stat:nth-child(2) { border-right: 0; }
      .stat:nth-child(-n+2) { border-bottom: 1px solid #e7e2da; }
      .sports-grid { grid-template-columns: 1fr 1fr; }
      .timeline { grid-template-columns: 1fr 1fr; row-gap: 40px; }
      .portal-card { padding: 34px 24px; }
      .dni { justify-self: start; }
      section { padding: 80px 0; }
    }
    @media (max-width: 560px) {
      .sports-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
      .sport { min-height: 168px; padding: 18px 16px; }
      .sport .ico { font-size: 30px; }
      .sport h3, .sport.feature.wide h3 { font-size: 19px; margin-top: 14px; }
      .sport p { font-size: 13px; }
      .sport.feature.wide { grid-column: auto; }
      .langs a { padding: 6px 8px; }
      .brand span:not(.sr-only) small { display: none; }
      .form { padding: 24px; }
      .timeline { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  <div class="demo-bar" role="note">{{ __('landing.demo_notice') }}</div>

  <header class="nav" id="nav">
    <div class="wrap nav-in">
      <a href="{{ route('landing') }}" class="brand" aria-label="{{ config('club.name') }}">
        <img src="/icons/escudo.svg" alt="" width="36" height="43">
        <span>{{ config('club.name') }}<small>{{ __('landing.footer.about', ['year' => $founded]) }}</small></span>
      </a>

      <nav class="links" id="links" aria-label="{{ __('landing.nav.menu') }}">
        <a href="#deportes">{{ __('landing.nav.sports') }}</a>
        <a href="#asociate">{{ __('landing.nav.join') }}</a>
        <a href="#cuota">{{ __('landing.nav.fees') }}</a>
        <a href="#historia">{{ __('landing.nav.history') }}</a>
      </nav>

      <div class="langs" role="group" aria-label="{{ __('landing.nav.language') }}">
        @foreach ($languages as $code => $label)
          <a href="{{ route('locale.switch', $code) }}" hreflang="{{ $code }}" lang="{{ $code }}"
             aria-current="{{ $locale === $code ? 'true' : 'false' }}">{{ $label }}</a>
        @endforeach
      </div>

      <a href="#asociate" class="btn btn-red">{{ __('landing.nav.cta') }}</a>
      <button class="burger" id="burger" aria-expanded="false" aria-controls="links">
        <span aria-hidden="true">☰</span><span class="sr-only">{{ __('landing.nav.menu') }}</span>
      </button>
    </div>
  </header>

  <main>
    {{-- HERO --}}
    <div class="hero">
      <div class="wrap hero-grid">
        <div>
          <div class="kicker reveal">{{ __('landing.hero.kicker') }}</div>
          <h1 class="reveal">
            {{ __('landing.hero.line1') }}<br>
            <span class="o">{{ __('landing.hero.line2') }}</span><br>
            <span class="r">{{ __('landing.hero.line3') }}</span>
          </h1>
          <p class="lead reveal">{{ __('landing.hero.sub') }}</p>
          <div class="ctas reveal">
            <a href="#asociate" class="btn btn-red">{{ __('landing.hero.cta') }} <span aria-hidden="true">→</span></a>
            <a href="#deportes" class="btn btn-ghost">{{ __('landing.hero.cta2') }}</a>
          </div>
        </div>
        <div class="crest-stage" aria-hidden="true">
          <div class="crest-ring"></div>
          <img src="/icons/escudo.svg" alt="" width="360" height="432">
        </div>
      </div>

      <div class="wrap stats reveal">
        <div class="stat"><b data-count="{{ $years }}">{{ $years }}</b><span>{{ __('landing.stats.years') }}</span></div>
        <div class="stat"><b data-count="{{ $sports->count() }}">{{ $sports->count() }}</b><span>{{ __('landing.stats.sports') }}</span></div>
        <div class="stat"><b data-count="{{ $activeMembers }}">{{ number_format($activeMembers, 0, ',', '.') }}</b><span>{{ __('landing.stats.members') }}</span></div>
        <div class="stat"><b>{{ $founded }}</b><span>{{ __('landing.stats.founded') }}</span></div>
      </div>
    </div>

    @if ($sports->isNotEmpty())
      <div class="marquee" aria-hidden="true">
        <div class="marquee-track">
          @foreach ([1, 2] as $copy)
            @foreach ($sports as $sport)<span>{{ $sportName($sport) }}</span>@endforeach
          @endforeach
        </div>
      </div>
    @endif

    {{-- SPORTS --}}
    <section id="deportes">
      <div class="wrap">
        <div class="tag reveal">{{ __('landing.sports.tag') }}</div>
        <h2 class="reveal">{{ __('landing.sports.title') }}</h2>
        <p class="sub reveal">{{ __('landing.sports.sub') }}</p>

        <div class="sports-grid">
          @foreach ($sports as $sport)
            <article class="sport reveal {{ $loop->first ? 'feature' : '' }} {{ $loop->first && $featureWide ? 'wide' : '' }}">
              <div class="ico" aria-hidden="true">{{ $icons[Str::slug($sport->name)] ?? '🏅' }}</div>
              <div>
                <h3>{{ $sportName($sport) }}</h3>
                <p>{{ $sportDesc($sport) }}</p>
              </div>
            </article>
          @endforeach

          @if ($showMore)
            <article class="sport more reveal">
              <div class="ico" aria-hidden="true">➕</div>
              <div>
                <h3>{{ __('landing.sports.more_title') }}</h3>
                <p>{{ __('landing.sports.more_text') }}</p>
              </div>
            </article>
          @endif
        </div>
      </div>
    </section>

    {{-- JOIN --}}
    <section id="asociate" class="join">
      <div class="wrap join-grid">
        <div>
          <div class="tag reveal">{{ __('landing.join.tag') }}</div>
          <h2 class="reveal">{{ __('landing.join.title') }}</h2>
          <ol class="steps">
            <li class="reveal"><div><b>{{ __('landing.join.step1_t') }}</b><span>{{ __('landing.join.step1_d') }}</span></div></li>
            <li class="reveal"><div><b>{{ __('landing.join.step2_t') }}</b><span>{{ __('landing.join.step2_d') }}</span></div></li>
            <li class="reveal"><div><b>{{ __('landing.join.step3_t') }}</b><span>{{ __('landing.join.step3_d') }}</span></div></li>
          </ol>
        </div>

        <form class="form reveal" id="join-form" action="{{ route('landing.contact') }}" method="post" novalidate
              data-sending="{{ __('landing.join.sending') }}" data-sent="{{ __('landing.join.sent') }}"
              data-error="{{ __('landing.join.error') }}" data-network="{{ __('landing.join.network') }}"
              data-too-many="{{ __('landing.join.too_many') }}">
          @csrf
          <div class="field">
            <label for="f-name">{{ __('landing.join.name') }}</label>
            <input id="f-name" name="name" type="text" maxlength="100" autocomplete="name" required placeholder="{{ __('landing.join.name_ph') }}">
          </div>
          <div class="field">
            <label for="f-phone">{{ __('landing.join.phone') }}</label>
            <input id="f-phone" name="phone" type="tel" maxlength="30" autocomplete="tel" inputmode="tel" required placeholder="{{ __('landing.join.phone_ph') }}">
          </div>
          <div class="field">
            <label for="f-sport">{{ __('landing.join.sport') }}</label>
            <select id="f-sport" name="sport">
              <option value="">{{ __('landing.join.sport_ph') }}</option>
              @foreach ($sports as $sport)
                <option value="{{ $sport->name }}">{{ $sportName($sport) }}</option>
              @endforeach
            </select>
          </div>
          <button type="submit" class="btn btn-red">{{ __('landing.join.submit') }}</button>
          <div class="msg" id="join-msg" role="status" aria-live="polite"></div>
        </form>
      </div>
    </section>

    {{-- MEMBER PORTAL --}}
    <section id="cuota">
      <div class="wrap">
        <div class="portal-card reveal">
          <div>
            <div class="tag">{{ __('landing.portal.tag') }}</div>
            <h2>{{ __('landing.portal.title') }}</h2>
            <p class="sub">{{ __('landing.portal.text') }}</p>
            <div class="ctas"><a href="{{ route('portal.estado') }}" class="btn btn-red">{{ __('landing.portal.cta') }} <span aria-hidden="true">→</span></a></div>
          </div>
          <div class="dni" aria-hidden="true">
            <small>DNI</small>
            <b>30.•••.•••</b>
            <span class="ok">✓ {{ __('landing.portal.ok') }}</span>
          </div>
        </div>
      </div>
    </section>

    {{-- HISTORY --}}
    <section id="historia" style="padding-top: 20px;">
      <div class="wrap">
        <div class="tag reveal">{{ __('landing.history.tag') }}</div>
        <h2 class="reveal">{{ __('landing.history.title') }}</h2>
        <ol class="timeline">
          @foreach (__('landing.history.items') as $item)
            <li class="reveal"><b>{{ $item['year'] }}</b><span>{{ $item['text'] }}</span></li>
          @endforeach
        </ol>
      </div>
    </section>
  </main>

  <footer>
    <div class="wrap">
      <div class="foot-grid">
        <div>
          <a href="{{ route('landing') }}" class="brand" style="margin-bottom:14px;">
            <img src="/icons/escudo.svg" alt="" width="36" height="43">
            <span>{{ config('club.name') }}</span>
          </a>
          <p>{{ __('landing.footer.about', ['year' => $founded]) }}</p>
          <p style="margin-top:8px;">{{ __('landing.demo_notice') }}</p>
        </div>
        <div>
          <h4>{{ __('landing.footer.sports') }}</h4>
          <ul>
            @foreach ($sports->take(6) as $sport)<li>{{ $sportName($sport) }}</li>@endforeach
          </ul>
        </div>
        <div>
          <h4>{{ __('landing.footer.contact') }}</h4>
          <ul>
            <li>{{ config('club.address') }}</li>
            <li>{{ config('club.phone') }}</li>
            <li><a href="{{ route('portal.estado') }}">{{ __('landing.nav.fees') }}</a></li>
            <li><a href="/admin">{{ __('landing.footer.admin') }}</a></li>
          </ul>
        </div>
      </div>
      <div class="foot-bottom">
        <span>© {{ date('Y') }} {{ config('club.name') }}. {{ __('landing.footer.rights') }}</span>
        <span>{{ __('landing.footer.built') }}</span>
      </div>
    </div>
  </footer>

  <script>
    (function () {
      // Mobile menu
      var nav = document.getElementById('nav');
      var burger = document.getElementById('burger');
      burger.addEventListener('click', function () {
        var open = nav.classList.toggle('open');
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      document.querySelectorAll('#links a').forEach(function (a) {
        a.addEventListener('click', function () { nav.classList.remove('open'); burger.setAttribute('aria-expanded', 'false'); });
      });

      // Reveal on scroll
      var items = document.querySelectorAll('.reveal');
      if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
          entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
        }, { threshold: 0.12 });
        items.forEach(function (el) { io.observe(el); });
      } else {
        items.forEach(function (el) { el.classList.add('in'); });
      }

      // Join form: sends JSON to /contacto, shows the result in place.
      var form = document.getElementById('join-form');
      var msg = document.getElementById('join-msg');
      var button = form.querySelector('button');
      var label = button.textContent;

      function show(text, ok) { msg.textContent = text; msg.className = 'msg ' + (ok ? 'ok' : 'err'); }

      form.addEventListener('submit', async function (ev) {
        ev.preventDefault();
        if (!form.reportValidity()) return;
        button.disabled = true;
        button.textContent = form.dataset.sending;
        msg.className = 'msg';
        try {
          var res = await fetch(form.action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                       'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ name: form.elements['name'].value.trim(), phone: form.elements['phone'].value.trim(), sport: form.elements['sport'].value })
          });
          if (res.ok) { show(form.dataset.sent, true); form.reset(); }
          else if (res.status === 429) { show(form.dataset.tooMany, false); }
          else { show(form.dataset.error, false); }
        } catch (e) {
          show(form.dataset.network, false);
        }
        button.disabled = false;
        button.textContent = label;
      });
    })();
  </script>
</body>
</html>
