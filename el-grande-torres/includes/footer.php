<footer class="footer-lux">
    <div class="container-lux">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="<?= SITE_URL ?>/index.php" class="nav-logo">The Atelier Noir</a>
                <p>A 7-star house of clothing and essentials, founded on the belief that true luxury is quiet, considered, and built to last a lifetime — not a season.</p>
                <div class="footer-social">
                    <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="0.8" fill="currentColor" stroke="none"/></svg></a>
                    <a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M15 4h-2a4 4 0 0 0-4 4v3H7v4h2v6h4v-6h2.5l.5-4H13V8a1 1 0 0 1 1-1h2z"/></svg></a>
                    <a href="#" aria-label="TikTok"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M14 4v10.5a3.5 3.5 0 1 1-3-3.46"/><path d="M14 4c.5 2.5 2 4 5 4.2"/></svg></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Shop</h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>/clothing.php">Clothing</a></li>
                    <li><a href="<?= SITE_URL ?>/essentials.php">Essentials</a></li>
                    <li><a href="<?= SITE_URL ?>/new-arrivals.php">New Arrivals</a></li>
                    <li><a href="<?= SITE_URL ?>/best-sellers.php">Best Sellers</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Discover</h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>/about.php#story">Our Story</a></li>
                    <li><a href="<?= SITE_URL ?>/lookbook.php">Lookbook</a></li>
                    <li><a href="<?= SITE_URL ?>/journal.php">Journal</a></li>
                    <li><a href="<?= SITE_URL ?>/contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Client Care</h4>
                <ul>
                    <li><a href="#">Shipping &amp; Returns</a></li>
                    <li><a href="#">Size Guide</a></li>
                    <li><a href="<?= SITE_URL ?>/customer/orders.php">Track Order</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> The Atelier Noir. All rights reserved.</span>
            <span class="footer-credit">Developed by <?= e(DEVELOPER_CREDIT) ?></span>
            <div class="footer-payments"><span>COD</span><span>GCash</span><span>Maya</span></div>
        </div>
    </div>
</footer>

<div id="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<?php if (!empty($extra_js)): ?><script src="<?= e($extra_js) ?>"></script><?php endif; ?>
</body>
</html>