/**
 * brew-icons.js
 * -------------------------------------------------------------------------
 * Single source of truth for the "brewing / preparing" overlay icons used
 * across the site (menu.php, reservation-menu.php for both Laguna and Manila
 * branches, and checkout.php for the multi-item order-preparation animation).
 *
 * UPDATED: added the 'combo' icon + 'packages' category for Packages Combo.
 *
 * Include this BEFORE any inline <script> that calls iconFor(), e.g.:
 *   <script src="brew-icons.js"></script>
 */

const BREW_ICON_PATHS = {
    cup:     '<path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/>',
    pastry:  '<path d="M3 11l19-9-9 19-2-8-8-2z"/>',
    glass:   '<path d="M8 2h8l-1 15a3 3 0 0 1-3 3h0a3 3 0 0 1-3-3L8 2z"/><line x1="7" y1="2" x2="17" y2="2"/><line x1="9" y1="20" x2="15" y2="20"/>',
    dessert: '<path d="M12 2c-3 4-3 7 0 9 3-2 3-5 0-9z"/><path d="M4 13h16l-1.5 7a2 2 0 0 1-2 1.6H7.5a2 2 0 0 1-2-1.6L4 13z"/>',
    combo:   '<line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>'
};

const BREW_LABELS = {
    cup:     'Pulling your shot…',
    pastry:  'Warming your pastry…',
    glass:   'Pouring your drink…',
    dessert: 'Plating your dessert…',
    combo:   'Putting your combo together…'
};

// Category -> icon key. Covers BOTH branches so menu.php, reservation-menu.php
// and checkout.php never fall out of sync when a new category is added.
const CATEGORY_TO_ICON = {
    // Laguna branch categories
    mains:      'cup',
    sides:      'pastry',
    drinks:     'glass',
    desserts:   'dessert',
    // Manila branch categories
    coffee:     'cup',
    meals:      'pastry',
    champagnes: 'glass',
    // Both branches: Packages Combo
    packages:   'combo'
};

function iconFor(category) {
    return CATEGORY_TO_ICON[category] || 'cup';
}