<?php
/**
 * includes/product-image.php — turns whatever is stored in products.image into a URL the browser can load.
 *
 * Handles: full URLs (https://…), protocol-relative (//cdn…), root paths (/uploads/x.jpg),
 * relative paths (assets/img/products/x.jpg), bare file names (x.jpg), Windows backslashes,
 * stray spaces/newlines, and spaces inside the file name.
 *
 * IMPORTANT: it never checks file_exists() — that always fails for http(s) URLs and is the usual
 * reason a correct image URL "disappears" from a card.
 *
 * Use inside render_product_card():
 *     require_once __DIR__ . '/includes/product-image.php';
 *     $src = product_image_src($p['image'] ?? '');
 *     echo product_image_html($p['name'], $p['image'] ?? '');
 */
const PRODUCT_IMAGE_DIR = 'assets/img/products/';   // used only for bare file names
const PRODUCT_PLACEHOLDER = "data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 120 120'%3E%3Crect width='120' height='120' fill='%23f5ecd2'/%3E%3Cpath d='M30 78h60M38 78c0-16 10-26 22-26s22 10 22 26M60 40v8' fill='none' stroke='%23b08d3c' stroke-width='3' stroke-linecap='round'/%3E%3C/svg%3E";

function product_image_src(?string $raw): string
{
    $raw = trim((string)$raw);
    $raw = str_replace(["\r", "\n", "\t", '\\'], ['', '', '', '/'], $raw);
    if ($raw === '') return PRODUCT_PLACEHOLDER;

    if (preg_match('~^(https?:)?//~i', $raw) || strncmp($raw, 'data:', 5) === 0) {
        return str_replace(' ', '%20', $raw);              // external / data URL: use as is
    }
    if ($raw[0] !== '/' && strpos($raw, '/') === false) {
        $raw = PRODUCT_IMAGE_DIR . $raw;                    // bare file name
    }
    return str_replace(' ', '%20', $raw);
}

/** The photo block for a product card. Pairs with .pv in premium.css / cops-menu-plus.css. */
function product_image_html(string $name, ?string $raw): string
{
    $src = product_image_src($raw);
    $none = $src === PRODUCT_PLACEHOLDER;
    return '<div class="pv ' . ($none ? 'no-photo' : 'has-photo') . '">'
        . '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" alt="' . htmlspecialchars($name, ENT_QUOTES) . '"'
        . ' loading="eager" decoding="async" referrerpolicy="no-referrer"'
        . ' data-fallback="' . htmlspecialchars(PRODUCT_PLACEHOLDER, ENT_QUOTES) . '">'
        . '</div>';
}