<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION["user"])) { header("Location: index.php"); exit(); }

include 'config.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm'] ?? '');

    if (empty($username) || empty($password) || empty($confirm)) {
        $error = 'Punan ang lahat ng fields.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $chk->bind_param("s", $username);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $error = 'Username already taken. Please choose another.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $ins->bind_param("ss", $username, $hashed);
            if ($ins->execute()) {
                $newId = $conn->insert_id;
                $_SESSION['user'] = [
                    'id'       => $newId,
                    'username' => $username,
                    'name'     => $username
                ];
                header("Location: index.php");
                exit();
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up — AyosCoffeeNegosyo</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0e0f0c;--card:#161710;--border:#2a2c24;--green:#5a7a3a;--green-lt:#7aad4a;--gold:#c9a84c;--text:#e8e6df;--muted:#7a7a6a;--input-bg:#1e201a;}
body{min-height:100vh;background:var(--bg);display:flex;align-items:center;justify-content:center;font-family:'DM Sans',sans-serif;color:var(--text);padding:20px;position:relative;overflow:hidden}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 80% 60% at 20% 20%,rgba(90,122,58,0.08) 0%,transparent 60%),radial-gradient(ellipse 60% 80% at 80% 80%,rgba(201,168,76,0.06) 0%,transparent 60%);pointer-events:none}
body::after{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(255,255,255,0.015) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.015) 1px,transparent 1px);background-size:60px 60px;pointer-events:none}
.card{background:var(--card);border:1px solid var(--border);border-radius:4px;width:100%;max-width:420px;padding:48px 40px 40px;position:relative;z-index:1;animation:fadeUp 0.5s ease both}
.card::before{content:'';position:absolute;top:0;left:0;width:40px;height:40px;border-top:2px solid var(--gold);border-left:2px solid var(--gold);border-radius:3px 0 0 0}
.card::after{content:'';position:absolute;bottom:0;right:0;width:40px;height:40px;border-bottom:2px solid var(--gold);border-right:2px solid var(--gold);border-radius:0 0 3px 0}
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.logo-area{text-align:center;margin-bottom:32px}
.logo-icon{width:48px;height:48px;margin:0 auto 14px;display:flex;align-items:center;justify-content:center}
.logo-icon svg{width:100%;height:100%}
h1{font-family:'Playfair Display',serif;font-size:26px;font-weight:700;color:var(--text);letter-spacing:0.01em}
.subtitle{font-size:13px;color:var(--muted);margin-top:6px;letter-spacing:0.08em;text-transform:uppercase}
.divider{display:flex;align-items:center;gap:12px;margin-bottom:28px}
.divider-line{flex:1;height:1px;background:var(--border)}
.divider-dot{width:4px;height:4px;border-radius:50%;background:var(--gold)}
.error-box{background:rgba(192,57,43,0.12);border:1px solid rgba(192,57,43,0.35);border-radius:4px;padding:12px 14px;margin-bottom:22px;font-size:13.5px;color:#e57370;display:flex;align-items:center;gap:10px;animation:shake 0.4s ease}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}
.field{margin-bottom:18px}
label{display:block;font-size:11px;font-weight:500;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
.input-wrap{position:relative}
.input-wrap svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);width:16px;height:16px;pointer-events:none}
input[type="text"],input[type="password"]{width:100%;background:var(--input-bg);border:1px solid var(--border);border-radius:4px;padding:12px 14px 12px 42px;font-family:'DM Sans',sans-serif;font-size:14px;color:var(--text);outline:none;transition:border-color 0.2s,box-shadow 0.2s}
input:focus{border-color:var(--green-lt);box-shadow:0 0 0 3px rgba(122,173,74,0.12)}
button[type="submit"]{width:100%;padding:13px;margin-top:10px;background:var(--green);border:none;border-radius:4px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:500;letter-spacing:0.06em;color:#fff;cursor:pointer;transition:background 0.2s,transform 0.1s}
button[type="submit"]:hover{background:var(--green-lt)}
button[type="submit"]:active{transform:scale(0.98)}
.login-link{text-align:center;margin-top:22px;font-size:13px;color:var(--muted)}
.login-link a{color:var(--green-lt);text-decoration:none;font-weight:500;transition:color 0.2s}
.login-link a:hover{color:var(--gold)}
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
        <h1>AyosCoffeeNegosyo</h1>
        <p class="subtitle">Create Account</p>
    </div>
    <div class="divider"><div class="divider-line"></div><div class="divider-dot"></div><div class="divider-line"></div></div>
    <?php if ($error): ?>
    <div class="error-box">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    <form method="POST">
        <div class="field">
            <label>Username</label>
            <div class="input-wrap">
                <input type="text" name="username" placeholder="Choose a username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
        </div>
        <div class="field">
            <label>Password</label>
            <div class="input-wrap">
                <input type="password" name="password" placeholder="At least 6 characters" required autocomplete="new-password">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
        </div>
        <div class="field">
            <label>Confirm Password</label>
            <div class="input-wrap">
                <input type="password" name="confirm" placeholder="Repeat your password" required autocomplete="new-password">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
        </div>
        <button type="submit">Create Account</button>
    </form>
    <p class="login-link">Already have an account? <a href="log-in.php">Login</a></p>
</div>
</body>
</html>