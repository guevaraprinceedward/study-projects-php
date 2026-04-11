<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: log-in.php");
    exit();
}

include 'config.php';

$user_id  = $_SESSION['user']['id']       ?? 0;
$username = $_SESSION['user']['username'] ?? $_SESSION['user'];

// Fetch fresh user data from DB
$user = [];
if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fallback if no DB row found
if (!$user) {
    $user = [
        'id'       => $user_id,
        'username' => $username,
        'email'    => $_SESSION['user']['email'] ?? '',
        'password' => '',
    ];
}

$success = "";
$error   = "";

// ── Update profile info ──────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {
    $new_username = trim($_POST["username"]);
    $new_email    = trim($_POST["email"]);

    if (empty($new_username) || empty($new_email)) {
        $error = "Username and email are required.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $check->bind_param("ssi", $new_username, $new_email, $user_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "That username or email is already taken.";
        } else {
            $upd = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $upd->bind_param("ssi", $new_username, $new_email, $user_id);
            if ($upd->execute()) {
                $_SESSION['user']['username'] = $new_username;
                $_SESSION['user']['email']    = $new_email;
                $user['username'] = $new_username;
                $user['email']    = $new_email;
                $username         = $new_username;
                $success = "Profile updated successfully!";
            } else {
                $error = "Something went wrong. Please try again.";
            }
            $upd->close();
        }
        $check->close();
    }
}

// ── Change password ──────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {
    $current = $_POST["current_password"];
    $new_pw  = $_POST["new_password"];
    $confirm = $_POST["confirm_password"];

    if (empty($current) || empty($new_pw) || empty($confirm)) {
        $error = "All password fields are required.";
    } elseif ($new_pw !== $confirm) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_pw) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif (!password_verify($current, $user["password"])) {
        $error = "Current password is incorrect.";
    } else {
        $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->bind_param("si", $hashed, $user_id);
        if ($upd->execute()) {
            $success = "Password changed successfully!";
        } else {
            $error = "Something went wrong. Please try again.";
        }
        $upd->close();
    }
}

// ── Initials for avatar ──────────────────────────────────────────
$initials = strtoupper(substr($user['username'] ?? 'U', 0, 2));
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

        /* ── HEADER ── */
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

        /* ── PAGE LAYOUT ── */
        .page-wrap {
            position: relative;
            z-index: 1;
            max-width: 900px;
            margin: 0 auto;
            padding: 60px 32px 100px;
        }

        /* ── PROFILE HERO ── */
        .profile-hero {
            display: flex;
            align-items: center;
            gap: 28px;
            margin-bottom: 48px;
            padding-bottom: 40px;
            border-bottom: 1px solid var(--border);
        }

        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold-dim), #5a4018);
            border: 2px solid var(--gold-dim);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Cormorant Garamond', serif;
            font-size: 30px;
            font-weight: 700;
            color: var(--gold);
            flex-shrink: 0;
            letter-spacing: 0.05em;
        }

        .profile-meta {
            flex: 1;
        }

        .profile-eyebrow {
            font-size: 10px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 6px;
        }

        .profile-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            font-weight: 700;
            color: var(--cream);
            line-height: 1.1;
        }

        .profile-name em {
            font-style: italic;
            color: var(--gold);
        }

        .profile-email {
            margin-top: 6px;
            font-size: 13px;
            color: var(--muted);
            font-weight: 300;
        }

        /* ── SECTION CARDS ── */
        .section-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 24px;
            animation: fadeUp 0.4s ease both;
        }

        .section-card:nth-child(1) { animation-delay: 0.05s; }
        .section-card:nth-child(2) { animation-delay: 0.12s; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .section-header {
            padding: 20px 28px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-icon {
            width: 32px; height: 32px;
            border: 1px solid var(--border);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--gold);
            flex-shrink: 0;
        }

        .section-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            font-weight: 600;
            color: var(--cream);
        }

        .section-body {
            padding: 28px;
        }

        /* ── ALERTS ── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 3px;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 24px;
        }

        .alert-error {
            background: rgba(192,57,43,0.12);
            border: 1px solid rgba(192,57,43,0.3);
            color: #e07060;
        }

        .alert-success {
            background: rgba(74,122,58,0.12);
            border: 1px solid rgba(74,122,58,0.3);
            color: var(--green-lt);
        }

        .alert svg { flex-shrink: 0; margin-top: 1px; }

        /* ── FORM ── */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            margin-bottom: 18px;
        }

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
            padding: 11px 14px;
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

        .form-actions {
            display: flex;
            justify-content: flex-end;
            padding-top: 8px;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 24px;
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
            gap: 8px;
            padding: 11px 24px;
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
            cursor: pointer;
            transition: border-color 0.2s, color 0.2s;
            margin-right: 10px;
        }

        .btn-ghost:hover { border-color: var(--muted); color: var(--cream); }

        /* ── FOOTER ── */
        footer {
            position: relative;
            z-index: 1;
            border-top: 1px solid var(--border);
            padding: 28px 32px;
            text-align: center;
        }

        footer p {
            font-size: 12px;
            color: var(--muted);
            letter-spacing: 0.06em;
        }

        footer p span { color: var(--gold-dim); }

        /* ── RESPONSIVE ── */
        @media (max-width: 640px) {
            .header-inner { padding: 0 16px; }
            nav a:not(.nav-cart) { display: none; }
            .page-wrap { padding: 40px 16px 60px; }
            .profile-hero { flex-direction: column; align-items: flex-start; gap: 16px; }
            .form-row { grid-template-columns: 1fr; }
            .section-body { padding: 20px; }
        }
    </style>
</head>
<body>

<!-- ═══ HEADER ═══ -->
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
            </a>
        </nav>
    </div>
</header>

<!-- ═══ MAIN ═══ -->
<div class="page-wrap">

    <!-- Profile hero -->
    <div class="profile-hero">
        <div class="avatar"><?= htmlspecialchars($initials) ?></div>
        <div class="profile-meta">
            <div class="profile-eyebrow">Member Account</div>
            <h1 class="profile-name">
                Welcome back, <em><?= htmlspecialchars($user['username']) ?></em>
            </h1>
            <?php if (!empty($user['email'])): ?>
                <p class="profile-email"><?= htmlspecialchars($user['email']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert (shared for both forms) -->
    <?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:28px;">
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
    <div class="alert alert-success" style="margin-bottom:28px;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <!-- ── Section 1: Profile Info ── -->
    <div class="section-card">
        <div class="section-header">
            <div class="section-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <h2 class="section-title">Profile Information</h2>
        </div>
        <div class="section-body">
            <form method="POST" novalidate>
                <div class="form-row">
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
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input
                            class="form-input"
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                            required
                            autocomplete="email"
                        >
                    </div>
                </div>
                <div class="form-actions">
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

    <!-- ── Section 2: Change Password ── -->
    <div class="section-card">
        <div class="section-header">
            <div class="section-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <h2 class="section-title">Change Password</h2>
        </div>
        <div class="section-body">
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
                <div class="form-row">
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
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm New Password</label>
                        <input
                            class="form-input"
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Repeat new password"
                            autocomplete="new-password"
                        >
                    </div>
                </div>
                <div class="form-actions">
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

</div>

<!-- ═══ FOOTER ═══ -->
<footer>
    <p>© 2026 <span>AyosCoffeeNegosyo</span> — All rights reserved.</p>
</footer>

</body>
</html>