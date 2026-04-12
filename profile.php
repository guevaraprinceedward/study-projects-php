<?php include 'config.php'; ?>
<?php include 'header.php'; ?>
<?php
if (!isset($_SESSION['user'])) {
    header("Location: log-in.php");
    exit();
}

$username      = $_SESSION['user'];
$avatar_letter = strtoupper(mb_substr($username, 0, 1));

// Get member since (only if created_at column exists)
$joined = '';
$stmt = $conn->prepare("SELECT created_at FROM users WHERE username = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['created_at'])) {
            $joined = date('F j, Y', strtotime($row['created_at']));
        }
    }
    $stmt->close();
}
?>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

<style>
    :root {
        --bg:       #0e0f0c;
        --card:     #161710;
        --border:   #2a2c24;
        --green:    #5a7a3a;
        --green-lt: #7aad4a;
        --gold:     #c9a84c;
        --gold-dim: #8a6f2e;
        --text:     #e8e6df;
        --muted:    #7a7a6a;
        --surface:  #1e201a;
    }

    /* ── Page base ── */
    body {
        background: var(--bg) !important;
        color: var(--text) !important;
        font-family: 'DM Sans', sans-serif !important;
        min-height: 100vh;
        position: relative;
    }

    body::before {
        content: '';
        position: fixed;
        inset: 0;
        background:
            radial-gradient(ellipse 80% 60% at 20% 20%, rgba(90,122,58,0.08) 0%, transparent 60%),
            radial-gradient(ellipse 60% 80% at 80% 80%, rgba(201,168,76,0.06) 0%, transparent 60%);
        pointer-events: none;
        z-index: 0;
    }

    /* ── Override header.php styles ── */
    header {
        background: rgba(14,15,12,0.92) !important;
        backdrop-filter: blur(18px) !important;
        border-bottom: 1px solid var(--border) !important;
        border-top: none !important;
        padding: 0 32px !important;
        height: 64px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        position: sticky !important;
        top: 0 !important;
        z-index: 100 !important;
        margin: 0 !important;
    }

    header h2 {
        font-family: 'Playfair Display', serif !important;
        font-size: 20px !important;
        font-weight: 700 !important;
        color: var(--text) !important;
        letter-spacing: 0.02em !important;
    }

    header nav {
        display: flex !important;
        gap: 4px !important;
    }

    header nav a {
        font-family: 'DM Sans', sans-serif !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        letter-spacing: 0.1em !important;
        text-transform: uppercase !important;
        color: var(--muted) !important;
        text-decoration: none !important;
        padding: 8px 14px !important;
        border-radius: 3px !important;
        transition: color 0.2s, background 0.2s !important;
    }

    header nav a:hover {
        color: var(--text) !important;
        background: rgba(255,255,255,0.04) !important;
    }

    header nav a[href="profile.php"] {
        color: var(--gold) !important;
    }

    /* ── Hide the <hr> from header.php ── */
    body > hr:first-of-type { display: none !important; }

    /* ── Override footer.php styles ── */
    footer {
        border-top: 1px solid var(--border) !important;
        padding: 24px 32px !important;
        text-align: center !important;
        background: transparent !important;
        position: relative;
        z-index: 1;
    }

    footer p {
        font-size: 12px !important;
        color: var(--muted) !important;
        letter-spacing: 0.06em !important;
    }

    /* Hide the <hr> before footer */
    footer + * { display: none; }
    hr { display: none !important; }

    /* ── Profile page layout ── */
    .profile-wrap {
        position: relative;
        z-index: 1;
        max-width: 680px;
        margin: 60px auto 80px;
        padding: 0 24px;
        animation: fadeUp 0.5s ease both;
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Profile card ── */
    .profile-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 4px;
        overflow: hidden;
    }

    /* Gold corner accents — same as login */
    .profile-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 40px; height: 40px;
        border-top: 2px solid var(--gold);
        border-left: 2px solid var(--gold);
        border-radius: 3px 0 0 0;
        pointer-events: none;
    }

    .profile-card::after {
        content: '';
        position: absolute;
        bottom: 0; right: 0;
        width: 40px; height: 40px;
        border-bottom: 2px solid var(--gold);
        border-right: 2px solid var(--gold);
        border-radius: 0 0 3px 0;
        pointer-events: none;
    }

    /* Top color band */
    .card-band {
        height: 80px;
        background: linear-gradient(135deg, rgba(201,168,76,0.1) 0%, rgba(90,122,58,0.08) 100%);
        border-bottom: 1px solid var(--border);
        position: relative;
    }

    /* Avatar */
    .avatar {
        position: absolute;
        bottom: -32px;
        left: 32px;
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: var(--surface);
        border: 3px solid var(--card);
        outline: 1px solid var(--gold-dim);
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Playfair Display', serif;
        font-size: 26px;
        font-weight: 700;
        color: var(--gold);
    }

    /* Card body */
    .card-body {
        padding: 48px 32px 32px;
    }

    .profile-name {
        font-family: 'Playfair Display', serif;
        font-size: 30px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 4px;
    }

    .profile-handle {
        font-size: 13px;
        color: var(--muted);
        font-weight: 300;
        margin-bottom: 28px;
    }

    /* Info grid */
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 28px;
    }

    .info-cell {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 3px;
        padding: 14px 16px;
    }

    .info-label {
        font-size: 10px;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: 5px;
    }

    .info-value {
        font-size: 14px;
        color: var(--text);
    }

    .info-value.gold { color: var(--gold); font-weight: 500; }
    .info-value.green { color: var(--green-lt); }

    /* Divider */
    .section-divider {
        height: 1px;
        background: var(--border);
        margin-bottom: 24px;
    }

    .section-label {
        font-size: 10px;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: 14px;
    }

    /* Action buttons */
    .actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 10px 20px;
        border-radius: 3px;
        font-family: 'DM Sans', sans-serif;
        font-size: 12px;
        font-weight: 500;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-primary {
        background: var(--green);
        color: #fff;
    }

    .btn-primary:hover { background: var(--green-lt); }

    .btn-outline {
        background: transparent;
        border: 1px solid var(--border);
        color: var(--muted);
    }

    .btn-outline:hover {
        border-color: var(--gold-dim);
        color: var(--text);
    }

    .btn-danger {
        background: transparent;
        border: 1px solid rgba(192,57,43,0.35);
        color: #e57370;
    }

    .btn-danger:hover {
        background: rgba(192,57,43,0.1);
        border-color: rgba(192,57,43,0.6);
    }

    @media (max-width: 520px) {
        .profile-wrap { margin: 40px auto 60px; padding: 0 16px; }
        .card-body { padding: 44px 20px 24px; }
        .info-grid { grid-template-columns: 1fr; }
        .avatar { left: 20px; }
    }
</style>

<div class="profile-wrap">
    <div class="profile-card" style="position:relative;">

        <div class="card-band">
            <div class="avatar"><?= htmlspecialchars($avatar_letter) ?></div>
        </div>

        <div class="card-body">

            <h1 class="profile-name">Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?> 👤</h1>
            <p class="profile-handle">@<?= htmlspecialchars(strtolower($username)) ?> · Member</p>

            <div class="info-grid">
                <div class="info-cell">
                    <div class="info-label">Username</div>
                    <div class="info-value gold"><?= htmlspecialchars($username) ?></div>
                </div>
                <div class="info-cell">
                    <div class="info-label">Account Status</div>
                    <div class="info-value green">Active</div>
                </div>
                <?php if ($joined): ?>
                <div class="info-cell">
                    <div class="info-label">Member Since</div>
                    <div class="info-value"><?= htmlspecialchars($joined) ?></div>
                </div>
                <?php endif; ?>
                <div class="info-cell">
                    <div class="info-label">Role</div>
                    <div class="info-value">Customer</div>
                </div>
            </div>

            <p style="display:none;">This is your profile page.</p>

            <div class="section-divider"></div>
            <div class="section-label">Quick Actions</div>

            <div class="actions">
                <a href="index.php" class="btn btn-primary">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    Home
                </a>
                <a href="cart.php" class="btn btn-outline">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    View Cart
                </a>
                <a href="log-out.php" class="btn btn-danger">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Logout
                </a>
            </div>

        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
