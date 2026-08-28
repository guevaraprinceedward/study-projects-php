<?php
$extra_css = ['booth.css'];
require_once __DIR__ . '/includes/header.php';

$slug = $_GET['type'] ?? '';
$photoType = get_photo_type_by_slug($slug);
if (!$photoType) {
    set_flash('error', "Hmm, we couldn't find that session type. Pick one below! 💫");
    header('Location: ' . SITE_URL . '/index.php#sessions');
    exit;
}
$page_title = $photoType['name'] . ' · Kislap Booth ✨';
?>

<section class="booth-page">
    <div class="container">
        <div class="booth-grid">

            <div>
                <div class="camera-stage" id="cameraStage">
                    <video id="cameraVideo" autoplay playsinline muted></video>
                    <canvas id="captureCanvas"></canvas>

                    <div class="camera-placeholder" id="cameraPlaceholder">
                        <span class="ph-icon">📷</span>
                        <p><strong>Ready when you are!</strong><br>Tap "Start Camera" to turn on your webcam, or upload photos instead.</p>
                    </div>

                    <div class="face-guide" id="faceGuide" style="display:none;">
                        <div class="face-oval" id="faceOval"></div>
                    </div>

                    <div class="detect-badge" id="detectBadge" style="display:none;">
                        <span class="dot"></span><span id="detectBadgeText">Looking for a face…</span>
                    </div>

                    <div class="shot-counter-badge" id="shotCounterBadge" style="display:none;">
                        Shot <span id="shotCurrent">1</span> / <span id="shotTotal"><?= (int)$photoType['shots_required'] ?></span>
                    </div>

                    <div class="pose-hint" id="poseHint" style="display:none;">Center your face in the oval ✨</div>

                    <div class="countdown-wrap" id="countdownWrap">
                        <div class="countdown-ring-box">
                            <svg viewBox="0 0 160 160">
                                <circle class="countdown-ring-bg" cx="80" cy="80" r="70"></circle>
                                <circle class="countdown-ring-fg" id="countdownRing" cx="80" cy="80" r="70"></circle>
                            </svg>
                            <div class="countdown-number" id="countdownNumber">3</div>
                        </div>
                    </div>

                    <div class="flash-overlay" id="flashOverlay"></div>
                </div>

                <div class="camera-controls">
                    <button type="button" class="btn-pill btn-pill-filled" id="startCameraBtn">Start Camera 📸</button>
                    <button type="button" class="shutter-btn" id="shutterBtn" style="display:none;" title="Capture now" aria-label="Capture now">⚡</button>
                    <button type="button" class="btn-pill btn-pill-ghost" id="switchCameraBtn" style="display:none;">Flip Camera 🔄</button>
                    <label class="btn-pill btn-pill-ghost upload-alt-label">
                        📁 Upload Instead
                        <input type="file" id="uploadInput" accept="image/*" multiple hidden>
                    </label>
                </div>

                <div class="shots-strip" id="shotsStrip"></div>

                <div style="text-align:center; margin-top:2rem;">
                    <button type="button" class="btn-pill btn-pill-sunny btn-pill-lg" id="continueBtn" disabled>Continue to Frame Design 🎀</button>
                </div>
            </div>

            <aside class="booth-sidebar">
                <div class="card-cute booth-type-card reveal">
                    <div class="bt-icon"><?= e($photoType['icon_emoji']) ?></div>
                    <h3><?= e($photoType['name']) ?></h3>
                    <p><?= e($photoType['description']) ?></p>
                    <div class="progress-dots" id="progressDots">
                        <?php for ($i = 0; $i < $photoType['shots_required']; $i++): ?>
                        <div class="pdot"></div>
                        <?php endfor; ?>
                    </div>
                    <div class="price-row">
                        <span>Session Price</span>
                        <span class="amt"><?= CURRENCY_SYMBOL . number_format($photoType['base_price'], 0) ?></span>
                    </div>
                </div>

                <div class="card-cute tip-card reveal reveal-delay-1">
                    <h4>💡 Cute Photo Tips</h4>
                    <ul>
                        <li>Face a light source for the best glow</li>
                        <li>Hold your pose for a beat after "Smile!"</li>
                        <li>Not feeling a shot? Tap it to retake</li>
                        <li>No webcam? Upload your own photos instead</li>
                    </ul>
                </div>
            </aside>

        </div>
    </div>
</section>

<script>
window.BOOTH_CONFIG = {
    typeSlug: <?= json_encode($photoType['slug']) ?>,
    typeName: <?= json_encode($photoType['name']) ?>,
    shotsRequired: <?= (int)$photoType['shots_required'] ?>
};
</script>

<?php $extra_js = ['booth.js']; require_once __DIR__ . '/includes/footer.php'; ?>