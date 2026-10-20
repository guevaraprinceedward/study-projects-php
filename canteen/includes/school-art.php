<?php
/**
 * includes/school-art.php — the COPS campus illustration.
 *
 * A Greco-Roman school building: a marble temple-front entrance (six fluted columns, pediment with the
 * CMDI logo), a classroom wing on the left and the CANTEEN wing on the right (striped awning, serving
 * arches, chimney). Drawn as plain SVG, so it stays sharp on every screen and needs no photo.
 *
 *   school_scene('wide')     → full campus panorama (index.php)
 *   school_scene('compact')  → the central block only (login pages)
 *
 * $p is a prefix for the SVG ids, so the scene can be used more than once on a page.
 */
const SCHOOL_LOGO = 'assets/img/cmdi-logo.jpg';

function school_scene(string $variant = 'wide', string $p = 's'): string
{
    $vb   = $variant === 'compact' ? '250 0 460 470' : '0 0 960 520';
    $logo = SCHOOL_LOGO;
    $o = '<svg class="school-scene scene-' . $variant . '" viewBox="' . $vb . '" preserveAspectRatio="xMidYMid slice" role="img" aria-label="Illustration of the school building and its canteen">';

    /* ── materials ── */
    $o .= <<<SVG
<defs>
  <linearGradient id="{$p}sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#8fbce6"/><stop offset=".5" stop-color="#cfe3f4"/><stop offset="1" stop-color="#fbf0d6"/></linearGradient>
  <radialGradient id="{$p}sun" cx="50%" cy="50%" r="50%"><stop offset="0" stop-color="#fff6cf" stop-opacity=".95"/><stop offset=".35" stop-color="#f6e2a0" stop-opacity=".45"/><stop offset="1" stop-color="#f6e2a0" stop-opacity="0"/></radialGradient>
  <linearGradient id="{$p}hillA" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#b9cbbd"/><stop offset="1" stop-color="#a1b8a6"/></linearGradient>
  <linearGradient id="{$p}hillB" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#98b39f"/><stop offset="1" stop-color="#7e9d88"/></linearGradient>
  <linearGradient id="{$p}lawn" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#9ebd88"/><stop offset="1" stop-color="#668a5b"/></linearGradient>
  <linearGradient id="{$p}shaft" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#aaa493"/><stop offset=".2" stop-color="#e9e5d9"/><stop offset=".42" stop-color="#ffffff"/><stop offset=".7" stop-color="#e3dece"/><stop offset="1" stop-color="#9d9784"/></linearGradient>
  <linearGradient id="{$p}cap" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="#d6d1c2"/></linearGradient>
  <linearGradient id="{$p}wall" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#bdb6a2"/><stop offset=".18" stop-color="#e2dccb"/><stop offset="1" stop-color="#f3efe3"/></linearGradient>
  <linearGradient id="{$p}wing" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f6f1e4"/><stop offset="1" stop-color="#e4dcc6"/></linearGradient>
  <linearGradient id="{$p}gold" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f2dc98"/><stop offset=".5" stop-color="#c9a24a"/><stop offset="1" stop-color="#8a6a1f"/></linearGradient>
  <linearGradient id="{$p}glass" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#8db6dd"/><stop offset="1" stop-color="#d7e8f6"/></linearGradient>
  <linearGradient id="{$p}warm" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f9e2a0"/><stop offset="1" stop-color="#e8b95e"/></linearGradient>
  <linearGradient id="{$p}door" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#244677"/><stop offset="1" stop-color="#0d1f3c"/></linearGradient>
  <linearGradient id="{$p}roof" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#c46f50"/><stop offset="1" stop-color="#8f4a36"/></linearGradient>
  <linearGradient id="{$p}tym" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2a4d82"/><stop offset="1" stop-color="#13294b"/></linearGradient>
  <linearGradient id="{$p}tree" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#2f5a3a"/><stop offset=".5" stop-color="#4b8057"/><stop offset="1" stop-color="#2a4f34"/></linearGradient>
  <pattern id="{$p}flute" width="5" height="20" patternUnits="userSpaceOnUse"><rect width="1.4" height="20" fill="#5d5846" opacity=".28"/><rect x="2.2" width=".8" height="20" fill="#fff" opacity=".75"/></pattern>
  <pattern id="{$p}tiles" width="9" height="7" patternUnits="userSpaceOnUse"><path d="M0 6.5H9M4.5 0V6.5" stroke="#6e3524" stroke-width=".8" opacity=".5" fill="none"/></pattern>
  <pattern id="{$p}stripe" width="22" height="10" patternUnits="userSpaceOnUse"><rect width="11" height="10" fill="#13294b"/><rect x="11" width="11" height="10" fill="#f7f1e0"/></pattern>
  <clipPath id="{$p}logoClip"><circle cx="480" cy="53" r="21"/></clipPath>
</defs>
SVG;

    /* ── sky, sun, clouds, hills ── */
    $o .= '<rect width="960" height="520" fill="url(#' . $p . 'sky)"/>';
    $o .= '<circle cx="800" cy="104" r="120" fill="url(#' . $p . 'sun)"/><circle cx="800" cy="104" r="26" fill="#fff4c4" opacity=".9"/>';
    $o .= '<g class="cloud c1" fill="#fff" opacity=".9"><ellipse cx="170" cy="96" rx="64" ry="15"/><ellipse cx="206" cy="82" rx="40" ry="16"/><ellipse cx="136" cy="86" rx="32" ry="12"/></g>';
    $o .= '<g class="cloud c2" fill="#fff" opacity=".8"><ellipse cx="620" cy="60" rx="54" ry="12"/><ellipse cx="648" cy="50" rx="34" ry="13"/></g>';
    $o .= '<g class="cloud c3" fill="#fff" opacity=".7"><ellipse cx="880" cy="190" rx="50" ry="11"/><ellipse cx="860" cy="182" rx="30" ry="11"/></g>';
    $o .= '<path d="M0 372 C90 330 170 344 250 360 S400 318 470 340 S640 312 720 336 S880 318 960 346 V430 H0Z" fill="url(#' . $p . 'hillA)"/>';
    $o .= '<path d="M0 392 C110 364 200 380 300 388 S520 360 620 380 S830 358 960 384 V430 H0Z" fill="url(#' . $p . 'hillB)"/>';
    $o .= '<path d="M236 74 q8-6 16 0 q8-6 16 0 M300 40 q6-5 12 0 q6-5 12 0 M700 54 q7-6 14 0 q7-6 14 0" stroke="#40526e" stroke-width="1.4" fill="none" stroke-linecap="round"/>';

    /* ── lawn ── */
    $o .= '<rect y="414" width="960" height="106" fill="url(#' . $p . 'lawn)"/>';
    $o .= '<rect y="414" width="960" height="5" fill="#4f7347" opacity=".55"/>';

    /* ── wings (drawn first, so the temple front sits in front of them) ── */
    foreach (['L' => 96, 'R' => 630] as $side => $x0) {
        $x1 = $x0 + 234;
        $o .= '<g class="wing wing-' . $side . '">';
        // hip roof, ridge, tiles
        $o .= '<polygon points="' . ($x0 - 10) . ',208 ' . ($x1 + 10) . ',208 ' . ($x1 - 18) . ',166 ' . ($x0 + 18) . ',166" fill="url(#' . $p . 'roof)"/>';
        $o .= '<polygon points="' . ($x0 - 10) . ',208 ' . ($x1 + 10) . ',208 ' . ($x1 - 18) . ',166 ' . ($x0 + 18) . ',166" fill="url(#' . $p . 'tiles)"/>';
        $o .= '<line x1="' . ($x0 + 18) . '" y1="166" x2="' . ($x1 - 18) . '" y2="166" stroke="#e0c27a" stroke-width="3"/>';
        // wall, cornice, plinth
        $o .= '<rect x="' . $x0 . '" y="208" width="234" height="212" fill="url(#' . $p . 'wing)"/>';
        $o .= '<rect x="' . ($x0 - 6) . '" y="204" width="246" height="9" fill="url(#' . $p . 'cap)" stroke="#cdbb8a" stroke-width=".6"/>';
        $o .= '<rect x="' . ($x0 - 6) . '" y="212" width="246" height="2" fill="url(#' . $p . 'gold)"/>';
        $o .= '<rect x="' . $x0 . '" y="214" width="234" height="8" fill="#000" opacity=".07"/>';
        $o .= '<rect x="' . $x0 . '" y="402" width="234" height="18" fill="#d8d0b9"/><rect x="' . $x0 . '" y="402" width="234" height="2" fill="#fff" opacity=".7"/>';
        $o .= '<rect x="' . $x0 . '" y="294" width="234" height="5" fill="#fff" opacity=".8"/><rect x="' . $x0 . '" y="299" width="234" height="2" fill="#000" opacity=".08"/>';
        // corner quoins
        for ($i = 0; $i < 9; $i++) {
            $qy = 226 + $i * 20;
            $o .= '<rect x="' . $x0 . '" y="' . $qy . '" width="' . ($i % 2 ? 11 : 15) . '" height="17" fill="#fff" opacity=".6"/>';
            $o .= '<rect x="' . ($x1 - ($i % 2 ? 11 : 15)) . '" y="' . $qy . '" width="' . ($i % 2 ? 11 : 15) . '" height="17" fill="#fff" opacity=".6"/>';
        }
        // upper floor: four arched windows
        for ($i = 0; $i < 4; $i++) {
            $wx = $x0 + 28 + $i * 48;
            $o .= $side === 'R' ? school_window($p, $wx, 222, 30, 36, true, 'glass') : school_window($p, $wx, 232, 30, 52, true, 'glass');
        }
        $o .= '</g>';
    }
    // classroom wing (left): lower windows with blinds
    for ($i = 0; $i < 4; $i++) {
        $wx = 96 + 28 + $i * 48;
        $o .= school_window($p, $wx, 318, 30, 62, false, 'glass');
        $o .= '<g opacity=".55" stroke="#f4efe0" stroke-width="1.2">';
        for ($b = 0; $b < 5; $b++) $o .= '<line x1="' . ($wx + 2) . '" y1="' . (322 + $b * 4) . '" x2="' . ($wx + 28) . '" y2="' . (322 + $b * 4) . '"/>';
        $o .= '</g>';
    }

    // canteen wing (right): serving arches, awning, sign, chimney
    $o .= '<rect x="822" y="128" width="20" height="46" fill="#a45a44"/><rect x="819" y="123" width="26" height="8" fill="#6e3524"/>';
    $o .= '<g class="smoke" fill="#fff"><circle cx="832" cy="112" r="7" opacity=".7"/><circle cx="838" cy="96" r="9" opacity=".5"/><circle cx="846" cy="78" r="11" opacity=".32"/></g>';
    for ($i = 0; $i < 3; $i++) {
        $ax = 654 + $i * 64;
        $o .= '<path d="M' . $ax . ' 388 V338 A26 26 0 0 1 ' . ($ax + 52) . ' 338 V388Z" fill="url(#' . $p . 'warm)" stroke="#8a6a1f" stroke-width="1.6"/>';
        $o .= '<path d="M' . ($ax + 5) . ' 388 V340 A21 21 0 0 1 ' . ($ax + 47) . ' 340 V388Z" fill="#fff" opacity=".16"/>';
        $o .= '<rect x="' . ($ax + 12) . '" y="352" width="28" height="3" fill="#fff" opacity=".5"/>';
        $o .= '<rect x="' . ($ax - 4) . '" y="386" width="60" height="9" fill="#13294b"/><rect x="' . ($ax - 4) . '" y="386" width="60" height="2" fill="#d9bb68"/>';
        $o .= '<line x1="' . ($ax + 26) . '" y1="316" x2="' . ($ax + 26) . '" y2="332" stroke="#3a2c0e" stroke-width="1"/><circle cx="' . ($ax + 26) . '" cy="336" r="5" fill="#fff0b8"/><circle cx="' . ($ax + 26) . '" cy="336" r="12" fill="#fff0b8" opacity=".35"/>';
    }
    // striped awning with scalloped edge
    $o .= '<path d="M638 296 H862 L874 322 H626Z" fill="url(#' . $p . 'stripe)"/>';
    $sc = 'M626 322';
    for ($i = 0; $i < 24; $i++) $sc .= ' a10.35 9 0 0 0 10.35 0';
    $o .= '<path d="' . $sc . ' V322Z" fill="url(#' . $p . 'stripe)"/>';
    $o .= '<path d="M638 296 H862 L874 322 H626Z" fill="#000" opacity=".1"/><rect x="638" y="294" width="224" height="3" fill="url(#' . $p . 'gold)"/>';
    // hanging sign
    $o .= '<line x1="690" y1="268" x2="690" y2="296" stroke="#6e5a2a" stroke-width="1.4"/><line x1="806" y1="268" x2="806" y2="296" stroke="#6e5a2a" stroke-width="1.4"/>';
    $o .= '<rect x="676" y="264" width="144" height="30" rx="2" fill="#13294b" stroke="url(#' . $p . 'gold)" stroke-width="2.2"/><rect x="681" y="269" width="134" height="20" fill="none" stroke="#d9bb68" stroke-width=".7"/>';
    $o .= '<text x="748" y="283.5" text-anchor="middle" font-family="Cinzel,\'Times New Roman\',serif" font-weight="700" font-size="15" letter-spacing="5" fill="#e9cc7a">CANTEEN</text>';
    // A-frame menu board + planters
    $o .= '<polygon points="690,420 702,384 724,384 736,420" fill="#2c3b2e" stroke="#c9a24a" stroke-width="1.4"/><text x="713" y="402" text-anchor="middle" font-family="Cinzel,serif" font-size="7" letter-spacing="1" fill="#f1e2ad">MENU</text><line x1="700" y1="408" x2="726" y2="408" stroke="#f1e2ad" stroke-width=".7" opacity=".6"/>';
    $o .= '<ellipse cx="836" cy="410" rx="14" ry="12" fill="#4b8057"/><rect x="826" y="410" width="20" height="10" fill="#a45a44"/>';
    $o .= '<ellipse cx="124" cy="410" rx="14" ry="12" fill="#4b8057"/><rect x="114" y="410" width="20" height="10" fill="#a45a44"/>';

    // flag on the classroom wing
    $o .= '<line x1="132" y1="92" x2="132" y2="168" stroke="#8a6a1f" stroke-width="2"/><circle cx="132" cy="90" r="3" fill="url(#' . $p . 'gold)"/>';
    $o .= '<g class="flag"><path d="M134 96 C150 90 160 104 178 98 V124 C160 130 150 116 134 122Z" fill="#13294b"/><path d="M134 108 C150 102 160 116 178 110 V114 C160 120 150 106 134 112Z" fill="#d9bb68"/></g>';

    /* ── central temple front ── */
    $o .= '<g class="temple-front">';
    // back wall of the entrance hall
    $o .= '<rect x="336" y="108" width="288" height="288" fill="url(#' . $p . 'wall)"/>';
    // windows + central lunette
    foreach ([380, 580] as $wx) {
        $o .= school_window($p, $wx - 15, 146, 30, 64, false, 'glass', true);
        $o .= school_window($p, $wx - 15, 240, 30, 64, false, 'glass');
    }
    $o .= '<path d="M458 218 V176 A22 22 0 0 1 502 176 V218Z" fill="url(#' . $p . 'glass)" stroke="#fff" stroke-width="3"/><path d="M458 218 V176 A22 22 0 0 1 502 176 V218Z" fill="none" stroke="#cdbb8a" stroke-width=".8"/><line x1="480" y1="154" x2="480" y2="218" stroke="#fff" stroke-width="2"/><line x1="458" y1="190" x2="502" y2="190" stroke="#fff" stroke-width="2"/>';
    // door: marble frame, arched fanlight, navy leaves, warm light
    $o .= '<rect x="454" y="318" width="52" height="80" fill="#fff" stroke="#cdbb8a" stroke-width=".8"/><path d="M458 326 A22 14 0 0 1 502 326 V398 H458Z" fill="url(#' . $p . 'door)"/>';
    $o .= '<path d="M462 326 A18 11 0 0 1 498 326 H462Z" fill="url(#' . $p . 'warm)" opacity=".9"/><line x1="480" y1="316" x2="480" y2="396" stroke="#d9bb68" stroke-width="1.2"/>';
    $o .= '<rect x="464" y="338" width="13" height="22" fill="none" stroke="#d9bb68" stroke-width=".6" opacity=".7"/><rect x="483" y="338" width="13" height="22" fill="none" stroke="#d9bb68" stroke-width=".6" opacity=".7"/><circle cx="476" cy="368" r="1.6" fill="#f2dc98"/><circle cx="484" cy="368" r="1.6" fill="#f2dc98"/>';
    $o .= '<ellipse cx="480" cy="398" rx="46" ry="6" fill="#ffe9a6" opacity=".35"/>';
    // cast shadows of the columns on the wall
    foreach ([355, 405, 455, 505, 555, 605] as $cx) $o .= '<rect x="' . ($cx + 10) . '" y="108" width="18" height="288" fill="#2c2614" opacity=".1"/>';
    // columns
    foreach ([355, 405, 455, 505, 555, 605] as $cx) $o .= school_column($p, $cx);
    // entablature
    $o .= '<rect x="326" y="98" width="308" height="14" fill="url(#' . $p . 'cap)" stroke="#cdbb8a" stroke-width=".6"/><rect x="326" y="110" width="308" height="2" fill="url(#' . $p . 'gold)"/>';
    $o .= '<rect x="326" y="82" width="308" height="17" fill="#fbf9f2" stroke="#d6c9a6" stroke-width=".5"/>';
    $o .= '<text x="480" y="94" text-anchor="middle" font-family="Cinzel,\'Times New Roman\',serif" font-weight="600" font-size="8.6" letter-spacing="2.3" fill="#8a6a1f">CARD-MRI DEVELOPMENT INSTITUTE</text>';
    $o .= '<rect x="326" y="82" width="308" height="6" fill="#000" opacity=".06"/>';
    for ($dx = 328; $dx < 632; $dx += 6) $o .= '<rect x="' . $dx . '" y="77" width="3.4" height="5" fill="#cbbf9f"/>';
    $o .= '<rect x="318" y="70" width="324" height="8" fill="url(#' . $p . 'cap)" stroke="#cdbb8a" stroke-width=".6"/><rect x="318" y="77" width="324" height="1.8" fill="url(#' . $p . 'gold)"/>';
    // pediment with the logo medallion
    $o .= '<polygon points="312,72 480,4 648,72" fill="url(#' . $p . 'cap)" stroke="#cdbb8a" stroke-width="1"/>';
    $o .= '<polygon points="312,72 480,4 648,72" fill="none" stroke="url(#' . $p . 'gold)" stroke-width="2.2"/>';
    $o .= '<polygon points="340,69 480,14 620,69" fill="url(#' . $p . 'tym)"/><polygon points="340,69 480,14 620,69" fill="none" stroke="#d9bb68" stroke-width=".8"/>';
    $o .= '<polygon points="354,66 480,22 606,66" fill="none" stroke="#d9bb68" stroke-width=".5" stroke-dasharray="2 3" opacity=".7"/>';
    $o .= '<circle cx="480" cy="53" r="24.5" fill="url(#' . $p . 'gold)"/><circle cx="480" cy="53" r="22" fill="#fff"/>';
    $o .= '<image href="' . $logo . '" x="459" y="32" width="42" height="42" clip-path="url(#' . $p . 'logoClip)" preserveAspectRatio="xMidYMid slice"/>';
    $o .= '<circle cx="480" cy="53" r="21" fill="none" stroke="#8a6a1f" stroke-width=".6"/>';
    $o .= '<path d="M480 -2 l-9 8 q9 -6 18 0z M308 74 l10 -4 -4 -12 q-8 6 -6 16z M652 74 l-10 -4 4 -12 q8 6 6 16z" fill="url(#' . $p . 'gold)"/>';
    // steps
    $o .= '<rect x="322" y="396" width="316" height="8" fill="url(#' . $p . 'cap)"/><rect x="312" y="404" width="336" height="8" fill="#ece7d9"/><rect x="302" y="412" width="356" height="8" fill="#ddd6c3"/>';
    $o .= '<rect x="322" y="396" width="316" height="1.6" fill="#fff"/><rect x="312" y="404" width="336" height="1.6" fill="#fff"/><rect x="302" y="412" width="356" height="1.6" fill="#fff"/>';
    $o .= '<rect x="302" y="419" width="356" height="5" fill="#000" opacity=".12"/>';
    $o .= '</g>';

    /* ── foreground: path, lamps, trees, shrubs ── */
    $o .= '<polygon points="302,420 658,420 740,520 220,520" fill="#e2dac4"/><polygon points="302,420 658,420 740,520 220,520" fill="none" stroke="#c4b88f" stroke-width="1"/>';
    $o .= '<g stroke="#b9ad86" stroke-width=".8" opacity=".7"><line x1="258" y1="460" x2="702" y2="460"/><line x1="236" y1="490" x2="724" y2="490"/><line x1="480" y1="420" x2="480" y2="520"/></g>';
    foreach ([288, 672] as $lx) {
        $o .= '<rect x="' . ($lx - 2) . '" y="352" width="4" height="64" fill="#2c3b4a"/><rect x="' . ($lx - 7) . '" y="412" width="14" height="6" fill="#2c3b4a"/>';
        $o .= '<path d="M' . ($lx - 8) . ' 352 L' . ($lx - 5) . ' 336 H' . ($lx + 5) . ' L' . ($lx + 8) . ' 352Z" fill="#fff2b8" stroke="#2c3b4a" stroke-width="1.4"/><circle cx="' . $lx . '" cy="344" r="16" fill="#fff2b8" opacity=".25"/>';
    }
    foreach ([[38, 360], [66, 376], [926, 360], [898, 378]] as [$tx, $ty]) {
        $o .= '<rect x="' . ($tx - 2) . '" y="' . ($ty + 40) . '" width="4" height="' . (420 - $ty - 40) . '" fill="#5a4a2e"/>';
        $o .= '<ellipse cx="' . $tx . '" cy="' . $ty . '" rx="17" ry="52" fill="url(#' . $p . 'tree)"/><ellipse cx="' . ($tx - 4) . '" cy="' . ($ty - 8) . '" rx="5" ry="34" fill="#7bab80" opacity=".35"/>';
    }
    foreach ([192, 770] as $bx) $o .= '<ellipse cx="' . $bx . '" cy="434" rx="38" ry="12" fill="#5a8a55"/><ellipse cx="' . $bx . '" cy="430" rx="30" ry="9" fill="#78a572"/>';
    foreach ([[205, 430, '#e9a8b8'], [180, 436, '#fff'], [214, 438, '#f0d78e'], [760, 432, '#fff'], [784, 438, '#e9a8b8'], [772, 430, '#f0d78e']] as [$fx, $fy, $fc]) $o .= '<circle cx="' . $fx . '" cy="' . $fy . '" r="2.4" fill="' . $fc . '"/>';
    // soft ground shadow + vignette so the scene sits in its frame
    $o .= '<rect x="0" y="470" width="960" height="50" fill="#2c4a2a" opacity=".16"/>';

    return $o . '</svg>';
}

/** One window with a marble surround; optional arched head, optional pediment. */
function school_window(string $p, float $x, float $y, float $w, float $h, bool $arched, string $glass = 'glass', bool $pedimented = false): string
{
    $r = $w / 2;
    $o = '';
    if ($pedimented) $o .= '<polygon points="' . ($x - 4) . ',' . ($y - 2) . ' ' . ($x + $r) . ',' . ($y - 12) . ' ' . ($x + $w + 4) . ',' . ($y - 2) . '" fill="#fff" stroke="#cdbb8a" stroke-width=".6"/>';
    if ($arched) {
        $d = 'M' . $x . ' ' . ($y + $h) . ' V' . ($y + $r) . ' A' . $r . ' ' . $r . ' 0 0 1 ' . ($x + $w) . ' ' . ($y + $r) . ' V' . ($y + $h) . 'Z';
        $o .= '<path d="' . $d . '" fill="url(#' . $p . $glass . ')" stroke="#fff" stroke-width="3"/><path d="' . $d . '" fill="none" stroke="#c9b98a" stroke-width=".7"/>';
        $o .= '<line x1="' . ($x + $r) . '" y1="' . $y . '" x2="' . ($x + $r) . '" y2="' . ($y + $h) . '" stroke="#fff" stroke-width="1.6"/><line x1="' . $x . '" y1="' . ($y + $h * .55) . '" x2="' . ($x + $w) . '" y2="' . ($y + $h * .55) . '" stroke="#fff" stroke-width="1.6"/>';
    } else {
        $o .= '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="url(#' . $p . $glass . ')" stroke="#fff" stroke-width="3"/><rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="none" stroke="#c9b98a" stroke-width=".7"/>';
        $o .= '<line x1="' . ($x + $r) . '" y1="' . $y . '" x2="' . ($x + $r) . '" y2="' . ($y + $h) . '" stroke="#fff" stroke-width="1.6"/><line x1="' . $x . '" y1="' . ($y + $h / 2) . '" x2="' . ($x + $w) . '" y2="' . ($y + $h / 2) . '" stroke="#fff" stroke-width="1.6"/>';
    }
    $o .= '<polygon points="' . ($x + 2) . ',' . ($y + 2) . ' ' . ($x + $w * .55) . ',' . ($y + 2) . ' ' . ($x + 2) . ',' . ($y + $h * .5) . '" fill="#fff" opacity=".3"/>';
    $o .= '<rect x="' . ($x - 3) . '" y="' . ($y + $h) . '" width="' . ($w + 6) . '" height="4" fill="#fff" stroke="#cdbb8a" stroke-width=".5"/>';
    return $o;
}

/** A fluted marble column with a Corinthian-style capital, centred on $cx. */
function school_column(string $p, float $cx): string
{
    $o  = '<g class="col">';
    $o .= '<rect x="' . ($cx - 15) . '" y="392" width="30" height="6" fill="url(#' . $p . 'cap)" stroke="#cdbb8a" stroke-width=".5"/>';
    $o .= '<rect x="' . ($cx - 12.5) . '" y="386" width="25" height="7" rx="3" fill="url(#' . $p . 'cap)" stroke="#cdbb8a" stroke-width=".5"/>';
    $d = 'M' . ($cx - 9.5) . ' 128 C' . ($cx - 10.5) . ' 220 ' . ($cx - 11.5) . ' 330 ' . ($cx - 11.5) . ' 387 L' . ($cx + 11.5) . ' 387 C' . ($cx + 11.5) . ' 330 ' . ($cx + 10.5) . ' 220 ' . ($cx + 9.5) . ' 128Z';
    $o .= '<path d="' . $d . '" fill="url(#' . $p . 'shaft)"/><path d="' . $d . '" fill="url(#' . $p . 'flute)"/>';
    $o .= '<path d="' . $d . '" fill="#000" opacity=".05"/>';
    $o .= '<rect x="' . ($cx - 10) . '" y="126" width="20" height="3" fill="url(#' . $p . 'gold)"/>';
    // capital: bell, acanthus, volutes, abacus
    $o .= '<path d="M' . ($cx - 10) . ' 126 C' . ($cx - 13) . ' 118 ' . ($cx - 15) . ' 112 ' . ($cx - 15) . ' 108 H' . ($cx + 15) . ' C' . ($cx + 15) . ' 112 ' . ($cx + 13) . ' 118 ' . ($cx + 10) . ' 126Z" fill="url(#' . $p . 'cap)" stroke="#cdbb8a" stroke-width=".5"/>';
    $o .= '<path d="M' . ($cx - 8) . ' 125 q-3-8 1-12 M' . ($cx) . ' 125 q-3-9 0-15 M' . ($cx + 8) . ' 125 q3-8-1-12" stroke="#a89e80" stroke-width=".9" fill="none"/>';
    $o .= '<circle cx="' . ($cx - 13) . '" cy="111" r="3.4" fill="url(#' . $p . 'cap)" stroke="#a89e80" stroke-width=".7"/><circle cx="' . ($cx + 13) . '" cy="111" r="3.4" fill="url(#' . $p . 'cap)" stroke="#a89e80" stroke-width=".7"/>';
    $o .= '<rect x="' . ($cx - 18) . '" y="104" width="36" height="5" fill="url(#' . $p . 'cap)" stroke="#cdbb8a" stroke-width=".5"/>';
    return $o . '</g>';
}       