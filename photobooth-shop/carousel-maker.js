/* =====================================================================
   KISLAP BOOTH ✨ — Carousel / Frame Maker Logic
   ===================================================================== */
(function () {
    const cfg = window.MAKER_CONFIG || { typeSlug: '', basePrice: 0, currency: '₱' };

    const canvas = document.getElementById('stripCanvas');
    const ctx = canvas.getContext('2d');
    const designPicker = document.getElementById('designPicker');
    const sumFee = document.getElementById('sumFee');
    const sumTotal = document.getElementById('sumTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const customerForm = document.getElementById('customerInfoForm');

    let shots = [];
    let loadedImages = [];
    let currentDesign = null;

    /* ---- Load captured shots from sessionStorage ---- */
    try {
        const raw = sessionStorage.getItem('kislap_shots');
        const savedType = sessionStorage.getItem('kislap_type');
        if (!raw || savedType !== cfg.typeSlug) throw new Error('missing');
        shots = JSON.parse(raw).filter(Boolean);
        if (!shots.length) throw new Error('empty');
    } catch (e) {
        window.location.href = window.SITE_URL + '/booth.php?type=' + encodeURIComponent(cfg.typeSlug);
        return;
    }

    function loadImages(urls) {
        return Promise.all(urls.map(url => new Promise((resolve) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = () => resolve(null);
            img.src = url;
        })));
    }

    function getLayout(n) {
        if (n <= 3) return { cols: 1, rows: n };
        if (n === 4) return { cols: 2, rows: 2 };
        if (n <= 6) return { cols: 2, rows: Math.ceil(n / 2) };
        return { cols: 3, rows: Math.ceil(n / 3) };
    }

    function roundRectPath(c, x, y, w, h, r) {
        c.beginPath();
        c.moveTo(x + r, y);
        c.arcTo(x + w, y, x + w, y + h, r);
        c.arcTo(x + w, y + h, x, y + h, r);
        c.arcTo(x, y + h, x, y, r);
        c.arcTo(x, y, x + w, y, r);
        c.closePath();
    }

    function drawCover(c, img, x, y, w, h) {
        const scale = Math.max(w / img.width, h / img.height);
        const sw = w / scale, sh = h / scale;
        const sx = (img.width - sw) / 2, sy = (img.height - sh) / 2;
        c.drawImage(img, sx, sy, sw, sh, x, y, w, h);
    }

    const STICKER_SETS = {
        sakura: ['🌸', '🌷', '🌸'],
        bear: ['🐻', '🧸', '🐻'],
        stars: ['⭐', '✨', '⭐'],
        sparkle: ['✨', '💫', '✨'],
        none: []
    };

    function drawStrip(design) {
        const n = loadedImages.length;
        const { cols, rows } = getLayout(n);
        const cellSize = cols === 1 ? 320 : 172;
        const gap = 16;
        const pad = 28;
        const footerH = 84;

        const w = pad * 2 + cols * cellSize + (cols - 1) * gap;
        const h = pad * 2 + rows * cellSize + (rows - 1) * gap + footerH;
        canvas.width = w;
        canvas.height = h;

        // Background
        roundRectPath(ctx, 0, 0, w, h, 26);
        ctx.fillStyle = design.bg;
        ctx.fill();

        // Photos
        loadedImages.forEach((img, i) => {
            if (!img) return;
            const col = i % cols;
            const row = Math.floor(i / cols);
            const x = pad + col * (cellSize + gap);
            const y = pad + row * (cellSize + gap);

            ctx.save();
            ctx.shadowColor = 'rgba(43,33,64,0.18)';
            ctx.shadowBlur = 14;
            ctx.shadowOffsetY = 6;
            roundRectPath(ctx, x, y, cellSize, cellSize, 14);
            ctx.fillStyle = '#FFFFFF';
            ctx.fill();
            ctx.restore();

            ctx.save();
            roundRectPath(ctx, x + 6, y + 6, cellSize - 12, cellSize - 12, 10);
            ctx.clip();
            drawCover(ctx, img, x + 6, y + 6, cellSize - 12, cellSize - 12);
            ctx.restore();
        });

        // Stickers scattered around border
        const stickers = STICKER_SETS[design.stickers] || [];
        if (stickers.length) {
            ctx.font = '26px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            const positions = [
                [pad * 0.55, pad * 0.55], [w - pad * 0.55, pad * 0.55],
                [pad * 0.5, h - footerH - pad * 0.4], [w - pad * 0.5, h - footerH - pad * 0.4]
            ];
            positions.forEach((p, i) => {
                ctx.fillText(stickers[i % stickers.length], p[0], p[1]);
            });
        }

        // Footer branding
        ctx.textAlign = 'center';
        ctx.fillStyle = design.accent;
        ctx.font = "600 22px 'Fredoka', sans-serif";
        ctx.fillText('Kislap Booth ✨', w / 2, h - footerH / 2 - 12);
        ctx.font = "500 13px 'Quicksand', sans-serif";
        ctx.fillStyle = 'rgba(43,33,64,0.55)';
        const dateStr = new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
        ctx.fillText(dateStr, w / 2, h - footerH / 2 + 16);
    }

    function selectDesign(optEl) {
        designPicker.querySelectorAll('.design-opt').forEach(o => o.classList.remove('active'));
        optEl.classList.add('active');
        currentDesign = {
            id: parseInt(optEl.dataset.id, 10),
            bg: optEl.dataset.bg,
            accent: optEl.dataset.accent,
            stickers: optEl.dataset.stickers,
            fee: parseFloat(optEl.dataset.fee)
        };
        drawStrip(currentDesign);
        updatePricing();
    }

    function updatePricing() {
        const fee = currentDesign ? currentDesign.fee : 0;
        const total = cfg.basePrice + fee;
        sumFee.textContent = fee > 0 ? cfg.currency + fee.toFixed(2) : 'Free';
        sumTotal.textContent = cfg.currency + total.toFixed(2);
    }

    designPicker.querySelectorAll('.design-opt').forEach(opt => {
        opt.addEventListener('click', () => selectDesign(opt));
    });

    checkoutBtn.addEventListener('click', async () => {
        if (!customerForm.reportValidity()) return;
        checkoutBtn.disabled = true;
        checkoutBtn.textContent = 'Creating your order… ✨';

        const finalImage = canvas.toDataURL('image/png');
        const formData = new FormData(customerForm);
        formData.append('csrf_token', window.CSRF_TOKEN);
        formData.append('type_slug', cfg.typeSlug);
        formData.append('design_id', currentDesign ? currentDesign.id : '');
        formData.append('shots', JSON.stringify(shots));
        formData.append('final_image', finalImage);

        try {
            const res = await fetch(window.SITE_URL + '/api/create_order.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                sessionStorage.removeItem('kislap_shots');
                sessionStorage.removeItem('kislap_type');
                window.location.href = window.SITE_URL + '/checkout.php?order=' + encodeURIComponent(data.order_number);
            } else {
                showToast(data.message || 'Something went wrong. Please try again.', 'error');
                checkoutBtn.disabled = false;
                checkoutBtn.textContent = 'Proceed to Checkout 💳';
            }
        } catch (err) {
            showToast('Network error — please try again.', 'error');
            checkoutBtn.disabled = false;
            checkoutBtn.textContent = 'Proceed to Checkout 💳';
        }
    });

    /* ---- Init ---- */
    loadImages(shots).then(imgs => {
        loadedImages = imgs;
        const firstOpt = designPicker.querySelector('.design-opt');
        if (firstOpt) selectDesign(firstOpt);
    });
})();