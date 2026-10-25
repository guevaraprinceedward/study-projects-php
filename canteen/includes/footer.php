<?php
/** includes/footer.php — public footer + scripts. Optional: $page['scripts'] = ['assets/js/menu.js'] */
$info = COPS_INFO;
?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-brand-row">
                    <span class="footer-logo"><img src="assets/img/cmdi-logo.jpg" alt="CMDI logo" width="64" height="64"></span>
                    <div>
                        <div class="footer-brand">COPS</div>
                        <div class="footer-inst">Card-MRI Development Institute, Inc.</div>
                    </div>
                </div>
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
        <div class="footer-bottom"><span>&copy; <?= date('Y') ?> COPS &middot; Card-MRI Development Institute, Inc. A school canteen reservation project.</span><span>Reserve first. Skip the line.</span></div>
    </div>
</footer>
<style>
.footer-brand-row{display:flex;align-items:center;gap:16px;margin-bottom:14px}
.footer-logo{flex:none;width:68px;height:68px;border-radius:50%;padding:3px;background:linear-gradient(145deg,#f6e7b0,#c9a24a 40%,#8a6a1f 68%,#e2c46c);box-shadow:0 10px 18px -8px rgba(0,0,0,.6),0 0 0 4px rgba(255,255,255,.08)}
.footer-logo img{width:100%;height:100%;border-radius:50%;object-fit:cover;background:#fff;display:block}
.footer-brand-row .footer-brand{margin:0;line-height:1.1}
.footer-inst{font-family:'Cormorant Garamond',Georgia,serif;font-style:italic;font-size:15.5px;color:#f0dca0;margin-top:2px}
</style>

<script src="assets/js/app.js"></script>
<?php foreach (($page['scripts'] ?? []) as $src): ?><script src="<?= e($src) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>