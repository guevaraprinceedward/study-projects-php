<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ─── DATABASE CONNECTION ───────────────────────────────────────────────────
$conn = new mysqli("localhost", "root", "", "restaurant_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error  = "";
$debug  = false; // ← Palitan ng TRUE para makita ang column names ng users table

// ─── DEBUG MODE: SHOW TABLE COLUMNS ───────────────────────────────────────
if ($debug) {
    $result = $conn->query("DESCRIBE users");
    echo "<pre style='background:#111;color:lime;padding:20px;'>";
    echo "=== COLUMNS NG USERS TABLE ===\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    echo "</pre>";
}

// ─── HANDLE LOGIN ──────────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($username === "" || $password === "") {
        $error = "Punan ang lahat ng fields.";
    } else {

        $sql  = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("SQL Error: " . $conn->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            // ✅ AUTO-DETECT: Case-insensitive password column search
            $dbPassword = null;
            $foundCol   = "";

            // Convert all keys to lowercase map para ma-handle ang PASSWORD, Password, password, etc.
            $userLower = array_change_key_case($user, CASE_LOWER);

            $possibleCols = ["password", "pass", "passwd", "user_password", "pwd", "user_pass"];

            foreach ($possibleCols as $col) {
                if (array_key_exists($col, $userLower)) {
                    $dbPassword = $userLower[$col];
                    $foundCol   = $col;
                    break;
                }
            }

            if ($dbPassword === null) {
                // Wala sa listahan — ipakita lahat ng columns para malaman
                $cols = implode(", ", array_keys($user));
                die("❌ Hindi mahanap ang password column!<br>Available columns: <strong>$cols</strong><br>I-update ang \$possibleCols array sa code.");
            }

            // ✅ SUPPORT BOTH: Hashed (password_hash) at plain text
            $loginOk = false;

            if (password_get_info($dbPassword)['algo'] !== null) {
                // Hashed password
                $loginOk = password_verify($password, $dbPassword);
            } else {
                // Plain text password
                $loginOk = ($password === $dbPassword);
            }

            if ($loginOk) {
                $_SESSION["user"]     = $user["username"];
                $_SESSION["user_id"]  = $user["id"] ?? null;

                header("Location: index.php");
                exit();
            } else {
                $error = "Mali ang password. Subukan ulit.";
            }

        } else {
            $error = "Hindi mahanap ang user. Siguraduhing tama ang username.";
        }

        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="fil">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Restaurant</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg:       #0e0f0c;
            --card:     #161710;
            --border:   #2a2c24;
            --green:    #5a7a3a;
            --green-lt: #7aad4a;
            --gold:     #c9a84c;
            --text:     #e8e6df;
            --muted:    #7a7a6a;
            --error:    #c0392b;
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

        /* Background texture */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 20%, rgba(90,122,58,0.08) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 80% 80%, rgba(201,168,76,0.06) 0%, transparent 60%);
            pointer-events: none;
        }

        /* Decorative grid lines */
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

        /* Gold corner accent */
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

        .logo-area {
            text-align: center;
            margin-bottom: 36px;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon svg {
            width: 100%;
            height: 100%;
        }

        h1 {
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

        .divider-line {
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .divider-dot {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: var(--gold);
        }

        .error-box {
            background: rgba(192,57,43,0.12);
            border: 1px solid rgba(192,57,43,0.35);
            border-radius: 4px;
            padding: 12px 14px;
            margin-bottom: 22px;
            font-size: 13.5px;
            color: #e57370;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%,100% { transform: translateX(0); }
            25%      { transform: translateX(-6px); }
            75%      { transform: translateX(6px); }
        }

        .field {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            width: 16px;
            height: 16px;
            pointer-events: none;
            transition: color 0.2s;
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

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--green-lt);
            box-shadow: 0 0 0 3px rgba(122,173,74,0.12);
        }

        input[type="text"]:focus ~ svg,
        input[type="password"]:focus ~ svg {
            color: var(--green-lt);
        }

        /* Show password toggle */
        .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--muted);
            padding: 0;
            display: flex;
            align-items: center;
            transition: color 0.2s;
        }

        .toggle-pw:hover { color: var(--text); }

        button[type="submit"] {
            width: 100%;
            padding: 13px;
            margin-top: 10px;
            background: var(--green);
            border: none;
            border-radius: 4px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.06em;
            color: #fff;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            position: relative;
            overflow: hidden;
        }

        button[type="submit"]:hover  { background: var(--green-lt); }
        button[type="submit"]:active { transform: scale(0.98); }

        /* Shimmer on hover */
        button[type="submit"]::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
            transition: left 0.4s ease;
        }
        button[type="submit"]:hover::after { left: 160%; }

        .register-link {
            text-align: center;
            margin-top: 22px;
            font-size: 13px;
            color: var(--muted);
        }

        .register-link a {
            color: var(--green-lt);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .register-link a:hover { color: var(--gold); }
    </style>
</head>
<body>

<div class="card">

    <div class="logo-area">
        <div class="logo-icon">
            <!-- Fork & Knife SVG icon -->
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="24" cy="24" r="23" stroke="#c9a84c" stroke-width="1.5"/>
                <path d="M17 10v8a4 4 0 0 0 4 4v14" stroke="#7aad4a" stroke-width="2" stroke-linecap="round"/>
                <path d="M13 10v6M17 10v6M21 10v6" stroke="#7aad4a" stroke-width="2" stroke-linecap="round"/>
                <path d="M31 10c0 0 4 3 4 9s-4 7-4 7v10" stroke="#c9a84c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h1>AyosCoffeeNegosyo</h1>
        <p class="subtitle">Staff Portal</p>
    </div>

    <div class="divider">
        <div class="divider-line"></div>
        <div class="divider-dot"></div>
        <div class="divider-line"></div>
    </div>

    <?php if ($error !== ""): ?>
        <div class="error-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">

        <div class="field">
            <label for="username">Username</label>
            <div class="input-wrap">
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    required
                    autocomplete="username"
                >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password"
                >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <button type="button" class="toggle-pw" onclick="togglePassword()" title="Ipakita/itago ang password">
                    <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit">Login</button>

    </form>

    <p class="register-link">
        You don't have an account yet? <a href="register.php">Register</a>
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
