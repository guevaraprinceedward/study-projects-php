<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <span class="logo-badge">📸</span>
            <span class="logo-text">Kislap <em>Booth</em></span>
            <p>Cute photos, cuter memories. Your friendly neighborhood photobooth shop — pose, print, and pop it in your bag. ✨</p>
        </div>
        <div class="footer-col">
            <h4>Sessions</h4>
            <ul>
                <li><a href="<?= SITE_URL ?>/booth.php?type=solo">Solo</a></li>
                <li><a href="<?= SITE_URL ?>/booth.php?type=duo">2-in-1</a></li>
                <li><a href="<?= SITE_URL ?>/booth.php?type=couple">Couple</a></li>
                <li><a href="<?= SITE_URL ?>/booth.php?type=group">Group</a></li>
                <li><a href="<?= SITE_URL ?>/booth.php?type=barkada">Barkada</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Shop</h4>
            <ul>
                <li><a href="<?= SITE_URL ?>/index.php#how">How it Works</a></li>
                <li><a href="<?= SITE_URL ?>/index.php#designs">Frame Designs</a></li>
                <li><a href="<?= SITE_URL ?>/auth/login.php">My Account</a></li>
            </ul>
        </div>
        <div class="footer-wave-note">
            <span>Est. 2026 · Made with 💜 in the Philippines</span>
        </div>
    </div>
    <div class="footer-bottom">
        <span>&copy; <?= date('Y') ?> Kislap Booth. All rights reserved.</span>
        <div class="footer-payments"><span>💵 Cash</span><span>📱 GCash</span><span>💳 Card</span></div>
    </div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<?php if (!empty($extra_js)): foreach ((array)$extra_js as $js): ?>
<script src="<?= SITE_URL ?>/assets/js/<?= e($js) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>