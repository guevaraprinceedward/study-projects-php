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
 */
function get_about_testimonials(): array {
    $fallback = [
        'row1' => [
            ['client_name' => 'Isabella Marasigan', 'client_branch' => 'Makati City', 'rating' => 5, 'quote_text' => 'Every piece feels considered — from the packaging to the stitching.', 'avatar_initial' => 'IM'],
            ['client_name' => 'Rafael Domingo', 'client_branch' => 'Quezon City', 'rating' => 5, 'quote_text' => 'The fit alone justifies the price. I have not stopped wearing it since.', 'avatar_initial' => 'RD'],
            ['client_name' => 'Camille Suarez', 'client_branch' => 'Cebu City', 'rating' => 5, 'quote_text' => 'Shopping here was genuinely enjoyable, and the photos matched the real thing exactly.', 'avatar_initial' => 'CS'],
        ],
        'row2' => [
            ['client_name' => 'Diego Reyes', 'client_branch' => 'Iloilo City', 'rating' => 5, 'quote_text' => 'Limited edition drops are no joke — sold out within days.', 'avatar_initial' => 'DR'],
            ['client_name' => 'Samantha Cruz', 'client_branch' => 'Baguio City', 'rating' => 5, 'quote_text' => 'The wool coat kept me warm and still looked sharp in every photo.', 'avatar_initial' => 'SC'],
            ['client_name' => 'Gabriel Tantoco', 'client_branch' => 'Antipolo City', 'rating' => 5, 'quote_text' => 'Feels like something you pass down, not just wear.', 'avatar_initial' => 'GT'],
        ],
    ];

    try {
        $stmt = getDB()->prepare(
            "SELECT client_name, client_branch, rating, quote_text, avatar_initial, row_group
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