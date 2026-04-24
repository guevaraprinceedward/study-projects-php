<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['admin'])) { header("Location: admin-dashboard.php"); exit(); }

include 'admin-config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Punan ang lahat ng fields.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin'] = [
                    'id'       => $admin['id'],
                    'username' => $admin['username']
                ];
                header("Location: admin-dashboard.php");
                exit();
            } else {
                $error = 'Mali ang password.';
            }
        } else {
            $error = 'Admin account not found.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — AyosCoffeeNegosyo</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0e0f0c;--card:#161710;--border:#2a2c24;--green:#5a7a3a;--green-lt:#7aad4a;--gold:#c9a84c;--red:#c0392b;--text:#e8e6df;--muted:#7a7a6a;--input-bg:#1e201a}
body{min-height:100vh;background:var(--bg);display:flex;align-items:center;justify-content:center;font-family:'DM Sans',sans-serif;color:var(--text);padding:20px;overflow:hidden;position:relative}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 80% 60% at 20% 20%,rgba(192,57,43,0.06) 0%,transparent 60%),radial-gradient(ellipse 60% 80% at 80% 80%,rgba(201,168,76,0.06) 0%,transparent 60%);pointer-events:none}
body::after{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(255,255,255,0.015) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.015) 1px,transparent 1px);background-size:60px 60px;pointer-events:none}
.card{background:var(--card);border:1px solid var(--border);border-radius:4px;width:100%;max-width:400px;padding:48px 40px 40px;position:relative;z-index:1;animation:fadeUp 0.5s ease both}
.card::before{content:'';position:absolute;top:0;left:0;width:40px;height:40px;border-top:2px solid var(--red);border-left:2px solid var(--red);border-radius:3px 0 0 0}
.card::after{content:'';position:absolute;bottom:0;right:0;width:40px;height:40px;border-bottom:2px solid var(--red);border-right:2px solid var(--red);border-radius:0 0 3px 0}
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.logo-area{text-align:center;margin-bottom:32px}
.admin-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(192,57,43,0.1);border:1px solid rgba(192,57,43,0.3);border-radius:100px;padding:4px 14px;font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:#e05a5a;margin-bottom:14px}
h1{font-family:'Playfair Display',serif;font-size:26px;font-weight:700;color:var(--text)}
.subtitle{font-size:13px;color:var(--muted);margin-top:6px;letter-spacing:0.08em;text-transform:uppercase}
.divider{display:flex;align-items:center;gap:12px;margin-bottom:28px}
.divider-line{flex:1;height:1px;background:var(--border)}
.divider-dot{width:4px;height:4px;border-radius:50%;background:var(--red)}
.error-box{background:rgba(192,57,43,0.12);border:1px solid rgba(192,57,43,0.35);border-radius:4px;padding:12px 14px;margin-bottom:22px;font-size:13.5px;color:#e57370;display:flex;align-items:center;gap:10px}
.field{margin-bottom:18px}
label{display:block;font-size:11px;font-weight:500;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
.input-wrap{position:relative}
.input-wrap svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);width:16px;height:16px;pointer-events:none}
input[type="text"],input[type="password"]{width:100%;background:var(--input-bg);border:1px solid var(--border);border-radius:4px;padding:12px 14px 12px 42px;font-family:'DM Sans',sans-serif;font-size:14px;color:var(--text);outline:none;transition:border-color 0.2s,box-shadow 0.2s}
input:focus{border-color:#e05a5a;box-shadow:0 0 0 3px rgba(192,57,43,0.1)}
button[type="submit"]{width:100%;padding:13px;margin-top:10px;background:#8b2e2e;border:none;border-radius:4px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:500;letter-spacing:0.06em;color:#fff;cursor:pointer;transition:background 0.2s,transform 0.1s}
button[type="submit"]:hover{background:#a33535}
button[type="submit"]:active{transform:scale(0.98)}
.back-link{text-align:center;margin-top:22px;font-size:13px;color:var(--muted)}
.back-link a{color:var(--green-lt);text-decoration:none;font-weight:500;transition:color 0.2s}
.back-link a:hover{color:var(--gold)}
</style>
</head>
<body>
<div class="card">
    <div class="logo-area">
        <div class="admin-badge">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Admin Portal
        </div>
        <h1>AyosCoffeeNegosyo</h1>
        <p class="subtitle">Admin Login</p>
    </div>
    <div class="divider"><div class="divider-line"></div><div class="divider-dot"></div><div class="divider-line"></div></div>
    <?php if ($error): ?>
    <div class="error-box">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    <form method="POST">
        <div class="field">
            <label>Username</label>
            <div class="input-wrap">
                <input type="text" name="username" placeholder="Admin username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
        </div>
        <div class="field">
            <label>Password</label>
            <div class="input-wrap">
                <input type="password" name="password" placeholder="Admin password" required>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
        </div>
        <button type="submit">Login as Admin</button>
    </form>
    <p class="back-link"><a href="index.php">← Back to Menu</a></p>
</div>
</body>
</html>