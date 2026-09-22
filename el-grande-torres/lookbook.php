<?php
require_once __DIR__ . '/config/app.php';

$page_title = "Lookbook — El Grande De La Torres";
$meta_description = "The Autumn 2026 Maison Collection lookbook — editorial imagery from El Grande De La Torres.";
require_once __DIR__ . '/includes/header.php';

$looks = [
    ['img' => 'https://images.unsplash.com/photo-1490578474895-699cd4e2cf59?q=80&w=1200&auto=format&fit=crop', 'title' => 'Look 01 — The Atelier Bomber', 'note' => 'Atelier Bomber Jacket layered over the Ivory Oxford Shirt.'],
    ['img' => 'https://images.unsplash.com/photo-1490114538077-0a7f8cb49891?q=80&w=1200&auto=format&fit=crop', 'title' => 'Look 02 — Quiet Tailoring', 'note' => 'Tailored House Trousers with the Signature Crest Tee.'],
    ['img' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?q=80&w=1200&auto=format&fit=crop', 'title' => 'Look 03 — Evening Column', 'note' => 'Torres Column Dress, styled with the House Crest Pendant.'],
    ['img' => 'https://images.unsplash.com/photo-1441984904996-e0b6ba687e04?q=80&w=1200&auto=format&fit=crop', 'title' => 'Look 04 — Overcoat Season', 'note' => 'Manila Wool Coat over a charcoal knit.'],
    ['img' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=1200&auto=format&fit=crop', 'title' => 'Look 05 — Off-Duty', 'note' => 'Cloud Zip Hoodie with the Maison Court Sneaker.'],
    ['img' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?q=80&w=1200&auto=format&fit=crop', 'title' => 'Look 06 — After Dark', 'note' => 'Sequin Cocktail Dress with the Torres Diamond Pendant.'],
];
?>

<section class="shop-header">
    <span class="eyebrow" style="justify-content:center;">Autumn 2026</span>
    <h1 class="section-title">The Maison Lookbook</h1>
    <p class="badge-count">Editorial styling from the house — six looks, one standard.</p>
</section>

<div class="container-lux" style="padding:3.6rem 0 6rem;">
    <div class="product-grid" style="grid-template-columns:repeat(2, minmax(0,1fr)); gap:3.2rem 2.6rem;">
        <?php foreach ($looks as $i => $look): ?>
        <article class="card-lux reveal" style="overflow:hidden;">
            <div style="aspect-ratio:4/5; overflow:hidden;">
                <img src="<?= e($look['img']) ?>" alt="<?= e($look['title']) ?>" loading="lazy" style="width:100%; height:100%; object-fit:cover;">
            </div>
            <div style="padding:1.6rem 1.8rem 2rem;">
                <p class="eyebrow" style="margin-bottom:0.6rem;">Look 0<?= $i + 1 ?></p>
                <h3 class="font-display" style="font-size:1.35rem; margin-bottom:0.6rem;"><?= e($look['title']) ?></h3>
                <p style="color:var(--text-secondary); font-size:0.9rem; line-height:1.7;"><?= e($look['note']) ?></p>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <div style="text-align:center; margin-top:4.5rem;">
        <a href="<?= SITE_URL ?>/new-arrivals.php" class="btn-lux btn-lux-filled">Shop the New Arrivals</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.reveal').forEach(el => el.classList.add('is-visible'));
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>