/* Club Rojo Deportivo — 3D crest in the hero (three.js).
 * The crest is built from the same outline as /icons/escudo.svg, extruded so it has
 * real depth and light. Scrolling the hero turns it and pushes it back; red embers
 * float like a night match under floodlights. */
import * as THREE from 'three';

const canvas = document.getElementById('crest3d');
const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const small = window.matchMedia('(max-width: 1080px)').matches;

if (canvas) {
  let renderer;
  try {
    renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true, powerPreference: 'high-performance' });
  } catch (e) {
    renderer = null; // No WebGL: the hero keeps its red gradient background.
  }
  if (renderer) start(renderer);
}

function shieldShape(inset = 0) {
  // Same outline as the SVG (viewBox 200x240), centred on (0, 0) and flipped to y-up.
  const s = new THREE.Shape();
  const P = (x, y) => [ (x - 100) / 100, (120 - y) / 100 ];
  const k = inset / 100;
  const [ax, ay] = P(100, 8 + inset), [bx, by] = P(188 - inset, 34 + inset * .6), [cx, cy] = P(188 - inset, 120);
  s.moveTo(ax, ay);
  s.lineTo(bx, by);
  s.lineTo(cx, cy);
  const [c1x, c1y] = P(188 - inset, 178 - inset * .6), [c2x, c2y] = P(146 - inset * .4, 214 - inset), [dx, dy] = P(100, 232 - inset * 1.2);
  s.bezierCurveTo(c1x, c1y, c2x, c2y, dx, dy);
  const [e1x, e1y] = P(54 + inset * .4, 214 - inset), [e2x, e2y] = P(12 + inset, 178 - inset * .6), [fx, fy] = P(12 + inset, 120);
  s.bezierCurveTo(e1x, e1y, e2x, e2y, fx, fy);
  const [gx, gy] = P(12 + inset, 34 + inset * .6);
  s.lineTo(gx, gy);
  s.closePath();
  void k;
  return s;
}

function star(r1, r2) {
  const s = new THREE.Shape();
  for (let i = 0; i < 10; i++) {
    const r = i % 2 ? r2 : r1;
    const a = Math.PI / 2 + i * Math.PI / 5;
    const x = Math.cos(a) * r, y = Math.sin(a) * r;
    i ? s.lineTo(x, y) : s.moveTo(x, y);
  }
  s.closePath();
  return s;
}

function textTexture() {
  const c = document.createElement('canvas');
  c.width = 512; c.height = 256;
  const g = c.getContext('2d');
  g.fillStyle = '#fff';
  g.textAlign = 'center';
  g.font = '900 150px "Arial Black", Arial, sans-serif';
  g.fillText('CRD', 256, 150);
  g.font = '700 46px Arial, sans-serif';
  g.letterSpacing = '14px';
  g.fillText('1948', 256, 222);
  const t = new THREE.CanvasTexture(c);
  t.colorSpace = THREE.SRGBColorSpace;
  t.anisotropy = 4;
  return t;
}

function start(renderer) {
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, small ? 1.5 : 2));
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.outputColorSpace = THREE.SRGBColorSpace;

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(35, 1, 0.1, 100);
  camera.position.set(0, 0, 7.5);

  // Lights: cold floodlight from the top-left, warm red rim from behind-right.
  scene.add(new THREE.AmbientLight(0xffffff, 0.35));
  const key = new THREE.DirectionalLight(0xfff4e8, 2.6); key.position.set(-3, 4, 5); scene.add(key);
  const rim = new THREE.PointLight(0xff2030, 60, 20); rim.position.set(3, -1, -2.5); scene.add(rim);
  const fill = new THREE.PointLight(0x88aaff, 12, 15); fill.position.set(-4, -3, 3); scene.add(fill);

  const crest = new THREE.Group();
  scene.add(crest);

  const bevel = { bevelEnabled: true, bevelThickness: 0.05, bevelSize: 0.04, bevelSegments: 6, curveSegments: 40 };
  const red = new THREE.MeshPhysicalMaterial({ color: 0xc8141d, roughness: 0.32, metalness: 0.1, clearcoat: 1, clearcoatRoughness: 0.15 });
  const white = new THREE.MeshPhysicalMaterial({ color: 0xffffff, roughness: 0.35, metalness: 0.05, clearcoat: 0.6 });

  // White outer rim, red body inside
  const outer = new THREE.Mesh(new THREE.ExtrudeGeometry(shieldShape(0), { depth: 0.22, ...bevel }), white);
  outer.position.z = -0.11;
  crest.add(outer);
  const body = new THREE.Mesh(new THREE.ExtrudeGeometry(shieldShape(7), { depth: 0.26, ...bevel }), red);
  body.position.z = -0.1;
  crest.add(body);

  // Diagonal white sash, clipped to the body by building it as a thin band and scaling inside
  const sashShape = new THREE.Shape();
  sashShape.moveTo(-1.2, -0.17); sashShape.lineTo(1.2, -0.17); sashShape.lineTo(1.2, 0.17); sashShape.lineTo(-1.2, 0.17); sashShape.closePath();
  const sash = new THREE.Mesh(new THREE.ExtrudeGeometry(sashShape, { depth: 0.06, bevelEnabled: false }), white);
  sash.rotation.z = THREE.MathUtils.degToRad(18);
  sash.position.set(0, 0.06, 0.2);
  sash.scale.set(0.74, 1, 1);
  crest.add(sash);

  const starMesh = new THREE.Mesh(new THREE.ExtrudeGeometry(star(0.2, 0.085), { depth: 0.07, ...bevel, bevelSize: 0.012, bevelThickness: 0.012 }), white);
  starMesh.position.set(0, 0.62, 0.2);
  crest.add(starMesh);

  const label = new THREE.Mesh(new THREE.PlaneGeometry(1.25, 0.62), new THREE.MeshBasicMaterial({ map: textTexture(), transparent: true }));
  label.position.set(0, -0.62, 0.3);
  crest.add(label);

  crest.scale.setScalar(1.15);

  // Embers
  const N = small ? 260 : 600;
  const pos = new Float32Array(N * 3);
  const speed = new Float32Array(N);
  for (let i = 0; i < N; i++) {
    pos[i * 3] = (Math.random() - 0.5) * 14;
    pos[i * 3 + 1] = (Math.random() - 0.5) * 9;
    pos[i * 3 + 2] = (Math.random() - 0.5) * 8 - 1;
    speed[i] = 0.15 + Math.random() * 0.5;
  }
  const embersGeo = new THREE.BufferGeometry();
  embersGeo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
  const embers = new THREE.Points(embersGeo, new THREE.PointsMaterial({
    color: 0xff3a3a, size: 0.035, transparent: true, opacity: 0.85, blending: THREE.AdditiveBlending, depthWrite: false,
  }));
  scene.add(embers);

  // Layout: on desktop the crest sits on the right of the headline, on phones above it.
  function resize() {
    const w = canvas.clientWidth, h = canvas.clientHeight;
    renderer.setSize(w, h, false);
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    const mobile = w < 1080;
    crest.userData.baseX = mobile ? 0 : 2.15;
    crest.userData.baseY = mobile ? 1.62 : 0.05;
    crest.userData.baseS = mobile ? 0.6 : 1.15;
  }
  window.addEventListener('resize', resize);
  resize();

  // Pointer tilt
  const pointer = { x: 0, y: 0 };
  window.addEventListener('pointermove', (e) => {
    pointer.x = (e.clientX / window.innerWidth) * 2 - 1;
    pointer.y = (e.clientY / window.innerHeight) * 2 - 1;
  }, { passive: true });

  // Scroll progress through the hero (0 → 1)
  const hero = document.getElementById('hero');
  let progress = 0;
  function readScroll() {
    const r = hero.getBoundingClientRect();
    progress = Math.min(1, Math.max(0, -r.top / Math.max(1, r.height)));
  }
  window.addEventListener('scroll', readScroll, { passive: true });
  readScroll();

  let visible = true;
  new IntersectionObserver(([e]) => { visible = e.isIntersecting; }).observe(hero);

  const clock = new THREE.Clock();
  let intro = reduced ? 1 : 0;

  function frame() {
    requestAnimationFrame(frame);
    if (!visible) return;
    const dt = Math.min(clock.getDelta(), 0.05);
    const t = clock.elapsedTime;
    intro = Math.min(1, intro + dt * 0.7);
    const e = 1 - Math.pow(1 - intro, 3);

    const { baseX, baseY, baseS } = crest.userData;
    const spin = reduced ? 0 : Math.sin(t * 0.6) * 0.18;
    crest.rotation.y = (1 - e) * -Math.PI * 1.2 + spin + pointer.x * 0.35 + progress * Math.PI * 1.1;
    crest.rotation.x = pointer.y * 0.2 + progress * 0.5;
    crest.position.set(baseX + progress * -1.2, baseY + (reduced ? 0 : Math.sin(t * 1.1) * 0.06) + progress * 0.8, -progress * 6);
    crest.scale.setScalar(baseS * (0.6 + 0.4 * e));

    if (!reduced) {
      for (let i = 0; i < N; i++) {
        pos[i * 3 + 1] += speed[i] * dt * 0.5;
        pos[i * 3] += Math.sin(t + i) * dt * 0.05;
        if (pos[i * 3 + 1] > 4.5) pos[i * 3 + 1] = -4.5;
      }
      embersGeo.attributes.position.needsUpdate = true;
    }
    rim.intensity = 60 + Math.sin(t * 2.3) * 12;
    renderer.render(scene, camera);
  }
  frame();
}
