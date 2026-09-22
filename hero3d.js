/**
 * hero3d.js — cinematic 3D hero for AyosCoffeeNegosyo
 * -------------------------------------------------------------------------
 * Loaded only when index.php's inline feature-check confirms WebGL works
 * and the visitor hasn't asked for reduced motion (see the <script> right
 * before <nav> in index.php). Everything here is procedural — no external
 * textures/models — so there's nothing extra to host or that can 404.
 *
 * Flow:
 *   1. Renders fullscreen inside #introOverlay: a lathe-geometry mug,
 *      procedurally-textured coffee beans orbiting it in true 3D (varying
 *      radius/height/speed, some passing in front of / behind the cup —
 *      that's just correct depth-testing from real 3D positions, not a
 *      trick), soft steam sprites, and gentle mouse-parallax camera drift.
 *   2. After ~3.4s (or an immediate tap on "Pasukin ➜"), the same live
 *      <canvas> — not a new one — animates via a computed transform from
 *      fullscreen into the hero's art slot, then gets re-parented into the
 *      page's normal flow there. The WebGL context is never recreated, so
 *      there's no flash/reload, just one continuous render.
 *   3. If anything here throws (e.g. the jsDelivr import fails on a flaky
 *      connection), it falls back to the static SVG cup exactly like the
 *      no-WebGL path — see the catch block at the bottom.
 */
import * as THREE from "https://cdn.jsdelivr.net/npm/three@0.184.0/build/three.module.js";

(function () {
  try {
    const overlay      = document.getElementById('introOverlay');
    const canvasWrap    = document.getElementById('introCanvasWrap');
    const canvas         = document.getElementById('heroCanvas');
    const skipBtn         = document.getElementById('introSkip');
    const loadingFill      = document.getElementById('introLoadingFill');
    const heroArtSlot        = document.getElementById('heroArt');
    const fallback             = heroArtSlot ? heroArtSlot.querySelector('.hero-art-fallback') : null;
    if (!overlay || !canvas || !heroArtSlot) throw new Error('hero3d: required DOM nodes missing');

    const isSmall = window.innerWidth < 640;
    const beanCount = isSmall ? 7 : 13;
    const espressoHex = 0x1a130d;

    // ---------------------------------------------------------------- core
    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));

    const scene = new THREE.Scene();
    scene.fog = new THREE.Fog(espressoHex, 6, 15);

    const camera = new THREE.PerspectiveCamera(38, 1, 0.1, 100);
    camera.position.set(0, 1.1, 7.2);
    camera.lookAt(0, 0.3, 0);

    scene.add(new THREE.AmbientLight(0xffe6c2, 0.55));
    const keyLight = new THREE.DirectionalLight(0xffcf8a, 1.5);
    keyLight.position.set(3, 5, 4);
    scene.add(keyLight);
    const rimLight = new THREE.DirectionalLight(0xe0a857, 1.1);
    rimLight.position.set(-4, 2, -3);
    scene.add(rimLight);
    const fillLight = new THREE.PointLight(0x8a4226, 0.7, 20);
    fillLight.position.set(-2, -1, 3);
    scene.add(fillLight);

    // ---------------------------------------------------------------- mug
    const cupGroup = new THREE.Group();
    const profile = [
      [0.00, -0.62], [0.55, -0.62], [0.58, -0.55], [0.60, 0.30],
      [0.66, 0.55], [0.70, 0.60], [0.66, 0.63], [0.60, 0.60],
      [0.56, 0.50], [0.52, 0.30], [0.50, -0.50], [0.00, -0.50],
    ].map(([x, y]) => new THREE.Vector2(x, y));
    const cupMat = new THREE.MeshPhysicalMaterial({
      color: 0xc7893c, roughness: 0.35, metalness: 0.05, clearcoat: 0.6, clearcoatRoughness: 0.25,
    });
    const cupMesh = new THREE.Mesh(new THREE.LatheGeometry(profile, 48), cupMat);
    cupGroup.add(cupMesh);

    function canvasTexture(draw, size) {
      const c = document.createElement('canvas');
      c.width = c.height = size;
      draw(c.getContext('2d'), size);
      return new THREE.CanvasTexture(c);
    }
    const cremaTex = canvasTexture((ctx, s) => {
      const g = ctx.createRadialGradient(s / 2, s / 2, s * 0.08, s / 2, s / 2, s / 2);
      g.addColorStop(0, '#3b2416'); g.addColorStop(0.72, '#2a180d'); g.addColorStop(1, '#c9a06a');
      ctx.fillStyle = g; ctx.fillRect(0, 0, s, s);
    }, 128);
    const crema = new THREE.Mesh(
      new THREE.CircleGeometry(0.58, 40),
      new THREE.MeshStandardMaterial({ map: cremaTex, roughness: 0.2 })
    );
    crema.rotation.x = -Math.PI / 2;
    crema.position.y = 0.585;
    cupGroup.add(crema);

    // Torus lies flat in the XY plane by default (hole facing the camera's Z
    // axis) — exactly what we want for a "D"-shaped handle silhouette seen
    // face-on, so the only adjustment needed is nudging it to the cup's side
    // and rotating the open ends of the arc to tuck into the cup body.
    const handle = new THREE.Mesh(new THREE.TorusGeometry(0.3, 0.075, 16, 32, Math.PI * 1.5), cupMat);
    handle.position.set(0.82, 0.02, 0);
    handle.rotation.z = -Math.PI * 0.24;
    cupGroup.add(handle);

    const saucer = new THREE.Mesh(
      new THREE.CylinderGeometry(1.05, 1.05, 0.08, 48),
      new THREE.MeshPhysicalMaterial({ color: 0xe0a857, roughness: 0.4, clearcoat: 0.4 })
    );
    saucer.position.y = -0.66;
    cupGroup.add(saucer);

    cupGroup.position.y = -0.2;
    scene.add(cupGroup);

    // --------------------------------------------------------------- steam
    const steamTex = canvasTexture((ctx, s) => {
      const g = ctx.createRadialGradient(s / 2, s / 2, 0, s / 2, s / 2, s / 2);
      g.addColorStop(0, 'rgba(240,225,200,0.55)');
      g.addColorStop(1, 'rgba(240,225,200,0)');
      ctx.fillStyle = g; ctx.fillRect(0, 0, s, s);
    }, 64);
    const steamSprites = Array.from({ length: 5 }, () => {
      const sprite = new THREE.Sprite(new THREE.SpriteMaterial({
        map: steamTex, transparent: true, depthWrite: false, opacity: 0,
      }));
      sprite.scale.set(0.5, 0.9, 1);
      sprite.position.set((Math.random() - 0.5) * 0.3, 0.7, (Math.random() - 0.5) * 0.3);
      sprite.userData = { seed: Math.random() * 10, speed: 0.25 + Math.random() * 0.15 };
      scene.add(sprite);
      return sprite;
    });

    // --------------------------------------------------------------- beans
    const beanTex = canvasTexture((ctx, s) => {
      const g = ctx.createRadialGradient(s * 0.37, s * 0.37, s * 0.06, s / 2, s / 2, s * 0.53);
      g.addColorStop(0, '#5b3a24'); g.addColorStop(1, '#2c1a10');
      ctx.fillStyle = g; ctx.fillRect(0, 0, s, s);
      ctx.strokeStyle = 'rgba(0,0,0,0.55)'; ctx.lineWidth = s * 0.045;
      ctx.beginPath(); ctx.moveTo(s / 2, s * 0.09);
      ctx.quadraticCurveTo(s * 0.37, s / 2, s / 2, s * 0.91);
      ctx.stroke();
    }, 64);
    const beanGeo = new THREE.SphereGeometry(1, 12, 10);
    const beans = Array.from({ length: beanCount }, () => {
      const mesh = new THREE.Mesh(beanGeo, new THREE.MeshStandardMaterial({ map: beanTex, roughness: 0.75 }));
      const s = 0.08 + Math.random() * 0.05;
      mesh.scale.set(s, s * 0.7, s * 0.55);
      mesh.userData = {
        radius: 1.6 + Math.random() * 1.6,
        speed: (0.15 + Math.random() * 0.2) * (Math.random() < 0.5 ? 1 : -1),
        angle: Math.random() * Math.PI * 2,
        heightAmp: 0.3 + Math.random() * 0.5,
        heightSpeed: 0.4 + Math.random() * 0.4,
        heightPhase: Math.random() * Math.PI * 2,
        tiltSpeed: (Math.random() - 0.5) * 1.5,
      };
      scene.add(mesh);
      return mesh;
    });

    // -------------------------------------------------------------- resize
    function resizeTo(w, h) {
      if (w <= 0 || h <= 0) return;
      renderer.setSize(w, h, false);
      camera.aspect = w / h;
      camera.updateProjectionMatrix();
    }
    resizeTo(window.innerWidth, window.innerHeight);

    // ------------------------------------------------------- mouse parallax
    let mouseX = 0, mouseY = 0;
    window.addEventListener('pointermove', (e) => {
      mouseX = e.clientX / window.innerWidth - 0.5;
      mouseY = e.clientY / window.innerHeight - 0.5;
    }, { passive: true });

    // ---------------------------------------------------------------- loop
    // THREE.Clock is deprecated as of r184+; Timer is the replacement API.
    const timer = new THREE.Timer();
    timer.connect(document);
    let docked = false;

    function animate(timestamp) {
      requestAnimationFrame(animate);
      timer.update(timestamp);
      const t = timer.getElapsed();
      const dt = Math.min(timer.getDelta(), 0.1);

      cupGroup.rotation.y += dt * 0.35;

      beans.forEach((bean) => {
        const u = bean.userData;
        u.angle += dt * u.speed;
        bean.position.set(
          Math.cos(u.angle) * u.radius,
          Math.sin(t * u.heightSpeed + u.heightPhase) * u.heightAmp + 0.1,
          Math.sin(u.angle) * u.radius
        );
        bean.rotation.x += dt * u.tiltSpeed;
        bean.rotation.y += dt * u.tiltSpeed * 0.6;
      });

      steamSprites.forEach((sprite) => {
        const u = sprite.userData;
        const life = (t * u.speed + u.seed) % 2.4;
        sprite.position.y = 0.65 + life * 0.6;
        sprite.position.x += Math.sin(t * 1.3 + u.seed) * 0.001;
        sprite.material.opacity = Math.max(0, 0.5 - Math.abs(life - 1.2) * 0.4) * (docked ? 0.5 : 1);
      });

      if (!docked) {
        camera.position.x += (mouseX * 0.6 - camera.position.x) * 0.03;
        camera.position.y += (1.1 - mouseY * 0.3 - camera.position.y) * 0.03;
        camera.lookAt(0, 0.3, 0);
      }

      renderer.render(scene, camera);
    }
    animate();

    // -------------------------------------------------- intro progress bar
    let progress = 0;
    const progressTimer = setInterval(() => {
      progress = Math.min(100, progress + 4);
      if (loadingFill) loadingFill.style.width = progress + '%';
      if (progress >= 100) clearInterval(progressTimer);
    }, 60);

    // ------------------------------------------------- dock into the hero
    function dockIntoHero() {
      if (docked) return;
      docked = true;
      clearInterval(progressTimer);

      const targetRect = heroArtSlot.getBoundingClientRect();
      const startRect = canvasWrap.getBoundingClientRect();
      const scale = Math.min(targetRect.width / startRect.width, targetRect.height / startRect.height);
      const dx = (targetRect.left + targetRect.width / 2) - (startRect.left + startRect.width / 2);
      const dy = (targetRect.top + targetRect.height / 2) - (startRect.top + startRect.height / 2);

      overlay.classList.add('intro-fading');
      canvasWrap.style.transition = 'transform 1.1s cubic-bezier(.22,.8,.28,1)';
      canvasWrap.style.transform = `translate(${dx}px, ${dy}px) scale(${scale})`;

      setTimeout(() => {
        heroArtSlot.appendChild(canvas); // same live context, just re-parented
        canvas.style.position = 'static';
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        overlay.hidden = true;
        if (fallback) fallback.remove();
        resizeTo(heroArtSlot.clientWidth, heroArtSlot.clientHeight);
        document.body.classList.add('intro-done');
        window.addEventListener('resize', () => resizeTo(heroArtSlot.clientWidth, heroArtSlot.clientHeight));
      }, 1150);
    }

    if (skipBtn) skipBtn.addEventListener('click', dockIntoHero);
    setTimeout(dockIntoHero, 3400);

  } catch (err) {
    // Any failure here (bad import, unexpected null, etc.) falls back to
    // exactly the no-WebGL experience: hide the overlay, reveal the static
    // SVG cup, and let the page work normally.
    console.warn('hero3d.js fell back to static hero:', err);
    const ov = document.getElementById('introOverlay');
    if (ov) ov.hidden = true;
    document.body.classList.add('intro-done');
  }
})();
