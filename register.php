<?php include 'config.php'; ?>
<?php
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $query = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
    mysqli_query($conn, $query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — AyosCoffeeNegosyo</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #0e0f0c;
            --card:     #161710;
            --border:   #2a2c24;
            --green:    #5a7a3a;
            --green-lt: #7aad4a;
            --gold:     #c9a84c;
            --text:     #e8e6df;
            --muted:    #7a7a6a;
            --input-bg: #1e201a;
        }

        body {
            min-height: 100vh;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 20%, rgba(90,122,58,0.08) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 80% 80%, rgba(201,168,76,0.06) 0%, transparent 60%);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 4px;
            width: 100%;
            max-width: 400px;
            padding: 48px 40px 40px;
            position: relative;
            z-index: 1;
            animation: fadeUp 0.5s ease both;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 40px; height: 40px;
            border-top: 2px solid var(--gold);
            border-left: 2px solid var(--gold);
            border-radius: 3px 0 0 0;
        }

        .card::after {
            content: '';
            position: absolute;
            bottom: 0; right: 0;
            width: 40px; height: 40px;
            border-bottom: 2px solid var(--gold);
            border-right: 2px solid var(--gold);
            border-radius: 0 0 3px 0;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .logo-area { text-align: center; margin-bottom: 36px; }

        .logo-icon {
            width: 48px; height: 48px;
            margin: 0 auto 14px;
            display: flex; align-items: center; justify-content: center;
        }

        .logo-icon svg { width: 100%; height: 100%; }

        h2 {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: 0.01em;
        }

        .subtitle {
            font-size: 13px;
            color: var(--muted);
            margin-top: 6px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .divider-line { flex: 1; height: 1px; background: var(--border); }
        .divider-dot  { width: 4px; height: 4px; border-radius: 50%; background: var(--gold); }

        .success-box {
            background: rgba(90,122,58,0.12);
            border: 1px solid rgba(90,122,58,0.4);
            border-radius: 4px;
            padding: 12px 14px;
            margin-bottom: 22px;
            font-size: 13.5px;
            color: var(--green-lt);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .field { margin-bottom: 18px; }

        label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .input-wrap { position: relative; }

        .input-wrap > svg {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            width: 16px; height: 16px;
            pointer-events: none;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 12px 14px 12px 42px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--text);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input::placeholder { color: var(--muted); opacity: 0.6; }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--green-lt);
            box-shadow: 0 0 0 3px rgba(122,173,74,0.12);
        }

        .toggle-pw {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer; color: var(--muted);
            padding: 0; display: flex; align-items: center;
            transition: color 0.2s;
        }

        .toggle-pw:hover { color: var(--text); }

        button[type="submit"] {
            width: 100%;
            padding: 13px;
            margin-top: 10px;
            background: var(--green);
            border: none; border-radius: 4px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px; font-weight: 500;
            letter-spacing: 0.06em;
            color: #fff; cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            position: relative; overflow: hidden;
        }

        button[type="submit"]:hover  { background: var(--green-lt); }
        button[type="submit"]:active { transform: scale(0.98); }

        button[type="submit"]::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
            transition: left 0.4s ease;
        }

        button[type="submit"]:hover::after { left: 160%; }

        .login-link {
            text-align: center;
            margin-top: 22px;
            font-size: 13px;
            color: var(--muted);
        }

        .login-link a {
            color: var(--green-lt);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .login-link a:hover { color: var(--gold); }

        @media (max-width: 440px) {
            .card { padding: 36px 24px 32px; }
        }
    </style>
</head>
<body>

<div class="card">

    <div class="logo-area">
        <div class="logo-icon">
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="24" cy="24" r="23" stroke="#c9a84c" stroke-width="1.5"/>
                <path d="M17 10v8a4 4 0 0 0 4 4v14" stroke="#7aad4a" stroke-width="2" stroke-linecap="round"/>
                <path d="M13 10v6M17 10v6M21 10v6" stroke="#7aad4a" stroke-width="2" stroke-linecap="round"/>
                <path d="M31 10c0 0 4 3 4 9s-4 7-4 7v10" stroke="#c9a84c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h2>AyosCoffeeNegosyo</h2>
        <p class="subtitle">Create Account</p>
    </div>

    <div class="divider">
        <div class="divider-line"></div>
        <div class="divider-dot"></div>
        <div class="divider-line"></div>
    </div>

    <?php if (isset($_POST['register'])): ?>
        <div class="success-box">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            Registered successfully!
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="field">
            <label for="username">Username</label>
            <div class="input-wrap">
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <button type="button" class="toggle-pw" onclick="togglePassword()">
                    <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit" name="register">Register</button>

    </form>

    <p class="login-link">
        Already have an account? <a href="log-in.php">Go to Login</a>
    </p>

</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
            <line x1="1" y1="1" x2="23" y2="23"/>`;
    } else {
        input.type = 'password';
        icon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>`;
    }
}
</script>

</body>
</html>
