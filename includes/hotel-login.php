<?php
include_once 'hotel-config.php';
$activePage = '';
$errors = [];
$next = $_GET['next'] ?? $_POST['next'] ?? 'hotel-industry.php';

if (isHotelLoggedIn()) {
    header("Location: $next"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? ''); // username or email
    $password   = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $errors[] = "Please enter both your username/email and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, email, password_hash, full_name, tier FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = "Incorrect username/email or password.";
        } else {
            $_SESSION['user'] = [
                'id' => $user['id'], 'name' => $user['full_name'], 'username' => $user['username'],
                'email' => $user['email'], 'tier' => $user['tier'],
            ];
            header("Location: $next"); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log In — Nocturne Manila Bay</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="hotel-theme.css">
<style>
/* PAGE-SPECIFIC: login (split-screen auth, mirrored from register) */
body{overflow-x:hidden}
.auth-split{position:relative;z-index:1;min-height:100vh;display:grid;grid-template-columns:1fr 1fr}
.auth-visual{position:relative;overflow:hidden;background:#000;order:2}
.auth-visual img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0;opacity:0.75}
.auth-visual::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(7,7,10,0.2) 0%,rgba(7,7,10,0.55) 60%,rgba(7,7,10,0.92) 100%)}
.auth-visual-content{position:relative;z-index:1;height:100%;display:flex;flex-direction:column;justify-content:flex-end;padding:60px}
.auth-visual-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:11px;letter-spacing:0.28em;text-transform:uppercase;color:var(--h-gold);margin-bottom:18px}
.auth-visual-eyebrow::before{content:'';width:28px;height:1px;background:var(--h-gold-dim)}
.auth-visual h2{font-family:'Cormorant Garamond',serif;font-size:clamp(32px,3vw,46px);font-weight:600;color:var(--h-champagne);line-height:1.15;margin-bottom:16px}
.auth-visual h2 em{font-style:italic;color:var(--h-gold)}
.auth-visual p{font-size:13.5px;color:var(--h-muted);line-height:1.8;max-width:380px;font-weight:300}
.auth-visual-quote{margin-top:30px;padding-top:26px;border-top:1px solid rgba(207,167,107,0.2)}
.auth-visual-quote blockquote{font-family:'Cormorant Garamond',serif;font-style:italic;font-size:18px;color:var(--h-champagne);line-height:1.5;max-width:380px}
.auth-visual-quote cite{display:block;margin-top:10px;font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:var(--h-gold-dim);font-style:normal}

.auth-side{order:1;display:flex;align-items:center;justify-content:center;padding:60px 40px;position:relative}
.auth-side-inner{width:100%;max-width:400px}
.auth-brand-mini{display:flex;align-items:center;gap:10px;margin-bottom:44px}
.auth-brand-mini .mark{width:32px;height:32px;border:1px solid var(--h-gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.auth-brand-mini span{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--h-champagne);letter-spacing:0.08em}

.auth-side h1{font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:600;color:var(--h-champagne);margin-bottom:8px}
.auth-side .auth-sub{font-size:13px;color:var(--h-muted);line-height:1.7;margin-bottom:36px;max-width:380px}

.auth-form{display:flex;flex-direction:column;gap:18px}
.auth-form label{display:block;font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--h-gold-dim);margin-bottom:8px}
.auth-form input{width:100%;background:rgba(255,255,255,0.02);border:1px solid var(--h-line);border-radius:3px;color:var(--h-champagne);font-size:14px;padding:13px 14px;outline:none;transition:all 0.2s}
.auth-form input:focus{border-color:var(--h-gold);background:rgba(207,167,107,0.03)}
.auth-form input::placeholder{color:#4a4a52}
.auth-row-between{display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--h-muted);margin-top:-4px}
.auth-remember{display:flex;align-items:center;gap:8px}
.auth-remember input{width:auto}
.auth-forgot{color:var(--h-gold-dim);text-decoration:none}
.auth-forgot:hover{color:var(--h-gold)}
.auth-submit{width:100%;padding:15px 26px;border:1px solid var(--h-gold-dim);border-radius:2px;background:var(--h-gold);color:#0a0a0c;font-size:12px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;cursor:pointer;transition:all 0.2s;margin-top:8px}
.auth-submit:hover{background:#e3c58c}

.auth-error{background:rgba(192,87,74,0.08);border:1px solid rgba(192,87,74,0.3);color:#f0c9c2;padding:13px 16px;border-radius:3px;font-size:12.5px}
.auth-switch{text-align:center;margin-top:26px;font-size:12.5px;color:var(--h-muted)}
.auth-switch a{color:var(--h-gold);text-decoration:none;border-bottom:1px solid var(--h-gold-dim);padding-bottom:1px}
.auth-switch a:hover{color:var(--h-champagne)}

.auth-divider{display:flex;align-items:center;gap:14px;margin:28px 0;font-size:10.5px;letter-spacing:0.14em;text-transform:uppercase;color:var(--h-muted)}
.auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;background:var(--h-line)}
.tier-mini{display:flex;gap:10px;margin-top:26px}
.tier-mini div{flex:1;border:1px solid var(--h-line);border-radius:3px;padding:12px 14px;font-size:11px;color:var(--h-muted);text-align:center}
.tier-mini div strong{display:block;font-family:'Cormorant Garamond',serif;font-size:14px;color:var(--h-champagne);margin-bottom:2px;font-weight:600}
.tier-mini div.vip strong{color:var(--h-vip)}

@media(max-width:900px){
    .auth-split{grid-template-columns:1fr}
    .auth-visual{order:1;min-height:240px}
    .auth-side{order:2;padding:50px 24px}
}
</style>
</head>
<body>

<?php include_once 'sidebar.php'; ?>
<?php include_once 'topheader.php'; ?>

<div class="auth-split">
    <div class="auth-side">
        <div class="auth-side-inner">
            <div class="auth-brand-mini">
                <div class="mark">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#cfa76b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
                </div>
                <span>Nocturne</span>
            </div>

            <h1>Welcome Back</h1>
            <p class="auth-sub">Log in to reserve a residence, book the spa, or order from the skincare shop and entertainment lounge.</p>

            <?php if (!empty($errors)): ?>
            <div class="auth-error" style="margin-bottom:20px">
                <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="hotel-login.php?next=<?= urlencode($next) ?>">
                <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

                <div class="field">
                    <label>Username or Email</label>
                    <input type="text" name="identifier" placeholder="juan.delacruz or you@example.com" value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>" required autofocus>
                </div>

                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>

                <div class="auth-row-between">
                    <label class="auth-remember"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
                    <a href="#" class="auth-forgot">Forgot password?</a>
                </div>

                <button type="submit" class="auth-submit">Log In</button>
            </form>

            <div class="auth-switch">New to Nocturne? <a href="register.php?next=<?= urlencode($next) ?>">Create an account</a></div>

            <div class="tier-mini">
                <div><strong>Regular</strong>Standard nightly rates</div>
                <div class="vip"><strong>Nocturne Noir</strong>Up to 20% off, VIP perks</div>
            </div>
        </div>
    </div>

    <div class="auth-visual">
        <img src="https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=1200" alt="Nocturne Manila Bay">
        <div class="auth-visual-content">
            <div class="auth-visual-eyebrow">Nocturne Manila Bay</div>
            <h2>The House<br>Remembers <em>You</em></h2>
            <p>Every stay, every order, every hour you've kept with us — carried forward the moment you sign in.</p>
            <div class="auth-visual-quote">
                <blockquote>We didn't stay at Nocturne so much as we were kept — the way a good house keeps time.</blockquote>
                <cite>Condé Nast Traveler, Fictional Review</cite>
            </div>
        </div>
    </div>
</div>

</body>
</html>