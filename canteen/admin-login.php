<?php
/**
 * admin-login.php — COPS staff login
 * Checks staff against the `admin_users` table, starts the session that
 * admin-config.php's getLoggedInAdmin() reads, and records every login / failed
 * attempt in `auth_events` and `admin_logs`. Five wrong passwords lock that
 * device for 5 minutes.
 *
 * The left panel now shows the CMDI school + canteen illustration (includes/school-art.php).
 */
include __DIR__ . '/admin-config.php';
require_once __DIR__ . '/auth-helpers.php';
require_once __DIR__ . '/includes/school-art.php';

if (getLoggedInAdmin($adminConn)) {
    header('Location: admin-reservations.php');
    exit();
}

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

$error       = '';
$notice      = isset($_GET['out']) ? 'You have been logged out.' : '';
$setupNote   = '';
$identifier  = '';
$lockSeconds = 0;

try {
    $cnt = $adminConn->query("SELECT COUNT(*) AS n FROM admin_users")->fetch_assoc();
    if ((int)$cnt['n'] === 0) {
        $setupNote = 'No staff accounts exist yet. Import admin-user-insert.sql in phpMyAdmin to create the first one.';
    }
} catch (Throwable $e) {
    error_log('COPS admin-login count failed: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = mb_substr(trim((string)($_POST['identifier'] ?? '')), 0, 100);
    $password   = (string)($_POST['password'] ?? '');

    if (!hash_equals($_SESSION['admin_csrf'], (string)($_POST['csrf'] ?? ''))) {
        $error = 'Your session expired. Please try again.';
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    } elseif ($identifier === '' || $password === '') {
        $error = 'Enter your staff ID or username, and your password.';
    } elseif (strlen($password) > 1024) {
        $error = 'That password is too long.';
    } elseif (($lockSeconds = authLockRemaining($adminConn, 'staff', $identifier)) > 0) {
        $error = 'Too many failed attempts. Please wait before trying again.';
    } else {
        $staff = null;
        try {
            $stmt = $adminConn->prepare(
                "SELECT id, staff_id, username, password, full_name, role, is_active
                 FROM admin_users WHERE username = ? OR staff_id = ? LIMIT 1"
            );
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $staff = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } catch (Throwable $e) {
            error_log('COPS admin-login lookup failed: ' . $e->getMessage());
            $error = 'Something went wrong on our side. Please try again.';
        }

        if ($error === '') {
            if ($staff) {
                [$ok, $newHash] = authCheckPassword((string)$staff['password'], $password);
            } else {
                password_hash($password, PASSWORD_DEFAULT);   // keeps timing the same whether or not the account exists
                [$ok, $newHash] = [false, null];
            }

            if ($ok && (int)$staff['is_active'] !== 1) {
                authLogEvent($adminConn, 'staff', (int)$staff['id'], $identifier, 'blocked');
                $error = 'This staff account is disabled. Please contact the administrator.';
            } elseif ($ok) {
                try {
                    if ($newHash !== null) {
                        $up = $adminConn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                        $up->bind_param('si', $newHash, $staff['id']);
                        $up->execute();
                        $up->close();
                    }
                    $lg = $adminConn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                    $lg->bind_param('i', $staff['id']);
                    $lg->execute();
                    $lg->close();
                } catch (Throwable $e) {
                    error_log('COPS admin-login post-login update failed: ' . $e->getMessage());
                }

                session_regenerate_id(true);
                $_SESSION['admin'] = [
                    'id'        => (int)$staff['id'],
                    'staff_id'  => $staff['staff_id'],
                    'username'  => $staff['username'],
                    'full_name' => $staff['full_name'],
                    'role'      => $staff['role'],
                ];
                $_SESSION['admin_last_seen'] = time();
                $_SESSION['admin_csrf']      = bin2hex(random_bytes(32));

                authLogEvent($adminConn, 'staff', (int)$staff['id'], $identifier, 'login');
                adminLog($adminConn, (int)$staff['id'], 'login', null, 'Logged in');

                header('Location: admin-reservations.php');
                exit();
            } else {
                authLogEvent($adminConn, 'staff', $staff ? (int)$staff['id'] : null, $identifier, 'failed');
                $lockSeconds = authLockRemaining($adminConn, 'staff', $identifier);
                $error = $lockSeconds > 0
                    ? 'Too many failed attempts. Please wait before trying again.'
                    : 'Incorrect staff ID, username, or password.';
            }
        }
    }
}
$disabled = $lockSeconds > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff login — COPS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=EB+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="cops-theme.css">
<link rel="stylesheet" href="cops-auth.css">
<link rel="stylesheet" href="cops-school.css">
<link rel="stylesheet" href="cops-school-login.css">
</head>
<body class="auth staff">
<div class="auth-col l" aria-hidden="true"></div>
<div class="auth-col r" aria-hidden="true"></div>

<div style="position:relative;z-index:1;width:100%;max-width:1020px">
<main class="auth-shell">
    <aside class="temple">
        <div class="moon" aria-hidden="true"></div>
        <a href="index.php" class="brand">
            <span class="brand-mark" style="width:52px;height:52px;padding:3px;overflow:hidden;background:linear-gradient(145deg,#f6e7b0,#c9a24a 40%,#8a6a1f 68%,#e2c46c);box-shadow:0 8px 16px -8px rgba(0,0,0,.55)"><img src="assets/img/cmdi-logo.jpg" alt="CMDI logo" width="52" height="52" style="width:100%;height:100%;border-radius:50%;object-fit:cover;display:block;background:#fff"></span>
            <span class="brand-name">COPS<span class="brand-sub">Staff portal</span></span>
        </a>
        <div class="temple-copy">
            <h2>Keep the counter calm.</h2>
            <p class="t">See every student reservation as it comes in. Set it to pending, then mark it ready for pickup.</p>
            <div class="maxim" lang="grc">Μηδὲν ἄγαν<small>Nothing in excess. Not even the lunch rush.</small></div>
        </div>
        <!-- CMDI school + canteen illustration -->
        <div class="temple-art"><div class="school-scene-wrap"><?= school_scene('compact', 'lg') ?></div></div>
    </aside>

    <section class="scroll">
        <div class="meander m top" aria-hidden="true"></div>
        <div class="scroll-in">
            <svg class="crest" viewBox="-40 -40 80 80" aria-hidden="true">
                <defs><clipPath id="crestLogoClip"><circle r="14"/></clipPath></defs>
                <g data-wreath="32" data-pairs="8"></g>
                <circle r="17" fill="url(#gG)" stroke="#8a6a1f" stroke-width=".8"/>
                <circle r="14.4" fill="#fff"/>
                <image href="assets/img/cmdi-logo.jpg" x="-14" y="-14" width="28" height="28" clip-path="url(#crestLogoClip)" preserveAspectRatio="xMidYMid slice"/>
                <circle r="14" fill="none" stroke="#8a6a1f" stroke-opacity=".5" stroke-width=".5"/>
            </svg>
            <p class="eyebrow"><span lang="grc">ΕΙΣΟΔΟΣ</span> &middot; <span>Hall of staff</span></p>
            <h1>Staff login</h1>
            <div class="rule" aria-hidden="true"><i></i><b></b><i></i></div>
            <p class="lead">Use your staff ID or username.</p>

            <?php if ($setupNote !== ''): ?>
                <div class="plaque warn" role="status"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg><span><?= htmlspecialchars($setupNote) ?></span></div>
            <?php endif; ?>
            <?php if ($notice !== ''): ?>
                <div class="plaque ok" role="status"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><span><?= htmlspecialchars($notice) ?></span></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="plaque err" role="alert"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span><?= htmlspecialchars($error) ?><?php if ($lockSeconds > 0): ?> Try again in <b id="lockCount" data-left="<?= (int)$lockSeconds ?>"><?= (int)$lockSeconds ?> seconds</b>.<?php endif; ?></span></div>
            <?php endif; ?>

            <form method="POST" id="loginForm" autocomplete="off" novalidate>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                <div class="field">
                    <label for="identifier">STAFF ID OR USERNAME</label>
                    <div class="inp">
                        <svg class="ic" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" id="identifier" name="identifier" placeholder="e.g. STF-0002" value="<?= htmlspecialchars($identifier) ?>" maxlength="100" required autofocus autocomplete="username" autocapitalize="off" spellcheck="false">
                    </div>
                </div>
                <div class="field">
                    <label for="password">PASSWORD</label>
                    <div class="inp pw">
                        <svg class="ic" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="1"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="password" name="password" placeholder="Your password" maxlength="1024" required <?= $disabled ? 'disabled' : '' ?> autocomplete="current-password">
                        <button type="button" class="toggle" id="pwToggle" aria-label="Show password">
                            <svg id="eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <div class="caps" id="caps" role="status"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>Caps Lock is on</div>
                </div>
                <button type="submit" class="btn btn-gold auth-submit" id="submitBtn" <?= $disabled ? 'disabled' : '' ?>><span class="spin"></span><span id="submitLbl">LOG IN TO DASHBOARD</span></button>
            </form>

            <div class="alt">
                <span>Are you a student? <a href="log-in.php">Student login</a></span>
                <a href="index.php">Back to home</a>
            </div>
        </div>
        <div class="meander m bot" aria-hidden="true"></div>
    </section>
</main>
<p class="auth-foot">&copy; 2026 COPS &middot; STAFF ACCESS ONLY</p>
</div>

<script src="cops-ui.js"></script>
<script>
(function () {
    var pw = document.getElementById('password'), btn = document.getElementById('pwToggle'), eye = document.getElementById('eye');
    var openEye = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    var shutEye = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
    btn.addEventListener('click', function () {
        var show = pw.type === 'password'; pw.type = show ? 'text' : 'password'; eye.innerHTML = show ? shutEye : openEye;
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });

    var caps = document.getElementById('caps');
    function capsCheck(e) { if (e.getModifierState) caps.classList.toggle('on', e.getModifierState('CapsLock')); }
    pw.addEventListener('keyup', capsCheck); pw.addEventListener('keydown', capsCheck);
    pw.addEventListener('blur', function () { caps.classList.remove('on'); });

    var form = document.getElementById('loginForm'), sub = document.getElementById('submitBtn'), lbl = document.getElementById('submitLbl');
    var idleText = lbl.textContent;
    form.addEventListener('submit', function (e) {
        if (sub.classList.contains('busy')) { e.preventDefault(); return; }
        if (!document.getElementById('identifier').value.trim() || !pw.value) { return; }
        sub.classList.add('busy'); lbl.textContent = 'CHECKING…';
        setTimeout(function () { sub.disabled = true; }, 0);
    });
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) { sub.classList.remove('busy'); sub.disabled = false; lbl.textContent = idleText; }
    });

    var lockEl = document.getElementById('lockCount');
    if (lockEl) {
        var left = parseInt(lockEl.getAttribute('data-left'), 10) || 0;
        var fmt = function (s) { return s >= 60 ? Math.floor(s / 60) + ' min ' + (s % 60) + ' sec' : s + ' seconds'; };
        lockEl.textContent = fmt(left);
        var timer = setInterval(function () {
            left--;
            if (left <= 0) {
                clearInterval(timer);
                var plaque = lockEl.closest('.plaque'); if (plaque) plaque.remove();
                pw.disabled = false; sub.disabled = false; pw.value = ''; pw.focus();
            } else { lockEl.textContent = fmt(left); }
        }, 1000);
    }
})();
</script>
</body>
</html>