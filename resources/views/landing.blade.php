{{-- Public landing page. Copy: lang/{es,en,pt}/landing.php · Styles: public/css/landing.css · Motion: public/js/landing.js --}}
@php
  use Illuminate\Support\Str;

  $locale    = app()->getLocale();
  $founded   = config('club.founded');
  $years     = now()->year - $founded;
  $languages = ['es' => 'ES', 'en' => 'EN', 'pt' => 'PT'];

  // Sports that have their own photo get a big card; the rest go in a compact row.
  $photos = ['futbol' => 'futbol', 'hockey' => 'hockey', 'natacion' => 'pileta', 'basquet' => 'basquet'];

  $sports   = $sports->sortBy(fn ($s) => Str::slug($s->name) === 'futbol' ? 0 : 1)->values();
  $featured = $sports->filter(fn ($s) => isset($photos[Str::slug($s->name)]))->values();
  $others   = $sports->reject(fn ($s) => isset($photos[Str::slug($s->name)]))->values();
  $showMore = $sports->count() < 5;

  $sportName = fn ($sport) => trans()->has('landing.sports.names.' . Str::slug($sport->name))
      ? __('landing.sports.names.' . Str::slug($sport->name))
      : $sport->name;
  $sportDesc = fn ($sport) => trans()->has('landing.sports.desc.' . Str::slug($sport->name))
      ? __('landing.sports.desc.' . Str::slug($sport->name))
      : __('landing.sports.desc.default');

  $whatsapp = config('club.whatsapp');
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
  <meta name="theme-color" content="#0b0b0d">
  <link rel="icon" href="/icons/escudo.svg" type="image/svg+xml">
  <link rel="manifest" href="/manifest.json">
  <link rel="preload" href="/fonts/club/anton.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/img/estadio.webp" as="image">
  <link rel="stylesheet" href="/css/landing.css?v=2">
  <script type="importmap">{ "imports": { "three": "/js/vendor/three.module.min.js" } }</script>
</head>
<body>

  <div class="demo-bar" role="note">{{ __('landing.demo_notice') }}</div>

  <header class="nav" id="nav">
    <div class="wrap nav-in">
      {{-- Five taps on the crest open the admin panel (no visible link). --}}
      <button type="button" class="brand" id="crest" data-admin="/admin" aria-label="{{ config('club.name') }}">
        <img src="/icons/escudo.svg" alt="" width="34" height="41">
        <span class="brand-txt">{{ config('club.name') }}<small>{{ __('landing.hero.kicker') }}</small></span>
      </button>

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

      <a href="#asociate" class="btn btn-red nav-cta">{{ __('landing.nav.cta') }}</a>
      <button class="burger" id="burger" aria-expanded="false" aria-controls="links">
        <span aria-hidden="true">☰</span><span class="sr-only">{{ __('landing.nav.menu') }}</span>
      </button>
    </div>
  </header>

  <main>
    {{-- HERO: 3D crest (canvas) behind the headline --}}
    <section class="hero" id="hero">
      <canvas id="crest3d" aria-hidden="true"></canvas>
      <div class="hero-shade" aria-hidden="true"></div>
      <div class="wrap hero-in">
        <p class="kicker">{{ __('landing.hero.kicker') }}</p>
        <h1 class="hero-title">
          <span class="line"><span>{{ __('landing.hero.line1') }}</span></span>
          <span class="line"><span class="o">{{ __('landing.hero.line2') }}</span></span>
          <span class="line"><span class="r">{{ __('landing.hero.line3') }}</span></span>
        </h1>
        <p class="lead">{{ __('landing.hero.sub') }}</p>
        <div class="ctas">
          <a href="#asociate" class="btn btn-red">{{ __('landing.hero.cta') }} <span aria-hidden="true">→</span></a>
          <a href="#deportes" class="btn btn-ghost">{{ __('landing.hero.cta2') }}</a>
        </div>
      </div>
      <a href="#estadio" class="scroll-cue" aria-label="{{ __('landing.hero.scroll') }}">
        <span>{{ __('landing.hero.scroll') }}</span><i aria-hidden="true"></i>
      </a>
    </section>

    {{-- STADIUM: parallax photo + counters --}}
    <section class="stadium" id="estadio">
      <div class="stadium-img" aria-hidden="true"><img src="/img/estadio.webp" alt="" width="1600" height="900" loading="eager"></div>
      <div class="wrap stats">
        <div class="stat"><b data-count="{{ $years }}">{{ $years }}</b><span>{{ __('landing.stats.years') }}</span></div>
        <div class="stat"><b data-count="{{ $sports->count() }}">{{ $sports->count() }}</b><span>{{ __('landing.stats.sports') }}</span></div>
        <div class="stat"><b data-count="{{ $activeMembers }}">{{ number_format($activeMembers, 0, ',', '.') }}</b><span>{{ __('landing.stats.members') }}</span></div>
        <div class="stat"><b>{{ $founded }}</b><span>{{ __('landing.stats.founded') }}</span></div>
      </div>
    </section>

    @if ($sports->isNotEmpty())
      <div class="marquee" aria-hidden="true">
        <div class="marquee-track">
          @foreach ([1, 2] as $copy)
            @foreach ($sports as $sport)<span>{{ $sportName($sport) }}</span>@endforeach
          @endforeach
        </div>
      </div>
    @endif

    {{-- SPORTS: pinned horizontal track of photo cards that tilt in 3D --}}
    <section class="sports" id="deportes">
      <div class="wrap sports-head">
        <p class="tag">{{ __('landing.sports.tag') }}</p>
        <h2>{{ __('landing.sports.title') }}</h2>
        <p class="sub">{{ __('landing.sports.sub') }}</p>
      </div>

      @if ($featured->isNotEmpty())
        <div class="track-wrap" id="track-wrap">
          <div class="track" id="track">
            @foreach ($featured as $sport)
              @php $slug = Str::slug($sport->name); @endphp
              <article class="card">
                <img src="/img/{{ $photos[$slug] }}.webp" alt="" width="1600" height="900" loading="lazy">
                <div class="card-txt">
                  <span class="card-n">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                  <h3>{{ $sportName($sport) }}</h3>
                  <p>{{ $sportDesc($sport) }}</p>
                </div>
              </article>
            @endforeach
          </div>
        </div>
      @endif

      <div class="wrap others">
        @if ($others->isNotEmpty())
          <p class="others-lbl">{{ __('landing.sports.also') }}</p>
          <ul class="chips">
            @foreach ($others as $sport)
              <li title="{{ $sportDesc($sport) }}">{{ $sportName($sport) }}</li>
            @endforeach
          </ul>
        @endif
        @if ($showMore)
          <div class="more">
            <b>{{ __('landing.sports.more_title') }}</b>
            <span>{{ __('landing.sports.more_text') }}</span>
          </div>
        @endif
      </div>
    </section>

    {{-- JOIN --}}
    <section class="join" id="asociate">
      <div class="join-bg" aria-hidden="true"><img src="/img/vestuario.webp" alt="" width="1600" height="900" loading="lazy"></div>
      <div class="wrap join-grid">
        <div>
          <p class="tag">{{ __('landing.join.tag') }}</p>
          <h2>{{ __('landing.join.title') }}</h2>
          <ol class="steps">
            <li><b>{{ __('landing.join.step1_t') }}</b><span>{{ __('landing.join.step1_d') }}</span></li>
            <li><b>{{ __('landing.join.step2_t') }}</b><span>{{ __('landing.join.step2_d') }}</span></li>
            <li><b>{{ __('landing.join.step3_t') }}</b><span>{{ __('landing.join.step3_d') }}</span></li>
          </ol>
        </div>

        <form class="form" id="join-form" action="{{ route('landing.contact') }}" method="post" novalidate
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
    <section class="portal" id="cuota">
      <div class="wrap portal-grid">
        <div>
          <p class="tag">{{ __('landing.portal.tag') }}</p>
          <h2>{{ __('landing.portal.title') }}</h2>
          <p class="sub">{{ __('landing.portal.text') }}</p>
          <a href="{{ route('portal.estado') }}" class="btn btn-red">{{ __('landing.portal.cta') }} <span aria-hidden="true">→</span></a>
        </div>
        <div class="card-dni" aria-hidden="true" id="card-dni">
          <img src="/icons/escudo.svg" alt="" width="54" height="65">
          <small>{{ config('club.name') }}</small>
          <b>DNI 30.•••.•••</b>
          <span class="ok">✓ {{ __('landing.portal.ok') }}</span>
        </div>
      </div>
    </section>

    {{-- HISTORY --}}
    <section class="history" id="historia">
      <div class="wrap">
        <p class="tag">{{ __('landing.history.tag') }}</p>
        <h2>{{ __('landing.history.title') }}</h2>
        <ol class="timeline" id="timeline">
          @foreach (__('landing.history.items') as $item)
            <li><b>{{ $item['year'] }}</b><span>{{ $item['text'] }}</span></li>
          @endforeach
        </ol>
      </div>
    </section>
  </main>

  <footer>
    <div class="wrap foot-grid">
      <div>
        <p class="foot-name">{{ config('club.name') }}</p>
        <p>{{ __('landing.footer.about', ['year' => $founded]) }}</p>
        <p class="foot-demo">{{ __('landing.demo_notice') }}</p>
      </div>
      <div>
        <h4>{{ __('landing.footer.sports') }}</h4>
        <ul>@foreach ($sports->take(6) as $sport)<li>{{ $sportName($sport) }}</li>@endforeach</ul>
      </div>
      <div>
        <h4>{{ __('landing.footer.contact') }}</h4>
        <ul>
          <li>{{ config('club.address') }}</li>
          <li><a href="tel:+{{ $whatsapp }}">{{ config('club.phone') }}</a></li>
          @if ($whatsapp)
            <li><a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener">{{ __('landing.footer.whatsapp') }}</a></li>
          @endif
          <li><a href="{{ route('portal.estado') }}">{{ __('landing.nav.fees') }}</a></li>
        </ul>
      </div>
    </div>
    <div class="wrap foot-bottom">
      <span>© {{ date('Y') }} {{ config('club.name') }}. {{ __('landing.footer.rights') }}</span>
      <span>{{ __('landing.footer.built') }}</span>
    </div>
  </footer>

  <script src="/js/vendor/gsap.min.js" defer></script>
  <script src="/js/vendor/ScrollTrigger.min.js" defer></script>
  <script src="/js/landing.js?v=2" defer></script>
  <script type="module" src="/js/crest3d.js?v=4"></script>
</body>
</html>
