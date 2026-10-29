<?php
/**
 * log-in.php — COPS student login
 * Checks the student against the `users` table, starts the session that
 * config.php's getLoggedInUser() reads, and records every login / failed attempt
 * in `auth_events`. Five wrong passwords lock that device for 5 minutes.
 *
 *   log-in.php?next=menu.php   → after login, go to menu.php (page names only)
 *   log-in.php?out=1           → shows "You have been logged out."
 *
 * The left panel shows the CMDI school + canteen illustration (includes/school-art.php)
 * and the CMDI logo (assets/img/cmdi-logo.jpg).
 */
include 'config.php';
require_once 'auth-helpers.php';
require_once __DIR__ . '/includes/school-art.php';

$next = authSafeNext($_POST['next'] ?? ($_GET['next'] ?? ''), 'menu.php');

/* ── Check the users table is ready ─────────────────────────────────────── */
$setupError = '';
$cols       = [];
try {
    $t = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$t || $t->num_rows === 0) {
        $setupError = 'Student accounts are not set up yet. Import users-setup.sql into canteen_cmdi_db, then refresh this page.';
    } else {
        $rc = $conn->query("SHOW COLUMNS FROM `users`");
        while ($c = $rc->fetch_assoc()) {
            $cols[$c['Field']] = true;
        }
        $required = ['id', 'student_id', 'username', 'first_name', 'last_name', 'role', 'is_banned'];
        $missing  = array_values(array_filter($required, fn($c) => !isset($cols[$c])));
        if ($missing) {
            $setupError = 'The users table is missing these columns: ' . implode(', ', $missing) . '. Run users-setup.sql to add them.';
        }
    }
} catch (Throwable $e) {
    error_log('COPS log-in setup check failed: ' . $e->getMessage());
    $setupError = 'The student database could not be read. Please tell the canteen staff.';
}

$passCol  = isset($cols['password']) ? 'password' : (isset($cols['password_hash']) ? 'password_hash' : null);
$loginCol = isset($cols['last_login']) ? 'last_login' : (isset($cols['last_login_at']) ? 'last_login_at' : null);
$idCols   = array_values(array_filter(['student_id', 'username', 'email'], fn($c) => isset($cols[$c])));
if ($setupError === '' && !$passCol) {
    $setupError = 'The users table has no password column. Run users-setup.sql to add it.';
}

/* ── Already logged in? ─────────────────────────────────────────────────── */
if ($setupError === '') {
    try {
        if (getLoggedInUser($conn)) {
            header('Location: ' . $next);
            exit();
        }
    } catch (Throwable $e) {
        error_log('COPS log-in session check failed: ' . $e->getMessage());
    }
}

if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

$error       = '';
$notice      = isset($_GET['out']) ? 'You have been logged out.' : '';
$identifier  = '';
$lockSeconds = 0;

/* ── Handle the form ────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $setupError === '') {
    $identifier = mb_substr(trim((string)($_POST['identifier'] ?? '')), 0, 100);
    $password   = (string)($_POST['password'] ?? '');

    if (!hash_equals($_SESSION['login_csrf'], (string)($_POST['csrf'] ?? ''))) {
        $error = 'Your session expired. Please try again.';
        $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
    } elseif ($identifier === '' || $password === '') {
        $error = 'Enter your student ID or username, and your password.';
    } elseif (strlen($password) > 1024) {
        $error = 'That password is too long.';
    } elseif (($lockSeconds = authLockRemaining($conn, 'student', $identifier)) > 0) {
        $error = 'Too many failed attempts. Please wait before trying again.';
    } else {
        $user = null;
        try {
            $where = implode(' OR ', array_map(fn($c) => "`$c` = ?", $idCols));   // column names come from a fixed list
            $sql   = "SELECT id, student_id, username, first_name, last_name, role, is_banned, `$passCol` AS pw
                      FROM users WHERE $where LIMIT 1";
            $stmt  = $conn->prepare($sql);
            $args  = array_fill(0, count($idCols), $identifier);
            $stmt->bind_param(str_repeat('s', count($idCols)), ...$args);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } catch (Throwable $e) {
            error_log('COPS log-in lookup failed: ' . $e->getMessage());
            $error = 'Something went wrong on our side. Please try again.';
        }

        if ($error === '') {
            if ($user) {
                [$ok, $newHash] = authCheckPassword((string)$user['pw'], $password);
            } else {
                password_hash($password, PASSWORD_DEFAULT);   // same work as a real check, so timing does not reveal which IDs exist
                [$ok, $newHash] = [false, null];
            }

            if ($ok && (int)$user['is_banned'] === 1) {
                authLogEvent($conn, 'student', (int)$user['id'], $identifier, 'blocked');
                $error = 'This account has been suspended. Please see the canteen staff.';
            } elseif ($ok) {
                try {
                    if ($newHash !== null) {
                        $up = $conn->prepare("UPDATE users SET `$passCol` = ? WHERE id = ?");
                        $up->bind_param('si', $newHash, $user['id']);
                        $up->execute();
                        $up->close();
                    }
                    if ($loginCol) {
                        $lg = $conn->prepare("UPDATE users SET `$loginCol` = NOW() WHERE id = ?");
                        $lg->bind_param('i', $user['id']);
                        $lg->execute();
                        $lg->close();
                    }
                } catch (Throwable $e) {
                    error_log('COPS log-in post-login update failed: ' . $e->getMessage());
                }

                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'         => (int)$user['id'],
                    'student_id' => $user['student_id'],
                    'name'       => trim($user['first_name'] . ' ' . $user['last_name']),
                    'role'       => $user['role'],
                ];
                unset($_SESSION['login_csrf']);
                $_SESSION['csrf'] = bin2hex(random_bytes(32));

                authLogEvent($conn, 'student', (int)$user['id'], $identifier, 'login');
                header('Location: ' . $next);
                exit();
            } else {
                authLogEvent($conn, 'student', $user ? (int)$user['id'] : null, $identifier, 'failed');
                $lockSeconds = authLockRemaining($conn, 'student', $identifier);
                $error = $lockSeconds > 0
                    ? 'Too many failed attempts. Please wait before trying again.'
                    : 'Incorrect student ID, username, or password.';
            }
        }
    }
}

$signupHref = '';
foreach (['sign-up.php', 'signup.php', 'register.php'] as $f) {
    if (is_file(__DIR__ . '/' . $f)) { $signupHref = $f; break; }
}
$disabled = $setupError !== '' || $lockSeconds > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student login — COPS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=EB+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="cops-theme.css">
<link rel="stylesheet" href="cops-auth.css">
<link rel="stylesheet" href="cops-school.css">
<link rel="stylesheet" href="cops-school-login.css">
</head>
<body class="auth">
<div class="auth-col l" aria-hidden="true"></div>
<div class="auth-col r" aria-hidden="true"></div>

<div style="position:relative;z-index:1;width:100%;max-width:1020px">
<main class="auth-shell">
    <aside class="temple">
        <div class="moon" aria-hidden="true"></div>
        <a href="index.php" class="brand">
            <span class="brand-mark" style="width:52px;height:52px;padding:3px;overflow:hidden;background:linear-gradient(145deg,#f6e7b0,#c9a24a 40%,#8a6a1f 68%,#e2c46c);box-shadow:0 8px 16px -8px rgba(0,0,0,.55)"><img src="assets/img/cmdi-logo.jpg" alt="CMDI logo" width="52" height="52" style="width:100%;height:100%;border-radius:50%;object-fit:cover;display:block;background:#fff"></span>
            <span class="brand-name">COPS<span class="brand-sub">Menu &amp; reservation</span></span>
        </a>
        <div class="temple-copy">
            <h2>Welcome back to the atrium.</h2>
            <p class="t">Log in to reserve your meal, follow its status, and claim it with your code.</p>
            <div class="maxim" lang="grc">Γνῶθι σαυτόν<small>Know thyself. And what you want for lunch.</small></div>
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
            <p class="eyebrow"><span lang="grc">ΕΙΣΟΔΟΣ</span> &middot; <span>Ingredere</span></p>
            <h1>Student login</h1>
            <div class="rule" aria-hidden="true"><i></i><b></b><i></i></div>
            <p class="lead">Use your student ID or username.</p>

            <?php if ($setupError !== ''): ?>
                <div class="plaque warn" role="alert"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><span><?= htmlspecialchars($setupError) ?></span></div>
            <?php endif; ?>
            <?php if ($notice !== ''): ?>
                <div class="plaque ok" role="status"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><span><?= htmlspecialchars($notice) ?></span></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="plaque err" role="alert"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span><?= htmlspecialchars($error) ?><?php if ($lockSeconds > 0): ?> Try again in <b id="lockCount" data-left="<?= (int)$lockSeconds ?>"><?= (int)$lockSeconds ?> seconds</b>.<?php endif; ?></span></div>
            <?php endif; ?>

            <form method="POST" id="loginForm" autocomplete="off" novalidate>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['login_csrf']) ?>">
                <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
                <div class="field">
                    <label for="identifier">STUDENT ID OR USERNAME</label>
                    <div class="inp">
                        <svg class="ic" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" id="identifier" name="identifier" placeholder="e.g. 2026-00123" value="<?= htmlspecialchars($identifier) ?>" maxlength="100" required <?= $setupError !== '' ? 'disabled' : 'autofocus' ?> autocomplete="username" autocapitalize="off" spellcheck="false">
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
                <button type="submit" class="btn btn-gold auth-submit" id="submitBtn" <?= $disabled ? 'disabled' : '' ?>><span class="spin"></span><span id="submitLbl">LOG IN</span></button>
            </form>

            <div class="alt">
                <span><?php if ($signupHref !== ''): ?>New here? <a href="<?= htmlspecialchars($signupHref) ?>">Create an account</a><?php else: ?>Canteen staff? <a href="admin-login.php">Staff portal</a><?php endif; ?></span>
                <a href="index.php">Back to home</a>
            </div>
        </div>
        <div class="meander m bot" aria-hidden="true"></div>
    </section>
</main>
<p class="auth-foot">&copy; 2026 COPS &middot; RESERVE FIRST, SKIP THE LINE</p>
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
    form.addEventListener('submit', function (e) {
        if (sub.classList.contains('busy')) { e.preventDefault(); return; }
        if (!document.getElementById('identifier').value.trim() || !pw.value) { return; }
        sub.classList.add('busy'); lbl.textContent = 'CHECKING…';
        setTimeout(function () { sub.disabled = true; }, 0);
    });
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) { sub.classList.remove('busy'); sub.disabled = false; lbl.textContent = 'LOG IN'; }
    });

    /* lock-out countdown: re-opens the form when the wait is over */
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