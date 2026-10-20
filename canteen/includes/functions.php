<?php
/**
 * includes/functions.php — shared helpers (no database access in here).
 */

/** An error whose message is safe to show to the user. Anything else is logged and hidden. */
class UserFacingError extends RuntimeException {}

// ── Output + formatting ───────────────────────────────────────────────────
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money($amount): string
{
    return '₱' . number_format((float)$amount, 2);
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// ── Logging (never shown to users) ────────────────────────────────────────
function app_log(string $message): void
{
    $dir = __DIR__ . '/../storage/logs';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    @file_put_contents($dir . '/app.log', '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// ── Request helpers ───────────────────────────────────────────────────────
function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

function json_out(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

// ── CSRF ──────────────────────────────────────────────────────────────────
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(?string $token): bool
{
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
}

// ── Flash messages (survive one redirect; used when JavaScript is off) ─────
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ── Safe redirect targets after login ─────────────────────────────────────
function safe_next(?string $next, string $default = 'index.php'): string
{
    $allowed = ['menu.php', 'menu.php?view=checkout', 'menu.php?view=orders', 'menu.php?view=cart', 'index.php'];
    return in_array($next, $allowed, true) ? $next : $default;
}

// ── Product pictures ──────────────────────────────────────────────────────
/**
 * Turns whatever is saved in products.image into a URL the browser can open, and checks the file really exists.
 *
 * It accepts: full web addresses, "admin/products/photo.png", "/admin/products/photo.png", Windows-style
 * "admin\products\photo.png", a bare file name, or a path with the wrong letter case. If the file is not at the
 * saved path, the usual upload folders are searched for the same file name. Returns '' when nothing is found,
 * so the card shows its gold placeholder instead of a broken picture.
 */
function product_image_url(?string $raw): string
{
    static $cache = [];
    $raw = trim((string)$raw);
    if ($raw === '') return '';
    if (isset($cache[$raw])) return $cache[$raw];

    if (preg_match('~^(https?:)?//~i', $raw) || strncasecmp($raw, 'data:image/', 11) === 0) {
        return $cache[$raw] = $raw;                                  // a picture hosted somewhere else
    }

    $root = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
    $rel  = ltrim(preg_replace('~^(\./|\.\./)+~', '', str_replace('\\', '/', $raw)), '/');
    $rel  = preg_replace('~[?#].*$~', '', $rel);
    $base = basename($rel);

    $dirs = ['', 'admin/products/', 'admin/uploads/products/', 'admin/uploads/', 'assets/img/products/', 'assets/images/products/',
             'uploads/products/', 'uploads/', 'products/', 'images/products/', 'images/', 'img/products/'];
    $candidates = [$rel];
    foreach ($dirs as $d) $candidates[] = $d . $base;

    $found = null;
    foreach (array_unique($candidates) as $c) {
        if ($c !== '' && is_file($root . '/' . $c)) { $found = $c; break; }
    }
    if ($found === null) {                                           // same name, different letter case (Linux hosting)
        foreach (array_unique(array_map(fn($d) => rtrim($d, '/'), array_merge([dirname($rel) === '.' ? '' : dirname($rel)], $dirs))) as $d) {
            $dir = $root . ($d !== '' ? '/' . $d : '');
            if (!is_dir($dir)) continue;
            foreach (@scandir($dir) ?: [] as $f) {
                if (strcasecmp($f, $base) === 0 && is_file($dir . '/' . $f)) { $found = ($d !== '' ? $d . '/' : '') . $f; break 2; }
            }
        }
    }
    if ($found === null) {
        app_log('product image not found on disk: ' . $raw);
        return $cache[$raw] = '';
    }
    $url = implode('/', array_map('rawurlencode', explode('/', $found)));
    return $cache[$raw] = $url . '?v=' . (int)@filemtime($root . '/' . $found);
}

// ── SVG: icons (line style, inherit currentColor) ─────────────────────────
function icon(string $name, int $size = 20, string $class = ''): string
{
    static $p = [
        'menu'    => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close'   => '<path d="M6 6l12 12M18 6L6 18"/>',
        'cart'    => '<path d="M6 7h13l-1.4 8.2a2 2 0 0 1-2 1.6H9.4a2 2 0 0 1-2-1.6L5.5 4H3"/><circle cx="10" cy="20" r="1"/><circle cx="17" cy="20" r="1"/>',
        'search'  => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check'   => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'alert'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v5M12 16.5h.01"/>',
        'info'    => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/>',
        'logout'  => '<path d="M10 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h5M15 8l4 4-4 4M19 12H9"/>',
        'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/>',
        'plus'    => '<path d="M12 5v14M5 12h14"/>',
        'minus'   => '<path d="M5 12h14"/>',
        'trash'   => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
        'bell'    => '<path d="M6 9a6 6 0 0 1 12 0c0 6 2 7 2 7H4s2-1 2-7"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'refresh' => '<path d="M20 11a8 8 0 0 0-14-4L4 9M4 4v5h5M4 13a8 8 0 0 0 14 4l2-2M20 20v-5h-5"/>',
        'calendar'=> '<rect x="4" y="5" width="16" height="15" rx="1"/><path d="M4 10h16M9 3v4M15 3v4"/>',
        'chevron' => '<path d="m8 10 4 4 4-4"/>',
        'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'book'    => '<path d="M5 4h11a3 3 0 0 1 3 3v13H8a3 3 0 0 1-3-3z"/><path d="M5 17a3 3 0 0 1 3-3h11"/>',
        'bag'     => '<path d="M5 8h14l-1 12H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
        'shield'  => '<path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'dash'    => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'list'    => '<path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/>',
        'rice_meals' => '<path d="M3 11h18a9 9 0 0 1-18 0z"/><path d="M8 7c0-1.5 1-1.5 1-3M12 7c0-1.5 1-1.5 1-3M16 7c0-1.5 1-1.5 1-3"/>',
        'snacks'  => '<path d="M4 10a8 5 0 0 1 16 0z"/><path d="M3 14h18"/><path d="M4 18a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1H4z"/>',
        'drinks'  => '<path d="M6 8h12l-1.5 12h-9z"/><path d="M12 8V3l4-1"/>',
        'desserts'=> '<path d="M8 11l4 11 4-11"/><path d="M7 11a5 5 0 1 1 10 0z"/>',
        'plate'   => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/>',
    ];
    $body = $p[$name] ?? $p['plate'];
    return '<svg class="icon ' . e($class) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
}

/** A laurel wreath ornament, generated as plain SVG (no JavaScript). */
function laurel(int $size = 34): string
{
    $R = 15.0; $rx = $R * .23; $ry = $R * .095; $out = '';
    foreach ([-1, 1] as $s) {
        $d = '';
        for ($i = 0; $i <= 20; $i++) {
            $th = deg2rad(16 + (152 - 16) * $i / 20);
            $d .= ($i ? 'L' : 'M') . round($s * $R * sin($th), 2) . ' ' . round($R * cos($th), 2);
        }
        $out .= '<path d="' . $d . '" fill="none" stroke="#a88532" stroke-width=".7" stroke-linecap="round"/>';
        for ($j = 0; $j < 6; $j++) {
            $th = deg2rad(22 + (150 - 22) * $j / 5);
            $px = $s * $R * sin($th); $py = $R * cos($th);
            $tan = rad2deg(atan2(-sin($th), $s * cos($th)));
            foreach ([1, -1] as $o) {
                $cx = $px + $s * sin($th) * $o * $R * .1; $cy = $py + cos($th) * $o * $R * .1;
                $out .= '<ellipse cx="' . round($cx, 2) . '" cy="' . round($cy, 2) . '" rx="' . round($rx, 2) . '" ry="' . round($ry, 2)
                      . '" transform="rotate(' . round($tan + $o * $s * 34, 1) . ' ' . round($cx, 2) . ' ' . round($cy, 2) . ')" fill="' . ($o === 1 ? '#c4a24d' : '#d9bf7a') . '"/>';
            }
        }
    }
    return '<svg class="laurel" width="' . $size . '" height="' . $size . '" viewBox="-17 -17 34 34" aria-hidden="true" focusable="false">' . $out . '</svg>';
}

/**
 * Product picture, or an elegant gold placeholder. $extra (e.g. a badge) is placed inside the picture box.
 * The saved path is checked with product_image_url(), so a wrong or moved file never shows a broken image.
 */
function product_visual(array $p, string $class = '', string $extra = ''): string
{
    $name = $p['name'] ?? '';
    $src  = product_image_url($p['image'] ?? '');
    $fallback = '<span class="pv-fallback" aria-hidden="true">' . icon($p['category'] ?? 'plate', 44) . '<span>' . e(mb_strtoupper(mb_substr($name, 0, 1))) . '</span></span>';
    $html = '<div class="pv ' . e($class) . '">' . $fallback;
    if ($src !== '') {
        $html .= '<img class="pv-img" src="' . e($src) . '" alt="' . e($name) . '" decoding="async" onerror="this.onerror=null;this.remove()">';
    }
    return $html . $extra . '</div>';
}

function relative_time(int $ts): string
{
    $d = max(0, time() - $ts);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . ' min ago';
    if ($d < 86400) return floor($d / 3600) . ' hr ago';
    return floor($d / 86400) . ' d ago';
}