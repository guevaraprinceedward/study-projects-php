<?php
include_once 'hotel-config.php';
$activePage = '';
$errors = [];
$next = $_GET['next'] ?? $_POST['next'] ?? 'hotel-industry.php';

if ($isLoggedIn = isHotelLoggedIn()) {
    header("Location: $next"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($fullName === '') $errors[] = "Full name is required.";
    if (!preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $username)) $errors[] = "Username must be 3-50 characters (letters, numbers, dot, underscore).";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $chk = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $chk->bind_param("ss", $username, $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = "That username or email is already registered. Try logging in instead.";
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, phone, tier) VALUES (?, ?, ?, ?, ?, 'regular')");
        $stmt->bind_param("sssss", $username, $email, $hash, $fullName, $phone);
        if ($stmt->execute()) {
            $_SESSION['user'] = [
                'id' => $stmt->insert_id, 'name' => $fullName, 'username' => $username,
                'email' => $email, 'tier' => 'regular',
            ];
            header("Location: $next"); exit;
        } else {
            $errors[] = "Something went wrong creating your account. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create an Account — Nocturne Manila Bay</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="hotel-theme.css">
<style>
/* PAGE-SPECIFIC: register (split-screen auth) */
body{overflow-x:hidden}
.auth-split{position:relative;z-index:1;min-height:100vh;display:grid;grid-template-columns:1fr 1fr}
.auth-visual{position:relative;overflow:hidden;background:#000}
.auth-visual img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0;opacity:0.75}
.auth-visual::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(7,7,10,0.2) 0%,rgba(7,7,10,0.55) 60%,rgba(7,7,10,0.92) 100%)}
.auth-visual-content{position:relative;z-index:1;height:100%;display:flex;flex-direction:column;justify-content:flex-end;padding:60px}
.auth-visual-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:11px;letter-spacing:0.28em;text-transform:uppercase;color:var(--h-gold);margin-bottom:18px}
.auth-visual-eyebrow::before{content:'';width:28px;height:1px;background:var(--h-gold-dim)}
.auth-visual h2{font-family:'Cormorant Garamond',serif;font-size:clamp(32px,3vw,46px);font-weight:600;color:var(--h-champagne);line-height:1.15;margin-bottom:16px}
.auth-visual h2 em{font-style:italic;color:var(--h-gold)}
.auth-visual p{font-size:13.5px;color:var(--h-muted);line-height:1.8;max-width:380px;font-weight:300}
.auth-visual-perks{display:flex;flex-direction:column;gap:12px;margin-top:28px}
.auth-visual-perks div{display:flex;align-items:center;gap:10px;font-size:12.5px;color:var(--h-text)}
.auth-visual-perks svg{flex-shrink:0;color:var(--h-gold)}

.auth-side{display:flex;align-items:center;justify-content:center;padding:60px 40px;position:relative}
.auth-side-inner{width:100%;max-width:420px}
.auth-brand-mini{display:flex;align-items:center;gap:10px;margin-bottom:44px}
.auth-brand-mini .mark{width:32px;height:32px;border:1px solid var(--h-gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.auth-brand-mini span{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--h-champagne);letter-spacing:0.08em}

.auth-side h1{font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:600;color:var(--h-champagne);margin-bottom:8px}
.auth-side .auth-sub{font-size:13px;color:var(--h-muted);line-height:1.7;margin-bottom:32px;max-width:400px}

.auth-form{display:flex;flex-direction:column;gap:18px}
.auth-form .field-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.auth-form label{display:block;font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--h-gold-dim);margin-bottom:8px}
.auth-form input{width:100%;background:rgba(255,255,255,0.02);border:1px solid var(--h-line);border-radius:3px;color:var(--h-champagne);font-size:14px;padding:13px 14px;outline:none;transition:all 0.2s}
.auth-form input:focus{border-color:var(--h-gold);background:rgba(207,167,107,0.03)}
.auth-form input::placeholder{color:#4a4a52}
.auth-form small{display:block;margin-top:6px;font-size:10.5px;color:var(--h-muted)}
.auth-submit{width:100%;padding:15px 26px;border:1px solid var(--h-gold-dim);border-radius:2px;background:var(--h-gold);color:#0a0a0c;font-size:12px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;cursor:pointer;transition:all 0.2s;margin-top:8px}
.auth-submit:hover{background:#e3c58c}

.auth-error{background:rgba(192,87,74,0.08);border:1px solid rgba(192,87,74,0.3);color:#f0c9c2;padding:13px 16px;border-radius:3px;font-size:12.5px;display:flex;flex-direction:column;gap:4px}
.auth-switch{text-align:center;margin-top:26px;font-size:12.5px;color:var(--h-muted)}
.auth-switch a{color:var(--h-gold);text-decoration:none;border-bottom:1px solid var(--h-gold-dim);padding-bottom:1px}
.auth-switch a:hover{color:var(--h-champagne)}

.auth-tier-note{display:flex;gap:10px;align-items:flex-start;background:rgba(207,167,107,0.05);border:1px solid rgba(207,167,107,0.15);border-radius:3px;padding:12px 14px;margin-bottom:28px;font-size:11.5px;color:var(--h-muted);line-height:1.6}
.auth-tier-note svg{flex-shrink:0;color:var(--h-gold);margin-top:1px}

@media(max-width:900px){
    .auth-split{grid-template-columns:1fr}
    .auth-visual{min-height:280px}
    .auth-side{padding:50px 24px}
}
@media(max-width:480px){
    .auth-form .field-row{grid-template-columns:1fr}
}
</style>
</head>
<body>

<?php include_once 'sidebar.php'; ?>
<?php include_once 'topheader.php'; ?>

<div class="auth-split">
    <div class="auth-visual">
        <img src="https://images.unsplash.com/photo-1590490360182-c33d57733427?w=1200" alt="Nocturne Manila Bay">
        <div class="auth-visual-content">
            <div class="auth-visual-eyebrow">Nocturne Manila Bay</div>
            <h2>A Hôtel of<br><em>Uncommon Hours</em></h2>
            <p>Create your account to unlock reservations, spa &amp; skincare orders, and entertainment bookings across all three residences.</p>
            <div class="auth-visual-perks">
                <div>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 6L9 17l-5-5"/></svg>
                    Book any of the three residences instantly
                </div>
                <div>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 6L9 17l-5-5"/></svg>
                    Earn toward Nocturne Noir — our VIP tier
                </div>
                <div>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 6L9 17l-5-5"/></svg>
                    Order spa, skincare &amp; entertainment services
                </div>
            </div>
        </div>
    </div>

    <div class="auth-side">
        <div class="auth-side-inner">
            <div class="auth-brand-mini">
                <div class="mark">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#cfa76b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
                </div>
                <span>Nocturne</span>
            </div>

            <h1>Create Your Account</h1>
            <div class="auth-tier-note">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2l2.4 7.2H22l-6 4.6 2.3 7.2L12 16.4 5.7 21l2.3-7.2-6-4.6h7.6z"/></svg>
                <span>Registration is required to reserve a residence or order spa, skincare, and entertainment services — this is how we apply your Regular or Nocturne Noir (VIP) rate.</span>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="auth-error" style="margin-bottom:20px">
                <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="register.php?next=<?= urlencode($next) ?>">
                <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

                <div class="field">
                    <label>Full Name</label>
                    <input type="text" name="full_name" placeholder="Juan Dela Cruz" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                </div>

                <div class="field">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="juan.delacruz" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    <small>3-50 characters — letters, numbers, dot, underscore only.</small>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="field">
                        <label>Mobile Number</label>
                        <input type="tel" name="phone" placeholder="09XXXXXXXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="••••••••" minlength="8" required>
                    </div>
                    <div class="field">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" placeholder="••••••••" minlength="8" required>
                    </div>
                </div>

                <button type="submit" class="auth-submit">Create Account</button>
            </form>

            <div class="auth-switch">Already have an account? <a href="hotel-login.php?next=<?= urlencode($next) ?>">Log in here</a></div>
        </div>
    </div>
</div>

</body>
</html>