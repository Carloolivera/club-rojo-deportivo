/* Club Rojo Deportivo — landing interactions (menu, hidden admin, form, scroll motion). */
(function () {
  'use strict';

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var nav = document.getElementById('nav');
  var burger = document.getElementById('burger');

  /* Mobile menu */
  burger.addEventListener('click', function () {
    var open = nav.classList.toggle('open');
    burger.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  document.querySelectorAll('#links a').forEach(function (a) {
    a.addEventListener('click', function () { nav.classList.remove('open'); burger.setAttribute('aria-expanded', 'false'); });
  });

  /* Solid nav after the first screen */
  function onScroll() { nav.classList.toggle('solid', window.scrollY > 40); }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* Hidden admin entrance: five taps on the crest within two seconds. Single taps go to the top. */
  var crest = document.getElementById('crest');
  var taps = [];
  crest.addEventListener('click', function () {
    var now = Date.now();
    taps = taps.filter(function (t) { return now - t < 2000; });
    taps.push(now);
    if (taps.length >= 5) { window.location.href = crest.dataset.admin; return; }
    if (taps.length === 1) { window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' }); }
  });

  /* Join form: JSON to /contacto, result shown in place */
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
    } catch (e) { show(form.dataset.network, false); }
    button.disabled = false;
    button.textContent = label;
  });

  /* Scroll motion (GSAP). Without GSAP or with reduced motion, the page stays static and complete. */
  window.addEventListener('load', function () {
    if (reduced || !window.gsap || !window.ScrollTrigger) return;
    var gsap = window.gsap;
    gsap.registerPlugin(window.ScrollTrigger);
    document.documentElement.classList.add('anim');

    // Headline lines rise in
    gsap.from('.hero-title .line > span', { yPercent: 110, duration: 1.1, stagger: .12, ease: 'power4.out' });
    gsap.from('.kicker, .lead, .ctas', { opacity: 0, y: 24, duration: .9, delay: .45, stagger: .1, ease: 'power3.out' });

    // Section headings and blocks reveal
    gsap.utils.toArray('.tag, h2, .sub, .steps li, .form, .others, .portal .btn, .timeline li, .stat').forEach(function (el) {
      el.setAttribute('data-reveal', '');
      gsap.to(el, { opacity: 1, y: 0, duration: .9, ease: 'power3.out',
        scrollTrigger: { trigger: el, start: 'top 88%' } });
    });

    // Parallax photos
    [['.stadium-img', '.stadium'], ['.join-bg', '.join']].forEach(function (p) {
      gsap.fromTo(p[0], { yPercent: -8 }, { yPercent: 8, ease: 'none',
        scrollTrigger: { trigger: p[1], start: 'top bottom', end: 'bottom top', scrub: true } });
    });

    // Counters
    document.querySelectorAll('[data-count]').forEach(function (el) {
      var target = parseInt(el.dataset.count, 10) || 0;
      var obj = { v: 0 };
      gsap.to(obj, { v: target, duration: 1.6, ease: 'power2.out',
        scrollTrigger: { trigger: el, start: 'top 85%' },
        onUpdate: function () { el.textContent = Math.round(obj.v).toLocaleString('es-AR'); } });
    });

    // Sports: pin the section and slide the cards sideways while they tilt in 3D
    var track = document.getElementById('track');
    if (track) {
      var cards = gsap.utils.toArray('.card');
      var distance = function () { return Math.max(0, track.scrollWidth - window.innerWidth); };
      if (distance() > 0) {
        var tl = gsap.timeline({ scrollTrigger: { trigger: '#track-wrap', start: 'center center',
          end: function () { return '+=' + distance(); }, pin: '.sports', scrub: 1, invalidateOnRefresh: true } });
        tl.to(track, { x: function () { return -distance(); }, ease: 'none' }, 0);
        cards.forEach(function (card, i) {
          tl.fromTo(card, { rotateY: -22, z: -120 }, { rotateY: 22, z: -120, ease: 'none' }, 0);
          tl.fromTo(card.querySelector('img'), { xPercent: -6 }, { xPercent: 6, ease: 'none' }, 0);
        });
      }
    }

    // ID card turns as you scroll past
    gsap.fromTo('#card-dni', { rotateY: -32, rotateX: 14 }, { rotateY: 10, rotateX: -4, ease: 'none',
      scrollTrigger: { trigger: '.portal', start: 'top bottom', end: 'bottom top', scrub: true } });

    // Timeline line draws
    var tlEl = document.getElementById('timeline');
    gsap.fromTo(tlEl, { '--p': 0 }, { '--p': 1, ease: 'none',
      scrollTrigger: { trigger: tlEl, start: 'top 80%', end: 'bottom 60%', scrub: true } });
  });
})();
