
/* ==========================================================================
   cops-ui.js — shared behaviours for the COPS pages
   Exposes window.COPS = { toast, money, esc, reduced }
   ========================================================================== */

(function () {

    'use strict';


    /* ==========================================
       CONSTANTS AND GLOBAL SETTINGS
       Defines shared variables and browser preferences.
    ========================================== */

    // SVG namespace used when creating SVG elements.
    var NS = 'http://www.w3.org/2000/svg';

    // Detect whether the user prefers reduced motion.
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Detect whether the device supports precise mouse or pointer hover.
    var fine = window.matchMedia('(hover:hover) and (pointer:fine)').matches;


    /* ==========================================
       HELPER FUNCTIONS
       Provides reusable utility functions for the COPS pages.
    ========================================== */


    /* HTML ESCAPE
       Converts special HTML characters into safe entities
       to help prevent HTML injection when displaying text.
    */

    function esc(s) {

        // Convert the supplied value into a string and replace special characters.
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {

            // Map special characters to their corresponding HTML entities.
            return {

                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'

            }

            [c];

        }

        );

    }


    /* CURRENCY FORMATTER
       Converts numerical values into Philippine Peso currency format.
    */

    function money(n) {

        // Format the number using the Philippine locale with two decimal places.
        return '₱' + Number(n).toLocaleString('en-PH', {

            minimumFractionDigits: 2,
            maximumFractionDigits: 2

        });

    }


    /* TOAST NOTIFICATION
       Displays temporary notification messages on the page.
    */

    function toast(msg, type, ms) {

        // Get the existing notification container.
        var host = document.getElementById('toastHost');

        // Create the notification container if it does not exist.
        if (!host) {

            host = document.createElement('div');

            host.id = 'toastHost';

            // Add accessibility support for announcing notifications.
            host.setAttribute('aria-live', 'polite');

            // Append the notification container to the document.
            document.body.appendChild(host);

        }

        // Create a new toast notification element.
        var t = document.createElement('div');

        // Assign the default toast class and optional notification type.
        t.className = 'toast' + (type ? ' ' + type : '');

        // Insert the notification message as plain text.
        t.textContent = msg;

        // Display the notification inside the toast container.
        host.appendChild(t);

        // Start the dismissal process after the specified duration.
        setTimeout(function () {

            // Add the exit animation class.
            t.classList.add('out');

            // Remove the notification after the exit animation.
            setTimeout(function () {

                t.remove();

            }, 320);

        }, ms || 3200);

    }


    /* ==========================================
       SHARED SVG GRADIENT DEFINITIONS
       Injects reusable gold gradients into the document.
    ========================================== */

    function injectDefs() {

        // Prevent duplicate SVG gradient definitions.
        if (document.getElementById('copsDefs')) return;

        // Create the hidden SVG container.
        var s = document.createElementNS(NS, 'svg');

        // Assign the SVG container identifier and dimensions.
        s.setAttribute('id', 'copsDefs');

        s.setAttribute('width', '0');

        s.setAttribute('height', '0');

        // Position the SVG container outside the visible layout.
        s.setAttribute('style', 'position:absolute');

        // Hide the SVG container from assistive technologies.
        s.setAttribute('aria-hidden', 'true');

        s.setAttribute('focusable', 'false');

        // Define reusable gold gradient styles.
        s.innerHTML = '<defs>' +
            '<linearGradient id="gG" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f0d78e"/><stop offset=".5" stop-color="#c9a24a"/><stop offset="1" stop-color="#8a6a1f"/></linearGradient>' +
            '<linearGradient id="gD" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#c9a24a"/><stop offset="1" stop-color="#6e5318"/></linearGradient>' +
            '</defs>';

        // Insert the SVG definitions at the beginning of the document body.
        document.body.insertBefore(s, document.body.firstChild);

    }


    /* ==========================================
       LAUREL WREATH GENERATOR
       Creates decorative mirrored laurel branches
       inside every SVG group containing [data-wreath].
    ========================================== */


    /* SVG ELEMENT CREATOR
       Creates an SVG element and applies its attributes.
    */

    function el(n, a) {

        // Create an SVG element using the specified element name.
        var e = document.createElementNS(NS, n);

        // Apply all supplied attributes to the SVG element.
        for (var k in a) e.setAttribute(k, a[k]);

        // Return the completed SVG element.
        return e;

    }


    /* WREATH BUILDER
       Generates two mirrored branches with decorative leaves.
    */

    function wreath(g, R, pairs) {

        // Calculate the width and height of each decorative leaf.
        var rx = R * .23, ry = R * .095;

        // Generate the left and right branches of the wreath.
        [-1, 1].forEach(function (s) {

            // Initialize the SVG path data for the branch.
            var d = '';

            // Generate the curved branch path using 25 coordinate points.
            for (var i = 0; i <= 24; i++) {

                // Calculate the current angle in radians.
                var th = (16 + (152 - 16) * i / 24) * Math.PI / 180;

                // Build the SVG path using move-to and line-to commands.
                d += (i ? 'L' : 'M') + (s * R * Math.sin(th)).toFixed(2) + ' ' + (R * Math.cos(th)).toFixed(2);

            }

            // Append the main branch path to the SVG group.
            g.appendChild(el('path', { d: d, fill: 'none', stroke: '#8a6a1f', 'stroke-width': Math.max(.5, R * .045), 'stroke-linecap': 'round' }));

            // Generate the decorative leaf pairs along the branch.
            for (var j = 0; j < pairs; j++) {

                // Calculate the position of each leaf pair along the branch.
                var t2 = (22 + (150 - 22) * j / (pairs - 1)) * Math.PI / 180;

                // Calculate the horizontal and vertical coordinates.
                var px = s * R * Math.sin(t2), py = R * Math.cos(t2);

                // Calculate the rotation angle used for the leaves.
                var tan = Math.atan2(-Math.sin(t2), s * Math.cos(t2)) * 180 / Math.PI;

                // Generate both leaves of the current pair.
                [1, -1].forEach(function (o) {

                    // Calculate the center coordinates of the current leaf.
                    var cx = px + s * Math.sin(t2) * o * R * .1, cy = py + Math.cos(t2) * o * R * .1;

                    // Create and append the decorative SVG ellipse.
                    g.appendChild(el('ellipse', {

                        // Set the leaf position and dimensions.
                        cx: cx.toFixed(2), cy: cy.toFixed(2), rx: rx.toFixed(2), ry: ry.toFixed(2),

                        // Apply rotation and the shared gold gradient.
                        transform: 'rotate(' + (tan + o * s * 34).toFixed(1) + ' ' + cx.toFixed(2) + ' ' + cy.toFixed(2) + ')', fill: 'url(#gG)'

                    }));

                });

            }

            // Add a decorative circular detail near the end of each branch.
            g.appendChild(el('circle', { cx: (s * R * Math.sin(.2)).toFixed(2), cy: (R * Math.cos(.2) + R * .1).toFixed(2), r: (R * .07).toFixed(2), fill: '#d9b45a' }));

        });

    }


    /* ==========================================
       INITIALIZATION FUNCTION
       Runs all shared COPS interface behaviours.
    ========================================== */

    function init() {

        // Inject the shared SVG gradient definitions.
        injectDefs();


        /* ==========================================
           LAUREL WREATH INITIALIZATION
           Finds all wreath containers and generates
           their decorative SVG branches and leaves.
        ========================================== */

        document.querySelectorAll('[data-wreath]').forEach(function (g) {

            // Read the wreath size and leaf-pair count from HTML attributes.
            wreath(g, parseFloat(g.getAttribute('data-wreath')), parseInt(g.getAttribute('data-pairs'), 10) || 7);

        });


        /* ==========================================
           HEADER AND MOBILE NAVIGATION
           Adds a scroll-based header background and
           controls the mobile navigation menu.
        ========================================== */

        // Get the main website header.
        var header = document.querySelector('.site-header');

        // Initialize header behaviours only when the header exists.
        if (header) {

            // Toggle the scrolled class depending on the current scroll position.
            var f = function () {

                header.classList.toggle('scrolled', window.scrollY > 24);

            };

            // Listen for page scrolling and update the header appearance.
            window.addEventListener('scroll', f, { passive: true });

            // Apply the initial header state immediately.
            f();

            // Get the mobile navigation toggle button.
            var tg = header.querySelector('.nav-toggle');

            // Initialize mobile navigation only when the toggle button exists.
            if (tg) {

                // Toggle the mobile navigation when the button is clicked.
                tg.addEventListener('click', function () {

                    // Add or remove the navigation open state.
                    var open = header.classList.toggle('nav-open');

                    // Update the accessibility state of the navigation button.
                    tg.setAttribute('aria-expanded', open ? 'true' : 'false');

                });

                // Close the mobile navigation when a navigation link is clicked.
                header.querySelectorAll('.main-nav a').forEach(function (a) {

                    a.addEventListener('click', function () {

                        header.classList.remove('nav-open');

                    });

                });

            }

        }


        /* ==========================================
           SCROLL REVEAL ANIMATION
           Reveals elements when they enter the viewport.
           Respects the user's reduced-motion preference.
        ========================================== */

        // Select all elements marked for scroll reveal.
        var rv = document.querySelectorAll('[data-reveal]');

        // Display all reveal elements immediately when reduced motion is enabled
        // or when IntersectionObserver is not supported.
        if (reduced || !('IntersectionObserver' in window)) {

            rv.forEach(function (e) {

                e.classList.add('in');

            });

        }

        else {

            // Create an observer to detect when elements enter the viewport.
            var io = new IntersectionObserver(function (es) {

                // Process each observed element.
                es.forEach(function (en) {

                    // Reveal the element once it becomes visible.
                    if (en.isIntersecting) {

                        en.target.classList.add('in');

                        // Stop observing the element after it has been revealed.
                        io.unobserve(en.target);

                    }

                });

            }, { threshold: .15, rootMargin: '0px 0px -50px 0px' });

            // Register all reveal elements with the observer.
            rv.forEach(function (e) {

                io.observe(e);

            });

        }


        /* ==========================================
           ANIMATED COUNTERS
           Counts numerical values when their elements
           become visible within the viewport.
        ========================================== */

        // Select all elements containing numerical counters.
        var cs = document.querySelectorAll('[data-count]');

        // Initialize the counter animation only when counter elements exist.
        if (cs.length) {

            // Observe when counter elements enter the viewport.
            var cio = new IntersectionObserver(function (es) {

                // Process each observed counter element.
                es.forEach(function (en) {

                    // Ignore elements that have not entered the viewport.
                    if (!en.isIntersecting) return;

                    // Get the current element and its target numerical value.
                    var e = en.target, target = parseInt(e.dataset.count, 10) || 0;

                    // Display the final number immediately when reduced motion is enabled.
                    if (reduced) {

                        e.textContent = target.toLocaleString();

                        cio.unobserve(e);

                        return;

                    }

                    // Record the animation start time and duration.
                    var t0 = performance.now(), dur = 1400;

                    // Animate the counter using a smooth easing function.
                    (function step(now) {

                        // Calculate animation progress and apply cubic easing.
                        var p = Math.min(1, (now - t0) / dur), eased = 1 - Math.pow(1 - p, 3);

                        // Update the displayed counter value.
                        e.textContent = Math.floor(eased * target).toLocaleString();

                        // Continue the animation until the target value is reached.
                        if (p < 1) requestAnimationFrame(step);

                    })(t0);

                    // Stop observing the counter after its animation starts.
                    cio.unobserve(e);

                });

            }, { threshold: .6 });

            // Register all counter elements with the observer.
            cs.forEach(function (e) {

                cio.observe(e);

            });

        }


        /* ==========================================
           CARD TILT AND LIGHT GLARE EFFECT
           Adds interactive 3D movement to cards when
           the user moves the mouse over them.
        ========================================== */

        // Enable interactive effects only on precise pointer devices
        // and when reduced motion is not enabled.
        if (fine && !reduced) {

            // Select all elements marked for the tilt effect.
            document.querySelectorAll('[data-tilt]').forEach(function (card) {

                // Track mouse movement over the card.
                card.addEventListener('mousemove', function (e) {

                    // Get the card's current position and dimensions.
                    var r = card.getBoundingClientRect();

                    // Calculate the mouse position relative to the card.
                    var px = (e.clientX - r.left) / r.width, py = (e.clientY - r.top) / r.height;

                    // Add the class used for the active tilt effect.
                    card.classList.add('tilting');

                    // Update the horizontal and vertical light glare positions.
                    card.style.setProperty('--gx', (px * 100) + '%');

                    card.style.setProperty('--gy', (py * 100) + '%');

                    // Apply the perspective rotation based on mouse position.
                    card.style.transform = 'perspective(900px) rotateY(' + ((px - .5) * 8).toFixed(2) + 'deg) rotateX(' + ((.5 - py) * 8).toFixed(2) + 'deg) translateY(-4px)';

                });

                // Reset the card appearance when the mouse leaves.
                card.addEventListener('mouseleave', function () {

                    // Remove the active tilt class.
                    card.classList.remove('tilting');

                    // Clear the inline transform style.
                    card.style.transform = '';

                });

            });


            /* ==========================================
               MOUSE PARALLAX EFFECT
               Moves individual scene layers according
               to their assigned data-depth attributes.
            ========================================== */

            // Select all elements marked for mouse parallax.
            document.querySelectorAll('[data-parallax]').forEach(function (scene) {

                // Collect all child elements with individual depth settings.
                var layers = Array.prototype.slice.call(scene.querySelectorAll('[data-depth]'));

                // Initialize target and current mouse positions.
                var tx = 0, ty = 0, cx = 0, cy = 0, raf = null;

                // Smoothly animate the movement of all parallax layers.
                function loop() {

                    // Gradually move the current coordinates toward the target coordinates.
                    cx += (tx - cx) * .08;

                    cy += (ty - cy) * .08;

                    // Apply individual movement based on each layer's depth value.
                    layers.forEach(function (l) {

                        // Read the movement depth assigned to the current layer.
                        var d = parseFloat(l.dataset.depth) || 0;

                        // Apply the calculated 3D translation to the layer.
                        l.style.transform = 'translate3d(' + (cx * d).toFixed(2) + 'px,' + (cy * d * .6).toFixed(2) + 'px,0)';

                    });

                    // Continue animation while the current position has not reached its target.
                    // Stop requesting animation frames when the movement is complete.
                    raf = (Math.abs(tx - cx) > .001 || Math.abs(ty - cy) > .001) ? requestAnimationFrame(loop) : null;

                }

                // Update the parallax target position when the mouse moves over the scene.
                scene.addEventListener('mousemove', function (e) {

                    // Get the current dimensions and position of the scene.
                    var r = scene.getBoundingClientRect();

                    // Normalize the mouse coordinates relative to the scene's center.
                    tx = ((e.clientX - r.left) / r.width - .5) * 2;

                    ty = ((e.clientY - r.top) / r.height - .5) * 2;

                    // Start the animation loop when no animation is currently running.
                    if (!raf) raf = requestAnimationFrame(loop);

                });

                // Return all parallax layers toward their original positions.
                scene.addEventListener('mouseleave', function () {

                    // Reset the target coordinates to the center.
                    tx = 0;

                    ty = 0;

                    // Restart the animation loop to smoothly reset the layers.
                    if (!raf) raf = requestAnimationFrame(loop);

                });

            });

        }

    }


    /* ==========================================
       GLOBAL COPS UTILITIES
       Exposes reusable functions and settings through
       the global window.COPS object.
    ========================================== */

    // Make shared utility functions available to other COPS scripts.
    window.COPS = {

        toast: toast,
        money: money,
        esc: esc,
        reduced: reduced

    };


    /* ==========================================
       DOCUMENT INITIALIZATION
       Waits until the DOM is ready before running
       the shared interface behaviours.
    ========================================== */

    // Run initialization after the document has finished loading.
    if (document.readyState === 'loading') {

        document.addEventListener('DOMContentLoaded', init);

    }

    // Initialize immediately when the DOM is already available.
    else {

        init();

    }

})();
