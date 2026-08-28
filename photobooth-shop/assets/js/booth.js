/* =====================================================================
   KISLAP BOOTH ✨ — Camera Capture Logic
   ===================================================================== */
(function () {
    const cfg = window.BOOTH_CONFIG || { typeSlug: '', typeName: '', shotsRequired: 3 };
    const shotsRequired = cfg.shotsRequired || 3;

    const video = document.getElementById('cameraVideo');
    const canvas = document.getElementById('captureCanvas');
    const placeholder = document.getElementById('cameraPlaceholder');
    const startCameraBtn = document.getElementById('startCameraBtn');
    const shutterBtn = document.getElementById('shutterBtn');
    const switchCameraBtn = document.getElementById('switchCameraBtn');
    const uploadInput = document.getElementById('uploadInput');
    const continueBtn = document.getElementById('continueBtn');
    const shotsStrip = document.getElementById('shotsStrip');
    const flashOverlay = document.getElementById('flashOverlay');
    const faceGuide = document.getElementById('faceGuide');
    const faceOval = document.getElementById('faceOval');
    const detectBadge = document.getElementById('detectBadge');
    const detectBadgeText = document.getElementById('detectBadgeText');
    const shotCounterBadge = document.getElementById('shotCounterBadge');
    const shotCurrent = document.getElementById('shotCurrent');
    const poseHint = document.getElementById('poseHint');
    const countdownWrap = document.getElementById('countdownWrap');
    const countdownNumber = document.getElementById('countdownNumber');
    const countdownRing = document.getElementById('countdownRing');
    const progressDots = document.querySelectorAll('#progressDots .pdot');

    let stream = null;
    let facingMode = 'user';
    let capturedShots = new Array(shotsRequired).fill(null);
    let currentSlotIndex = 0;
    let countdownActive = false;
    let faceApiReady = false;
    let manualMode = false;
    let faceStreak = 0;
    let detectTimer = null;
    const RING_CIRC = 440;

    function allSlotsFilled() {
        return capturedShots.every(s => s !== null);
    }

    function nextEmptySlot() {
        return capturedShots.findIndex(s => s === null);
    }

    function renderSlots() {
        shotsStrip.innerHTML = '';
        capturedShots.forEach((shot, i) => {
            const slot = document.createElement('div');
            slot.className = 'shot-slot' + (shot ? ' filled' : '');
            if (shot) {
                slot.innerHTML = `<img src="${shot}" alt="Shot ${i + 1}"><button type="button" class="retake-btn" data-retake="${i}">RETAKE</button>`;
            } else {
                slot.innerHTML = `<span class="slot-num">${i + 1}</span><span>📷</span>`;
            }
            shotsStrip.appendChild(slot);
        });
        shotsStrip.querySelectorAll('[data-retake]').forEach(btn => {
            btn.addEventListener('click', () => retakeSlot(parseInt(btn.dataset.retake, 10)));
        });
        updateProgressDots();
        continueBtn.disabled = !allSlotsFilled();
    }

    function updateProgressDots() {
        progressDots.forEach((dot, i) => dot.classList.toggle('done', capturedShots[i] !== null));
    }

    function updateShotCounterBadge() {
        const idx = nextEmptySlot();
        shotCurrent.textContent = idx === -1 ? shotsRequired : idx + 1;
    }

    function retakeSlot(index) {
        capturedShots[index] = null;
        currentSlotIndex = index;
        renderSlots();
        updateShotCounterBadge();
        poseHint.textContent = `Let's redo shot ${index + 1}! Get in position ✨`;
        poseHint.style.display = 'block';
        continueBtn.disabled = true;
        if (!stream) {
            // camera was stopped or never started — nothing else to do, user can re-upload for this slot
        }
    }

    /* ---- Camera setup ---- */
    async function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showToast("Your browser doesn't support camera access. Please use Upload Instead. 📁", 'error');
            return;
        }
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode }, audio: false });
            video.srcObject = stream;
            placeholder.style.display = 'none';
            video.style.display = 'block';
            shutterBtn.style.display = 'flex';
            switchCameraBtn.style.display = 'inline-flex';
            startCameraBtn.style.display = 'none';
            shotCounterBadge.style.display = 'block';
            detectBadge.style.display = 'flex';
            faceGuide.style.display = 'flex';
            updateShotCounterBadge();
            initFaceDetection();
        } catch (err) {
            showToast("Couldn't access your camera — check permissions, or upload photos instead. 📁", 'error');
        }
    }

    async function flipCamera() {
        facingMode = facingMode === 'user' ? 'environment' : 'user';
        if (stream) stream.getTracks().forEach(t => t.stop());
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode }, audio: false });
            video.srcObject = stream;
        } catch (err) {
            showToast("Couldn't switch camera on this device.", 'error');
        }
    }

    /* ---- Face detection (face-api.js from CDN, with graceful manual fallback) ---- */
    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    async function initFaceDetection() {
        try {
            await Promise.race([
                (async () => {
                    if (!window.faceapi) {
                        await loadScript('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js');
                    }
                    await faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights');
                })(),
                new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 7000))
            ]);
            faceApiReady = true;
            manualMode = false;
            detectBadgeText.textContent = 'Looking for a face…';
            startDetectionLoop();
        } catch (err) {
            faceApiReady = false;
            manualMode = true;
            detectBadge.classList.add('ok');
            detectBadgeText.textContent = 'Manual mode ready';
            faceGuide.style.display = 'none';
            poseHint.style.display = 'block';
            poseHint.textContent = "Strike a pose, then tap the shutter ⚡ when ready!";
            shutterBtn.disabled = false;
        }
    }

    function startDetectionLoop() {
        if (detectTimer) clearInterval(detectTimer);
        detectTimer = setInterval(async () => {
            if (countdownActive || allSlotsFilled() || !window.faceapi) return;
            try {
                const result = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 }));
                if (result) {
                    faceStreak += 300;
                    faceOval.classList.add('detected');
                    detectBadge.classList.add('ok');
                    detectBadgeText.textContent = 'Cutie detected! 🥰';
                    poseHint.style.display = 'none';
                    shutterBtn.disabled = false;
                    if (faceStreak >= 900 && !countdownActive) {
                        startCountdown(3);
                    }
                } else {
                    faceStreak = 0;
                    faceOval.classList.remove('detected');
                    detectBadge.classList.remove('ok');
                    detectBadgeText.textContent = 'Looking for a face…';
                    poseHint.style.display = 'block';
                    poseHint.textContent = 'Center your face in the oval ✨';
                }
            } catch (e) { /* transient detection error, ignore */ }
        }, 300);
    }

    /* ---- Countdown + capture ---- */
    function startCountdown(seconds) {
        if (countdownActive || allSlotsFilled()) return;
        countdownActive = true;
        shutterBtn.disabled = true;
        countdownWrap.classList.add('active');
        poseHint.style.display = 'none';

        let remaining = seconds;
        countdownRing.style.transition = 'none';
        countdownRing.style.strokeDashoffset = '0';
        void countdownRing.offsetWidth;
        countdownRing.style.transition = 'stroke-dashoffset 1s linear';
        countdownNumber.textContent = remaining;
        popNumber();

        const interval = setInterval(() => {
            remaining--;
            const offset = RING_CIRC * (seconds - remaining) / seconds;
            countdownRing.style.strokeDashoffset = String(offset);
            if (remaining <= 0) {
                clearInterval(interval);
                countdownNumber.textContent = '📸';
                popNumber();
                setTimeout(() => capturePhoto(), 300);
                return;
            }
            countdownNumber.textContent = remaining;
            popNumber();
        }, 1000);
    }

    function popNumber() {
        countdownNumber.style.animation = 'none';
        void countdownNumber.offsetWidth;
        countdownNumber.style.animation = '';
    }

    function capturePhoto() {
        flashOverlay.classList.add('flashing');
        setTimeout(() => flashOverlay.classList.remove('flashing'), 380);

        const vw = video.videoWidth || 640;
        const vh = video.videoHeight || 480;
        canvas.width = vw;
        canvas.height = vh;
        const ctx = canvas.getContext('2d');
        // Mirror the capture to match the mirrored live preview the user saw
        ctx.translate(vw, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0, vw, vh);
        const dataUrl = canvas.toDataURL('image/jpeg', 0.92);

        capturedShots[currentSlotIndex] = dataUrl;
        renderSlots();

        const rect = document.getElementById('cameraStage').getBoundingClientRect();
        confettiBurst(rect.left + rect.width / 2, rect.top + rect.height / 2, 16);

        countdownWrap.classList.remove('active');
        countdownActive = false;
        faceStreak = 0;

        currentSlotIndex = nextEmptySlot();

        if (currentSlotIndex === -1) {
            poseHint.style.display = 'block';
            poseHint.textContent = 'All done! You look amazing ✨';
            shutterBtn.disabled = true;
            detectBadgeText.textContent = 'Session complete 🎉';
        } else {
            updateShotCounterBadge();
            shutterBtn.disabled = false;
            if (manualMode) {
                poseHint.style.display = 'block';
                poseHint.textContent = `Get ready for shot ${currentSlotIndex + 1}! Tap ⚡ when set.`;
            } else {
                poseHint.style.display = 'block';
                poseHint.textContent = `Get ready for shot ${currentSlotIndex + 1}! ✨`;
            }
        }
    }

    /* ---- Upload fallback ---- */
    function handleUpload(files) {
        const readers = [];
        for (let i = 0; i < files.length; i++) {
            const idx = nextEmptySlot();
            if (idx === -1) break;
            readers.push(new Promise(resolve => {
                const reader = new FileReader();
                reader.onload = () => { capturedShots[idx] = reader.result; resolve(); };
                reader.readAsDataURL(files[i]);
            }));
            capturedShots[idx] = 'pending'; // reserve slot synchronously to avoid race on multi-file select
        }
        Promise.all(readers).then(() => {
            currentSlotIndex = nextEmptySlot();
            renderSlots();
            if (allSlotsFilled()) {
                showToast('All shots are in! Ready to pick a frame ✨', 'success');
            }
        });
    }

    /* ---- Wire up events ---- */
    startCameraBtn.addEventListener('click', startCamera);
    shutterBtn.addEventListener('click', () => startCountdown(3));
    switchCameraBtn.addEventListener('click', flipCamera);
    uploadInput.addEventListener('change', (e) => {
        if (e.target.files && e.target.files.length) handleUpload(e.target.files);
        e.target.value = '';
    });

    continueBtn.addEventListener('click', () => {
        if (!allSlotsFilled()) return;
        try {
            sessionStorage.setItem('kislap_shots', JSON.stringify(capturedShots));
            sessionStorage.setItem('kislap_type', cfg.typeSlug);
        } catch (e) {
            showToast('Storage full — try clearing some browser data and retry.', 'error');
            return;
        }
        if (stream) stream.getTracks().forEach(t => t.stop());
        window.location.href = window.SITE_URL + '/carousel-maker.php?type=' + encodeURIComponent(cfg.typeSlug);
    });

    window.addEventListener('beforeunload', () => {
        if (stream) stream.getTracks().forEach(t => t.stop());
    });

    renderSlots();
})();