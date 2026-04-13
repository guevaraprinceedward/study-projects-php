<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: log-in.php");
    exit();
}

include 'config.php';

$user_id  = $_SESSION['user']['id'];
$username = $_SESSION['user']['username'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $user = ['id' => $user_id, 'username' => $username, 'password' => ''];
} else {
    $user = array_change_key_case($user, CASE_LOWER);
}
$success = "";
$error   = "";

// ── Update username ──────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {
    $new_username = trim($_POST["username"]);

    if (empty($new_username)) {
        $error = "Username is required.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->bind_param("si", $new_username, $user_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "That username is already taken.";
        } else {
            $upd = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $upd->bind_param("si", $new_username, $user_id);
            if ($upd->execute()) {
                $_SESSION['user']['username'] = $new_username;
                $user['username'] = $new_username;
                $username         = $new_username;
                $success = "Username updated successfully!";
            } else {
                $error = "Something went wrong. Please try again.";
            }
            $upd->close();
        }
        $check->close();
    }
}

// ── Change password ──────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {
    $current = $_POST["current_password"];
    $new_pw  = $_POST["new_password"];

    if (empty($current) || empty($new_pw)) {
        $error = "All password fields are required.";
    } elseif (strlen($new_pw) < 6) {
        $error = "New password must be at least 6 characters.";
    } else {
        $db_password = $user["password"] ?? "";

        // ✅ Support both hashed and plain text passwords
        $password_info = password_get_info($db_password);
        if ($password_info['algo'] !== null && $password_info['algo'] !== 0) {
            // Hashed password — gamitin ang password_verify
            $password_ok = password_verify($current, $db_password);
        } else {
            // Plain text password — direct compare
            $password_ok = ($current === $db_password);
        }

        if (!$password_ok) {
            $error = "Current password is incorrect.";
        } else {
            $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->bind_param("si", $hashed, $user_id);
            if ($upd->execute()) {
                $success = "Password changed successfully!";
                // I-update ang $user array para consistent
                $user["password"] = $hashed;
            } else {
                $error = "Something went wrong. Please try again.";
            }
            $upd->close();
        }
    }
}

$initials = strtoupper(substr($user['username'], 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — AyosCoffeeNegosyo</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #0b0b09;
            --surface:  #131310;
            --card:     #1a1a16;
            --border:   #2c2c24;
            --gold:     #c9a84c;
            --gold-dim: #8a6f2e;
            --green:    #4a7a3a;
            --green-lt: #6aaa52;
            --cream:    #f0ead8;
            --muted:    #6b6b58;
            --text:     #e8e4d8;
            --shadow:   rgba(0,0,0,0.6);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Jost', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 50% at 10% 0%, rgba(201,168,76,0.06) 0%, transparent 55%),
                radial-gradient(ellipse 50% 70% at 90% 100%, rgba(74,122,58,0.07) 0%, transparent 55%);
            pointer-events: none;
            z-index: 0;
        }

        /* ─────────────────────── HEADER ─────────────────────── */
        header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(11,11,9,0.88);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--border);
        }

        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 32px;
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-icon {
            width: 36px; height: 36px;
            border: 1px solid var(--gold-dim);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }

        .brand-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 600;
            color: var(--cream);
            letter-spacing: 0.04em;
        }

        .brand-name span { color: var(--gold); }

        nav { display: flex; align-items: center; gap: 6px; }

        nav a {
            font-size: 12.5px;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--muted);
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 3px;
            transition: color 0.2s, background 0.2s;
        }

        nav a:hover { color: var(--cream); background: rgba(255,255,255,0.04); }
        nav a.active { color: var(--gold); }

        .nav-cart {
            margin-left: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px !important;
            background: var(--green) !important;
            color: #fff !important;
            border-radius: 3px;
            font-weight: 500 !important;
            transition: background 0.2s !important;
        }

        .nav-cart:hover { background: var(--green-lt) !important; }

        .cart-badge {
            background: var(--gold);
            color: #000;
            font-size: 10px;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
        }

        /* ─────────────────────── HERO ─────────────────────── */
        .hero {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 90px 32px 70px;
            max-width: 700px;
            margin: 0 auto;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 22px;
        }

        .hero-eyebrow::before,
        .hero-eyebrow::after {
            content: '';
            width: 28px;
            height: 1px;
            background: var(--gold-dim);
        }

        .hero h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(40px, 5vw, 64px);
            font-weight: 700;
            line-height: 1.08;
            color: var(--cream);
            letter-spacing: -0.01em;
            margin-bottom: 14px;
        }

        .hero h1 em { font-style: italic; color: var(--gold); }

        .hero p {
            font-size: 15px;
            color: var(--muted);
            line-height: 1.7;
            font-weight: 300;
        }

        /* ─────────────────────── AVATAR STRIP ─────────────────────── */
        .avatar-strip {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: center;
            padding: 0 32px 52px;
        }

        .avatar-wrap {
            display: flex;
            align-items: center;
            gap: 20px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 20px 32px;
            min-width: 340px;
        }

        .avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: 1px solid var(--gold-dim);
            background: linear-gradient(135deg, #2a1f08, #1a1a16);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--gold);
            flex-shrink: 0;
            letter-spacing: 0.05em;
        }

        .avatar-info {}

        .avatar-label {
            font-size: 10px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 4px;
        }

        .avatar-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 600;
            color: var(--cream);
        }

        /* ─────────────────────── MAIN SECTION ─────────────────────── */
        .profile-section {
            position: relative;
            z-index: 1;
            max-width: 860px;
            margin: 0 auto;
            padding: 0 32px 100px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* ─────────────────────── ALERTS ─────────────────────── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 18px;
            border-radius: 4px;
            font-size: 13.5px;
            line-height: 1.5;
        }

        .alert-error {
            background: rgba(192,57,43,0.1);
            border: 1px solid rgba(192,57,43,0.28);
            color: #e07060;
        }

        .alert-success {
            background: rgba(74,122,58,0.1);
            border: 1px solid rgba(74,122,58,0.3);
            color: var(--green-lt);
        }

        .alert svg { flex-shrink: 0; margin-top: 1px; }

        /* ─────────────────────── CARD ─────────────────────── */
        .profile-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 4px;
            overflow: hidden;
            animation: cardIn 0.5s ease both;
        }

        .profile-card:nth-child(1) { animation-delay: 0.05s; }
        .profile-card:nth-child(2) { animation-delay: 0.12s; }
        .profile-card:nth-child(3) { animation-delay: 0.19s; }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .profile-card:hover {
            border-color: var(--gold-dim);
            box-shadow: 0 8px 32px rgba(0,0,0,0.4), 0 0 0 1px rgba(201,168,76,0.06);
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .card-header {
            padding: 20px 28px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .card-header-icon {
            width: 34px; height: 34px;
            border: 1px solid var(--border);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--gold);
            flex-shrink: 0;
        }

        .card-header-text {}

        .card-header-eyebrow {
            font-size: 10px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--gold-dim);
            margin-bottom: 2px;
        }

        .card-header-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            font-weight: 600;
            color: var(--cream);
            line-height: 1.2;
        }

        .card-body {
            padding: 28px;
        }

        /* ─────────────────────── FORM ─────────────────────── */
        .form-group { margin-bottom: 18px; }
        .form-group:last-of-type { margin-bottom: 0; }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 3px;
            font-family: 'Jost', sans-serif;
            font-size: 14px;
            color: var(--cream);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            appearance: none;
        }

        .form-input::placeholder { color: var(--muted); }

        .form-input:focus {
            border-color: var(--gold-dim);
            box-shadow: 0 0 0 3px rgba(201,168,76,0.08);
        }

        .form-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            margin-top: 24px;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            background: var(--green);
            border: none;
            border-radius: 3px;
            font-family: 'Jost', sans-serif;
            font-size: 12.5px;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #fff;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
        }

        .btn-primary:hover  { background: var(--green-lt); }
        .btn-primary:active { transform: scale(0.97); }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 3px;
            font-family: 'Jost', sans-serif;
            font-size: 12.5px;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            text-decoration: none;
            transition: border-color 0.2s, color 0.2s;
        }

        .btn-ghost:hover { border-color: var(--muted); color: var(--cream); }

        /* ─────────────────────── FOOTER ─────────────────────── */
        footer {
            position: relative;
            z-index: 1;
            border-top: 1px solid var(--border);
            padding: 30px 32px;
            text-align: center;
        }

        footer p {
            font-size: 12px;
            color: var(--muted);
            letter-spacing: 0.06em;
        }

        footer p span { color: var(--gold-dim); }

        /* ─────────────────────── RESPONSIVE ─────────────────────── */
        @media (max-width: 640px) {
            .header-inner { padding: 0 16px; }
            .brand-name { font-size: 18px; }
            nav a:not(.nav-cart) { display: none; }
            .hero { padding: 60px 20px 50px; }
            .avatar-strip { padding: 0 16px 40px; }
            .avatar-wrap { min-width: unset; width: 100%; }
            .profile-section { padding: 0 16px 60px; }
            .card-body { padding: 20px; }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════ HEADER ═══════════════════════ -->
<header>
    <div class="header-inner">
        <a href="index.php" class="brand">
            <div class="brand-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 11l19-9-9 19-2-8-8-2z"/>
                </svg>
            </div>
            <span class="brand-name">My <span>AyosCoffeeNegosyo</span></span>
        </a>

        <nav>
            <a href="index.php">Menu</a>
            <a href="profile.php" class="active">Profile</a>
            <a href="log-out.php">Logout</a>
            <a href="cart.php" class="nav-cart">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                Cart
                <span class="cart-badge" id="cartCount">0</span>
            </a>
        </nav>
    </div>
</header>

<!-- ═══════════════════════ HERO ═══════════════════════ -->
<section class="hero">
    <div class="hero-eyebrow">Account Settings</div>
    <h1>Your <em>Profile</em></h1>
    <p>Manage your account details and keep your information up to date.</p>
</section>

<!-- ═══════════════════════ AVATAR STRIP ═══════════════════════ -->
<div class="avatar-strip">
    <div class="avatar-wrap">
        <div class="avatar"><?= htmlspecialchars($initials) ?></div>
        <div class="avatar-info">
            <div class="avatar-label">Logged in as</div>
            <div class="avatar-name"><?= htmlspecialchars($user['username']) ?></div>
        </div>
    </div>
</div>

<!-- ═══════════════════════ PROFILE SECTION ═══════════════════════ -->
<main class="profile-section">

    <?php if ($error): ?>
    <div class="alert alert-error">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <!-- ── Card 1: Username ── -->
    <div class="profile-card">
        <div class="card-header">
            <div class="card-header-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div class="card-header-text">
                <div class="card-header-eyebrow">Account</div>
                <div class="card-header-title">Profile Information</div>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" novalidate>
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input
                        class="form-input"
                        type="text"
                        id="username"
                        name="username"
                        value="<?= htmlspecialchars($user['username']) ?>"
                        required
                        autocomplete="username"
                    >
                </div>
                <div class="form-footer">
                    <button type="submit" name="update_profile" class="btn-primary">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── Card 2: Password ── -->
    <div class="profile-card">
        <div class="card-header">
            <div class="card-header-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <div class="card-header-text">
                <div class="card-header-eyebrow">Security</div>
                <div class="card-header-title">Change Password</div>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" novalidate>
                <div class="form-group">
                    <label class="form-label" for="current_password">Current Password</label>
                    <input
                        class="form-input"
                        type="password"
                        id="current_password"
                        name="current_password"
                        placeholder="Enter your current password"
                        autocomplete="current-password"
                    >
                </div>
                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <input
                        class="form-input"
                        type="password"
                        id="new_password"
                        name="new_password"
                        placeholder="At least 6 characters"
                        autocomplete="new-password"
                    >
                </div>
                <div class="form-footer">
                    <a href="index.php" class="btn-ghost">Cancel</a>
                    <button type="submit" name="change_password" class="btn-primary">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

</main>

<!-- ═══════════════════════ FOOTER ═══════════════════════ -->
<footer>
    <p>© 2026 <span>AyosCoffeeNegosyo</span> — All rights reserved.</p>
</footer>

<script>
function updateCartBadge() {
    fetch('cart_handler.php?count=1')
        .then(r => r.json())
        .then(data => {
            document.getElementById('cartCount').textContent = data.count || 0;
        })
        .catch(() => {});
}
updateCartBadge();
</script>

</body>
</html>
