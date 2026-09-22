<?php
require_once __DIR__ . '/config/app.php';

$page_title = "Journal — El Grande De La Torres";
$meta_description = "Stories from the atelier — craft, material, and the philosophy behind El Grande De La Torres.";
require_once __DIR__ . '/includes/header.php';

$articles = [
    [
        'title' => 'Inside the Atelier: How a Crest Tee Is Made',
        'excerpt' => 'From fabric selection to the final embroidery pass — a look at the twelve steps behind the house signature tee.',
        'img' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=1000&auto=format&fit=crop',
        'date' => 'August 2026',
        'tag' => 'Craft',
    ],
    [
        'title' => 'A Short History of the House Crest',
        'excerpt' => 'The crest that marks every piece began as a single sketch in 2026 — here is how it came to define the house.',
        'img' => 'https://images.unsplash.com/photo-1441984904996-e0b6ba687e04?q=80&w=1000&auto=format&fit=crop',
        'date' => 'July 2026',
        'tag' => 'Heritage',
    ],
    [
        'title' => 'Caring for Wool: A Seasonal Guide',
        'excerpt' => 'Coats, vests, and trousers built to outlast the season — a practical guide to keeping wool pieces at their best.',
        'img' => 'https://images.unsplash.com/photo-1490578474895-699cd4e2cf59?q=80&w=1000&auto=format&fit=crop',
        'date' => 'July 2026',
        'tag' => 'Care Guide',
    ],
    [
        'title' => 'Limited Edition: What "Numbered" Actually Means',
        'excerpt' => 'Every limited piece carries a number and a story. Here is what goes into deciding how few pieces are made.',
        'img' => 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?q=80&w=1000&auto=format&fit=crop',
        'date' => 'August 2026',
        'tag' => 'Limited Edition',
    ],
];
?>

<section class="shop-header">
    <span class="eyebrow" style="justify-content:center;">From the House</span>
    <h1 class="section-title">The Journal</h1>
    <p class="badge-count">Stories on craft, material, and the philosophy behind every piece.</p>
</section>

<div class="container-lux" style="padding:3.6rem 0 6rem;">
    <div class="product-grid" style="grid-template-columns:repeat(2, minmax(0,1fr)); gap:3.2rem 2.6rem;">
        <?php foreach ($articles as $a): ?>
        <article class="card-lux reveal" style="overflow:hidden;">
            <div style="aspect-ratio:16/10; overflow:hidden;">
                <img src="<?= e($a['img']) ?>" alt="<?= e($a['title']) ?>" loading="lazy" style="width:100%; height:100%; object-fit:cover;">
            </div>
            <div style="padding:1.8rem 2rem 2.2rem;">
                <p style="font-size:0.7rem; letter-spacing:0.14em; text-transform:uppercase; color:var(--gold); margin-bottom:0.7rem;">
                    <?= e($a['tag']) ?> &middot; <?= e($a['date']) ?>
                </p>
                <h3 class="font-display" style="font-size:1.5rem; margin-bottom:0.8rem;"><?= e($a['title']) ?></h3>
                <p style="color:var(--text-secondary); font-size:0.9rem; line-height:1.8; margin-bottom:1.2rem;"><?= e($a['excerpt']) ?></p>
                <a href="#" style="font-size:0.75rem; letter-spacing:0.14em; text-transform:uppercase; color:var(--gold);">Read the Story &rarr;</a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.reveal').forEach(el => el.classList.add('is-visible'));
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>