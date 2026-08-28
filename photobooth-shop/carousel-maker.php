<?php
$extra_css = ['carousel.css'];
require_once __DIR__ . '/includes/header.php';

$slug = $_GET['type'] ?? '';
$photoType = get_photo_type_by_slug($slug);
if (!$photoType) {
    set_flash('error', "Let's pick a session first! 💫");
    header('Location: ' . SITE_URL . '/index.php#sessions');
    exit;
}
$designs = get_carousel_designs();
$page_title = 'Design Your Frame · Kislap Booth ✨';

$prefillName = $_SESSION['customer_name'] ?? '';
$prefillEmail = $_SESSION['customer_email'] ?? '';
?>

<section class="maker-page">
    <div class="container maker-grid">

        <div class="maker-preview-wrap">
            <div class="section-head-cute" style="text-align:left; margin-bottom:1.6rem;">
                <span class="eyebrow-cute">Almost there!</span>
                <h2>Design your carousel ✨</h2>
                <p>Pick a cute frame and we'll compose your <?= (int)$photoType['shots_required'] ?>-shot strip.</p>
            </div>
            <div class="maker-canvas-frame">
                <canvas id="stripCanvas" width="420" height="600"></canvas>
            </div>
            <p class="maker-preview-note">This is exactly what you'll get printed &amp; downloaded 🎀</p>
        </div>

        <aside class="maker-sidebar">
            <div class="card-cute" style="padding:1.5rem;">
                <h3 style="font-size:1.05rem; margin-bottom:1rem;">Pick a Frame Design</h3>
                <div class="design-picker" id="designPicker">
                    <?php foreach ($designs as $i => $d):
                        $stickerIcons = ['sakura' => '🌸','bear' => '🐻','stars' => '⭐','sparkle' => '✨','none' => '🖼️'];
                        $icon = $stickerIcons[$d['sticker_set']] ?? '🖼️';
                    ?>
                    <div class="design-opt<?= $i === 0 ? ' active' : '' ?>"
                         data-id="<?= (int)$d['design_id'] ?>"
                         data-bg="<?= e($d['bg_color']) ?>"
                         data-accent="<?= e($d['accent_color']) ?>"
                         data-stickers="<?= e($d['sticker_set']) ?>"
                         data-fee="<?= e($d['extra_fee']) ?>">
                        <div class="do-swatch" style="background: <?= e($d['bg_color']) ?>;"><?= $icon ?></div>
                        <div class="do-name"><?= e($d['name']) ?></div>
                        <div class="do-fee"><?= $d['extra_fee'] > 0 ? '+' . CURRENCY_SYMBOL . number_format($d['extra_fee'], 0) : 'Free' ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <form class="form-cute" id="customerInfoForm">
                    <div class="field-group">
                        <label for="custName">Your Name</label>
                        <input type="text" id="custName" name="customer_name" placeholder="Juana Dela Cruz" value="<?= e($prefillName) ?>" required>
                    </div>
                    <div class="field-group">
                        <label for="custPhone">Phone Number</label>
                        <input type="tel" id="custPhone" name="customer_phone" placeholder="09XX XXX XXXX" required>
                    </div>
                    <div class="field-group">
                        <label for="custEmail">Email (optional)</label>
                        <input type="email" id="custEmail" name="customer_email" placeholder="you@email.com" value="<?= e($prefillEmail) ?>">
                    </div>
                </form>
            </div>

            <div class="card-cute price-summary">
                <div class="row"><span><?= e($photoType['name']) ?></span><span id="sumBase"><?= CURRENCY_SYMBOL . number_format($photoType['base_price'], 2) ?></span></div>
                <div class="row"><span>Frame Design</span><span id="sumFee">Free</span></div>
                <div class="row total"><span>Total</span><span id="sumTotal"><?= CURRENCY_SYMBOL . number_format($photoType['base_price'], 2) ?></span></div>
            </div>

            <button type="button" class="btn-pill btn-pill-sunny btn-pill-lg btn-pill-block" id="checkoutBtn" style="margin-top:1.2rem;">
                Proceed to Checkout 💳
            </button>
            <a href="<?= SITE_URL ?>/booth.php?type=<?= e($photoType['slug']) ?>" class="retake-link">↺ Retake Photos</a>
        </aside>

    </div>
</section>

<script>
window.MAKER_CONFIG = {
    typeSlug: <?= json_encode($photoType['slug']) ?>,
    basePrice: <?= (float)$photoType['base_price'] ?>,
    currency: <?= json_encode(CURRENCY_SYMBOL) ?>
};
</script>

<?php $extra_js = ['carousel-maker.js']; require_once __DIR__ . '/includes/footer.php'; ?>