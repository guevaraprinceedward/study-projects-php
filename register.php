<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

include 'config.php';

$error   = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check if username or email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Username or email is already taken.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed);

            if ($stmt->execute()) {
                $success = "Account created! You can now log in.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — AyosCoffeeNegosyo</title>
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
            --danger:   #c0392b;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Jost', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 15% 0%, rgba(201,168,76,0.07) 0%, transparent 55%),
                radial-gradient(ellipse 50% 60% at 85% 100%, rgba(74,122,58,0.07) 0%, transparent 55%);
            pointer-events: none;
            z-index: 0;
        }

        /* ── HEADER ── */
        header {
            position: relative;
            z-index: 10;
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

        .header-link {
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

        .header-link:hover { color: var(--cream); background: rgba(255,255,255,0.04); }

        /* ── MAIN WRAPPER ── */
        .page-wrap {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 24px;
        }

        /* ── CARD ── */
        .auth-card {
            width: 100%;
            max-width: 460px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 4px;
            overflow: hidden;
            animation: fadeUp 0.5s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .card-top {
            padding: 40px 40px 32px;
            border-bottom: 1px solid var(--border);
            text-align: center;
        }

        .card-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 10px;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 16px;
        }

        .card-eyebrow::before,
        .card-eyebrow::after {
            content: '';
            width: 22px; height: 1px;
            background: var(--gold-dim);
        }

        .card-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            font-weight: 700;
            color: var(--cream);
            line-height: 1.1;
            letter-spacing: -0.01em;
        }

        .card-title em {
            font-style: italic;
            color: var(--gold);
        }

        .card-subtitle {
            margin-top: 10px;
            font-size: 13px;
            color: var(--muted);
            font-weight: 300;
        }

        /* ── FORM ── */
        .card-body {
            padding: 32px 40px 40px;
        }

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

        .form-divider {
            height: 1px;
            background: var(--border);
            margin: 24px 0;
        }

        .form-section-label {
            font-size: 10px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--gold-dim);
            margin-bottom: 16px;
            margin-top: -8px;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: var(--green);
            border: none;
            border-radius: 3px;
            font-family: 'Jost', sans-serif;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #fff;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
            margin-top: 8px;
        }

        .submit-btn:hover  { background: var(--green-lt); }
        .submit-btn:active { transform: scale(0.98); }

        .card-footer-link {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: var(--muted);
        }

        .card-footer-link a {
            color: var(--gold);
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.2s;
        }

        .card-footer-link a:hover { opacity: 0.75; }

        /* ── FOOTER ── */
        footer {
            position: relative;
            z-index: 1;
            border-top: 1px solid var(--border);
            padding: 24px 32px;
            text-align: center;
        }

        footer p {
            font-size: 12px;
            color: var(--muted);
            letter-spacing: 0.06em;
        }

        footer p span { color: var(--gold-dim); }

        @media (max-width: 520px) {
            .card-top, .card-body { padding-left: 24px; padding-right: 24px; }
            .card-title { font-size: 30px; }
        }
    </style>
</head>
<body>

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
        <a href="log-in.php" class="header-link">Login</a>
    </div>
</header>

<div class="page-wrap">
    <div class="auth-card">

        <div class="card-top">
            <div class="card-eyebrow">New Account</div>
            <h1 class="card-title">Create an <em>Account</em></h1>
            <p class="card-subtitle">Join us and start ordering your favorites.</p>
        </div>

        <div class="card-body">

            <?php if ($error): ?>
            <div class="alert alert-error">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
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
                <a href="log-in.php" style="color:inherit;font-weight:600;margin-left:6px;">Log in →</a>
            </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" novalidate>

                <p class="form-section-label">Account Info</p>

                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input
                        class="form-input"
                        type="text"
                        id="username"
                        name="username"
                        placeholder="e.g. juandelacruz"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
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
                        placeholder="you@example.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form-divider"></div>
                <p class="form-section-label">Password</p>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input
                        class="form-input"
                        type="password"
                        id="password"
                        name="password"
                        placeholder="At least 6 characters"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input
                        class="form-input"
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Repeat your password"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <button type="submit" name="register" class="submit-btn">
                    Create Account
                </button>

            </form>
            <?php endif; ?>

            <div class="card-footer-link">
                Already have an account? <a href="log-in.php">Log in</a>
            </div>

        </div>
    </div>
</div>

<footer>
    <p>© 2026 <span>AyosCoffeeNegosyo</span> — All rights reserved.</p>
</footer>

</body>
</html>