<?php
/** includes/footer.php — public footer + scripts. Optional: $page['scripts'] = ['assets/js/menu.js'] */
$info = COPS_INFO;
?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-brand">COPS</div>
                <p>Canteen Ordering Platform System. Browse the menu, reserve your meal, and pick it up without the line.</p>
            </div>
            <div>
                <h4>Explore</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="menu.php">Menu &amp; reservations</a></li>
                    <li><a href="menu.php?view=orders">My reservations</a></li>
                    <li><a href="admin-login.php">Staff portal</a></li>
                </ul>
            </div>
            <div>
                <h4>Canteen information</h4>
                <ul>
                    <?php if ($info['location'] !== ''): ?><li><?= e($info['location']) ?></li><?php endif; ?>
                    <?php if ($info['hours'] !== ''): ?><li><?= e($info['hours']) ?></li><?php endif; ?>
                    <li><?= e(COPS_PAYMENT_NOTE) ?></li>
                    <li>Orders are prepared after staff confirm your request.</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom"><span>&copy; <?= date('Y') ?> COPS. A school canteen reservation project.</span><span>Reserve first. Skip the line.</span></div>
    </div>
</footer>

<script src="assets/js/app.js"></script>
<?php foreach (($page['scripts'] ?? []) as $src): ?><script src="<?= e($src) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>