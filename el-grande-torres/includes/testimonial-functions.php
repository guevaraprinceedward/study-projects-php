<?php
/**
 * Helpers for the about_testimonials table — powers the two-row
 * scrolling marquee on about.php.
 */

/**
 * Returns ['row1' => [...], 'row2' => [...]] of active testimonials,
 * ordered by display_order. Falls back to a small built-in set if the
 * table doesn't exist yet (e.g. before database/about_testimonials.sql
 * has been imported), so the page never breaks.
 *
 * Each row now also carries `avatar_image` (nullable) — a path/URL to
 * the client's photo. Leave it NULL/empty and the card falls back to
 * the gold initials monogram automatically.
 */
function get_about_testimonials(): array {
    $fallback = [
        'row1' => [
            ['client_name' => 'Isabella Marasigan', 'client_branch' => 'Makati City', 'rating' => 5, 'quote_text' => 'Every piece feels considered — from the packaging to the stitching. The Atelier Noir does not rush anything, and you can feel it the moment you open the box.', 'avatar_initial' => 'IM', 'avatar_image' => null],
            ['client_name' => 'Rafael Domingo', 'client_branch' => 'Quezon City', 'rating' => 5, 'quote_text' => 'Ordered the Atelier Bomber for a formal event and I have not stopped wearing it since. The fit alone justifies the price.', 'avatar_initial' => 'RD', 'avatar_image' => null],
            ['client_name' => 'Camille Suarez', 'client_branch' => 'Cebu City', 'rating' => 5, 'quote_text' => 'Their site made shopping genuinely enjoyable — clean layout, fast checkout, and the product photos matched the real thing exactly.', 'avatar_initial' => 'CS', 'avatar_image' => null],
        ],
        'row2' => [
            ['client_name' => 'Diego Reyes', 'client_branch' => 'Iloilo City', 'rating' => 5, 'quote_text' => 'Limited edition drops are no joke — copped the House Crest Bomber and it sold out within days. Glad I did not hesitate.', 'avatar_initial' => 'DR', 'avatar_image' => null],
            ['client_name' => 'Samantha Cruz', 'client_branch' => 'Baguio City', 'rating' => 5, 'quote_text' => 'The wool coat kept me warm through the entire Baguio trip and still looked sharp in every photo. Worth the investment.', 'avatar_initial' => 'SC', 'avatar_image' => null],
            ['client_name' => 'Gabriel Tantoco', 'client_branch' => 'Antipolo City', 'rating' => 5, 'quote_text' => 'The automatic watch I ordered is a masterpiece. Feels like something you pass down, not just wear.', 'avatar_initial' => 'GT', 'avatar_image' => null],
        ],
    ];

    try {
        $stmt = getDB()->prepare(
            "SELECT client_name, client_branch, rating, quote_text, avatar_initial, avatar_image, row_group
             FROM about_testimonials
             WHERE is_active = 1
             ORDER BY row_group ASC, display_order ASC"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        return $fallback;
    }

    if (!$rows) return $fallback;

    $grouped = ['row1' => [], 'row2' => []];
    foreach ($rows as $r) {
        $key = $r['row_group'] === 'row2' ? 'row2' : 'row1';
        $grouped[$key][] = $r;
    }
    if (!$grouped['row1'] && !$grouped['row2']) return $fallback;
    return $grouped;
}

/**
 * Short, safe excerpt for the marquee card. The full quote is still
 * rendered in full on the card's data-full attribute so the "read
 * full testimonial" modal always has the complete text, regardless
 * of where the excerpt was cut.
 */
function noir_excerpt(string $text, int $limit = 128): string {
    $text = trim($text);
    if (mb_strlen($text) <= $limit) return $text;
    $cut = mb_substr($text, 0, $limit);
    // avoid cutting mid-word
    $cut = preg_replace('/\s+?(\S+)?$/u', '', $cut);
    return rtrim($cut, " \t\n\r\0\x0B,.;:") . '…';
}