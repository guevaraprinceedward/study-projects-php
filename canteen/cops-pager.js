
/* ==========================================================================
   cops-pager.js — pagination for product cards on menu.php

   4 products per page, per category section (and for the flat grid when sorting).
   Works without touching card markup. If JS is off, all cards simply stay visible.
   ========================================================================== */

(function () {

    'use strict';


    /* ==========================================
       PAGINATION CONFIGURATION
       Defines the number of products displayed per page.
    ========================================== */

    // Set the maximum number of product cards displayed on each page.
    var PER_PAGE = 4;


    /* ==========================================
       INITIALIZATION FUNCTION
       Finds all product grids and initializes pagination.
    ========================================== */

    function init() {

        // Select all product grids inside the menu wrapper.
        var grids = document.querySelectorAll('.menu-wrap .grid-products');

        // Initialize pagination separately for every product grid.
        Array.prototype.forEach.call(grids, setup);

    }


    /* ==========================================
       PAGINATION SETUP
       Creates and configures pagination controls
       for an individual product grid.
    ========================================== */

    function setup(grid) {

        // Convert all direct grid children into an array of product cards.
        var cards = Array.prototype.slice.call(grid.children);

        // Skip pagination when the number of cards does not exceed the page limit.
        if (cards.length <= PER_PAGE) return;

        // Calculate the total number of pages based on the product count.
        var total = Math.ceil(cards.length / PER_PAGE);

        // Set the initial active page to the first page.
        var page = 1;

        // Create the navigation container for pagination controls.
        var nav = document.createElement('nav');

        // Assign the pagination container class.
        nav.className = 'cp-pager';

        // Add an accessible label describing the pagination navigation.
        nav.setAttribute('aria-label', 'Product pages');

        // Insert the pagination navigation immediately after the product grid.
        grid.insertAdjacentElement('afterend', nav);


        /* ==========================================
           PAGE NUMBER GENERATOR
           Determines which page numbers should appear
           in the pagination navigation.
        ========================================== */

        function pageList() {

            // Pagination pattern: first page, nearby pages, and last page.
            var set = {};

            // Collect the first, last, previous, current, and next page numbers.
            [1, total, page - 1, page, page + 1].forEach(function (n) {

                // Include only page numbers within the valid page range.
                if (n >= 1 && n <= total) set[n] = true;

            });

            // Convert the collected page numbers into a sorted numerical array.
            var nums = Object.keys(set).map(Number).sort(function (a, b) {

                return a - b;

            });

            // Initialize the final pagination list and previous page tracker.
            var out = [], prev = 0;

            // Build the pagination sequence and insert ellipses where necessary.
            nums.forEach(function (n) {

                // Insert an ellipsis when there is a gap between page numbers.
                if (n - prev > 1) out.push('…');

                // Add the current page number to the pagination sequence.
                out.push(n);

                // Update the previous page number.
                prev = n;

            });

            // Return the completed pagination sequence.
            return out;

        }


        /* ==========================================
           PAGINATION BUTTON CREATOR
           Creates reusable navigation buttons with
           accessibility and interaction attributes.
        ========================================== */

        function btn(label, target, opts) {

            // Use an empty configuration object when no options are provided.
            opts = opts || {};

            // Create a new button element.
            var b = document.createElement('button');

            // Set the button type to prevent unintended form submission.
            b.type = 'button';

            // Assign the default button class and any optional additional class.
            b.className = 'cp-btn' + (opts.cls ? ' ' + opts.cls : '');

            // Set the visible button content.
            b.innerHTML = label;

            // Apply an accessible label when one is provided.
            if (opts.aria) b.setAttribute('aria-label', opts.aria);

            // Mark the button as the currently active page when applicable.
            if (opts.current) b.setAttribute('aria-current', 'page');

            // Disable the button when the supplied configuration requires it.
            if (opts.disabled) b.disabled = true;

            // Attach the page navigation event when the button is enabled.
            else b.addEventListener('click', function () {

                // Navigate to the selected page and enable automatic scrolling.
                go(target, true);

            });

            // Return the completed pagination button.
            return b;

        }


        /* ==========================================
           PAGINATION NAVIGATION RENDERER
           Builds the page information, page numbers,
           and previous/next navigation buttons.
        ========================================== */

        function renderNav() {

            // Clear the existing pagination navigation content.
            nav.innerHTML = '';

            // Calculate the first product number displayed on the current page.
            var from = (page - 1) * PER_PAGE + 1;

            // Calculate the last product number displayed on the current page.
            var to = Math.min(page * PER_PAGE, cards.length);


            /* PRODUCT DISPLAY INFORMATION */

            // Create the element that displays the current product range.
            var info = document.createElement('div');

            // Assign the product information class.
            info.className = 'cp-info';

            // Display the current product range and total product count.
            info.textContent = 'Showing ' + from + '–' + to + ' of ' + cards.length;

            // Append the product information to the pagination navigation.
            nav.appendChild(info);


            /* PAGE NAVIGATION BUTTONS */

            // Create the container for all pagination buttons.
            var row = document.createElement('div');

            // Assign the pagination button row class.
            row.className = 'cp-row';

            // Add the previous page button and disable it on the first page.
            row.appendChild(btn('&lsaquo; Prev', page - 1, { cls: 'cp-edge', aria: 'Previous page', disabled: page === 1 }));

            // Generate and append the visible page numbers.
            pageList().forEach(function (n) {

                // Check whether the current item represents an ellipsis.
                if (n === '…') {

                    // Create an element for the pagination gap indicator.
                    var gap = document.createElement('span');

                    // Assign the pagination gap class.
                    gap.className = 'cp-gap';

                    // Display the ellipsis character.
                    gap.textContent = '…';

                    // Append the gap indicator to the pagination row.
                    row.appendChild(gap);

                }

                else {

                    // Create and append the corresponding page number button.
                    // Highlight the button when its page number matches the active page.
                    row.appendChild(btn(String(n), n, { current: n === page, aria: 'Page ' + n }));

                }

            });

            // Add the next page button and disable it on the last page.
            row.appendChild(btn('Next &rsaquo;', page + 1, { cls: 'cp-edge', aria: 'Next page', disabled: page === total }));

            // Append the completed pagination button row.
            nav.appendChild(row);

        }


        /* ==========================================
           PAGE NAVIGATION FUNCTION
           Updates the active page, controls product
           visibility, and handles optional scrolling.
        ========================================== */

        function go(n, scroll) {

            // Keep the requested page number within the valid page range.
            page = Math.max(1, Math.min(total, n));

            // Calculate the starting and ending product indexes for the active page.
            var start = (page - 1) * PER_PAGE, end = start + PER_PAGE;

            // Process every product card inside the current grid.
            cards.forEach(function (card, i) {

                // Determine whether the current card belongs to the active page.
                var show = i >= start && i < end;

                // Toggle the hidden class depending on the card's visibility.
                card.classList.toggle('cp-hidden', !show);

                // Apply additional animations and reveal handling to visible cards.
                if (show) {

                    // Remove the pending reveal class from previously hidden cards.
                    // This prevents scroll-reveal from leaving them invisible.
                    card.classList.remove('rv-pending');

                    // Mark the card as revealed and visible.
                    card.classList.add('rv-in');

                    // Remove any previously applied entrance animation class.
                    card.classList.remove('cp-enter');

                    // Force a browser reflow to restart the entrance animation.
                    void card.offsetWidth;

                    // Apply the entrance animation class to the visible card.
                    card.classList.add('cp-enter');

                }

            });

            // Refresh the pagination information and navigation buttons.
            renderNav();


            /* AUTOMATIC SCROLL POSITIONING */

            // Scroll only when the function is called with scrolling enabled.
            if (scroll) {

                // Get the nearest category section or use the product grid itself.
                var holder = grid.closest('.cat-section') || grid;

                // Calculate the category section's position relative to the viewport.
                var top = holder.getBoundingClientRect().top;

                // Adjust the page position when the category section is above the desired offset.
                if (top < 90) {

                    // Smoothly scroll the page to reposition the product section.
                    window.scrollTo({ top: window.pageYOffset + top - 120, behavior: 'smooth' });

                }

            }

        }


        /* ==========================================
           INITIAL PAGE RENDER
           Displays the first page of products when
           pagination is initialized.
        ========================================== */

        // Initialize pagination on page one without triggering automatic scrolling.
        go(1, false);

    }


    /* ==========================================
       DOCUMENT INITIALIZATION
       Ensures pagination starts only after the DOM
       is available and product grids can be accessed.
    ========================================== */

    // Wait for the document to finish loading before initializing pagination.
    if (document.readyState === 'loading') {

        document.addEventListener('DOMContentLoaded', init);

    }

    // Initialize immediately when the document is already loaded.
    else {

        init();

    }

})();
