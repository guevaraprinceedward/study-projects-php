<?php
/**
 * auth-temple.php — the marble temple illustration shown on the login pages.
 * Included by log-in.php and admin-login.php:   <?php include 'auth-temple.php'; ?>
 * Pure inline SVG, no images to download. The laurel wreath in the pediment is
 * drawn by cops-ui.js (the [data-wreath] group).
 */
?>
<svg viewBox="0 -12 480 368" preserveAspectRatio="xMidYMin meet" role="img" aria-label="A marble temple with a laurel wreath in its pediment" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="tmMarble" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fffdf6"/><stop offset="1" stop-color="#e3dac2"/></linearGradient>
        <linearGradient id="tmShaft" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#9d9378"/><stop offset=".28" stop-color="#f4eedd"/><stop offset=".5" stop-color="#ffffff"/>
            <stop offset=".78" stop-color="#d9d0b8"/><stop offset="1" stop-color="#857b60"/>
        </linearGradient>
        <linearGradient id="tmStep" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f9f4e6"/><stop offset="1" stop-color="#c9bfa4"/></linearGradient>
        <linearGradient id="tmGold" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f0d78e"/><stop offset=".5" stop-color="#c9a24a"/><stop offset="1" stop-color="#8a6a1f"/></linearGradient>
        <linearGradient id="tmTymp" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0c1b38"/><stop offset="1" stop-color="#23467d"/></linearGradient>
        <radialGradient id="tmGlow" cx=".5" cy=".9" r=".75">
            <stop offset="0" stop-color="#f6dfa0" stop-opacity=".95"/><stop offset=".5" stop-color="#b8903a" stop-opacity=".5"/><stop offset="1" stop-color="#0b1730" stop-opacity="0"/>
        </radialGradient>
        <radialGradient id="tmTorch" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#f6dfa0" stop-opacity=".7"/><stop offset="1" stop-color="#f6dfa0" stop-opacity="0"/></radialGradient>
        <pattern id="tmFlute" width="5.4" height="10" patternUnits="userSpaceOnUse">
            <rect width="1.1" height="10" fill="#5d5236" opacity=".32"/><rect x="1.1" width="1.4" height="10" fill="#ffffff" opacity=".4"/>
        </pattern>
        <pattern id="tmFrieze" x="36" y="118" width="24" height="20" patternUnits="userSpaceOnUse">
            <rect width="24" height="20" fill="#e9e0c8"/>
            <rect width="10" height="20" fill="url(#tmMarble)"/>
            <rect x="2.1" y="1" width="1.6" height="14" fill="#6f6548" opacity=".55"/>
            <rect x="6.3" y="1" width="1.6" height="14" fill="#6f6548" opacity=".55"/>
            <rect width="10" height="20" fill="none" stroke="#bfb497" stroke-width=".5"/>
            <circle cx="17" cy="10" r="2.6" fill="#b8903a"/><circle cx="17" cy="10" r="1" fill="#f0d78e"/>
        </pattern>
        <pattern id="tmDentil" x="18" y="114" width="8" height="4" patternUnits="userSpaceOnUse">
            <rect width="4.5" height="4" fill="#cfc6ad"/>
        </pattern>
        <g id="tmCol">
            <rect x="-19" y="-12" width="38" height="6" fill="url(#tmMarble)" stroke="#a79d82" stroke-width=".6"/>
            <path d="M-14 -6 L14 -6 L13 0 L-13 0 Z" fill="url(#tmMarble)" stroke="#a79d82" stroke-width=".6"/>
            <path d="M-13 0 L13 0 L15 130 L-15 130 Z" fill="url(#tmShaft)"/>
            <path d="M-13 0 L13 0 L15 130 L-15 130 Z" fill="url(#tmFlute)"/>
            <rect x="-18" y="130" width="36" height="6" rx="2" fill="url(#tmMarble)" stroke="#a79d82" stroke-width=".6"/>
            <rect x="-21" y="136" width="42" height="8" fill="url(#tmMarble)" stroke="#a79d82" stroke-width=".6"/>
        </g>
    </defs>

    <!-- inner hall, lit by gold light -->
    <rect x="36" y="156" width="408" height="156" fill="#0b1730"/>
    <rect x="36" y="156" width="408" height="156" fill="url(#tmGlow)"/>
    <path d="M214 312 V232 a26 26 0 0 1 52 0 V312z" fill="#060d1b" stroke="#b8903a" stroke-width="1.2"/>
    <path d="M214 312 V232 a26 26 0 0 1 52 0 V312z" fill="url(#tmGlow)" opacity=".45"/>
    <g class="tm-torch">
        <circle cx="200" cy="248" r="18" fill="url(#tmTorch)"/>
        <rect x="199" y="256" width="2" height="22" fill="#8a6a1f"/>
        <path class="flame" d="M200 256 c-4 -6 -1 -11 0 -17 c2 5 5 10 0 17z" fill="#f6dfa0"/>
    </g>
    <g class="tm-torch">
        <circle cx="280" cy="248" r="18" fill="url(#tmTorch)"/>
        <rect x="279" y="256" width="2" height="22" fill="#8a6a1f"/>
        <path class="flame" d="M280 256 c-4 -6 -1 -11 0 -17 c2 5 5 10 0 17z" fill="#f6dfa0"/>
    </g>

    <!-- five fluted columns -->
    <use href="#tmCol" x="66"  y="168"/>
    <use href="#tmCol" x="153" y="168"/>
    <use href="#tmCol" x="240" y="168"/>
    <use href="#tmCol" x="327" y="168"/>
    <use href="#tmCol" x="414" y="168"/>

    <!-- architrave with the inscription -->
    <rect x="36" y="138" width="408" height="18" fill="url(#tmMarble)" stroke="#a79d82" stroke-width=".6"/>
    <rect x="36" y="154.5" width="408" height="1.5" fill="#b8903a"/>
    <text x="240" y="151.6" text-anchor="middle" font-family="Cinzel, serif" font-size="10" font-weight="600" fill="#ffffff" opacity=".9" textLength="330" lengthAdjust="spacing">MENS SANA IN CORPORE SANO</text>
    <text x="240" y="151" text-anchor="middle" font-family="Cinzel, serif" font-size="10" font-weight="600" fill="#8a6a1f" textLength="330" lengthAdjust="spacing">MENS SANA IN CORPORE SANO</text>

    <!-- frieze (triglyphs and metopes), dentils, cornice -->
    <rect x="36" y="118" width="408" height="20" fill="url(#tmFrieze)" stroke="#a79d82" stroke-width=".5"/>
    <rect x="18" y="114" width="444" height="4" fill="#efe7d0"/>
    <rect x="18" y="114" width="444" height="4" fill="url(#tmDentil)"/>
    <rect x="18" y="104" width="444" height="10" fill="url(#tmMarble)" stroke="#a79d82" stroke-width=".6"/>
    <rect x="18" y="104" width="444" height="1.6" fill="#b8903a"/>

    <!-- pediment -->
    <polygon points="18,104 240,10 462,104" fill="url(#tmMarble)" stroke="#a79d82" stroke-width=".8"/>
    <polygon points="48,100 240,18.7 432,100" fill="none" stroke="#b8903a" stroke-width="1.2"/>
    <polygon points="73,98 240,27.4 407,98" fill="url(#tmTymp)" stroke="#8a6a1f" stroke-width="1"/>
    <g transform="translate(240 80)">
        <g data-wreath="22" data-pairs="8"></g>
        <circle r="8" fill="url(#tmGold)" stroke="#8a6a1f" stroke-width=".6"/>
        <g stroke="#d9b45a" stroke-width="1" stroke-linecap="round">
            <line x1="10.5" y1="0" x2="14" y2="0"/><line x1="-10.5" y1="0" x2="-14" y2="0"/>
            <line x1="0" y1="10.5" x2="0" y2="14"/><line x1="0" y1="-10.5" x2="0" y2="-14"/>
            <line x1="7.4" y1="7.4" x2="9.9" y2="9.9"/><line x1="-7.4" y1="7.4" x2="-9.9" y2="9.9"/>
            <line x1="7.4" y1="-7.4" x2="9.9" y2="-9.9"/><line x1="-7.4" y1="-7.4" x2="-9.9" y2="-9.9"/>
        </g>
    </g>
    <!-- acroteria -->
    <path d="M240 12 c-7 -2 -9 -10 0 -16 c9 6 7 14 0 16z" fill="url(#tmGold)" stroke="#8a6a1f" stroke-width=".5"/>
    <path d="M20 88 l5 9 -5 9 -5 -9z" fill="url(#tmGold)" stroke="#8a6a1f" stroke-width=".5"/>
    <path d="M460 88 l5 9 -5 9 -5 -9z" fill="url(#tmGold)" stroke="#8a6a1f" stroke-width=".5"/>

    <!-- steps -->
    <rect x="24" y="312" width="432" height="12" fill="url(#tmStep)" stroke="#a79d82" stroke-width=".6"/>
    <rect x="14" y="324" width="452" height="12" fill="url(#tmStep)" stroke="#a79d82" stroke-width=".6"/>
    <rect x="4"  y="336" width="472" height="20" fill="url(#tmStep)" stroke="#a79d82" stroke-width=".6"/>
    <rect x="24" y="312" width="432" height="1.2" fill="#ffffff" opacity=".9"/>
    <rect x="14" y="324" width="452" height="1.2" fill="#ffffff" opacity=".9"/>
    <rect x="4"  y="336" width="472" height="1.2" fill="#ffffff" opacity=".9"/>
</svg>