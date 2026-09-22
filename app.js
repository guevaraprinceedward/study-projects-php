/**
 * app.js
 * -------------------------------------------------------------------------
 * UPDATED: addToCart(id) used to do window.location = "cart_handler.php?add="+id,
 * which reloaded the whole page. cart_handler.php already returns JSON, so
 * this now calls it via fetch() instead — no reload, matches the smoother
 * feel used across index.php / menu.php (theme.js).
 *
 * Kept as a global function so any existing onclick="addToCart(id)" markup
 * elsewhere in the system keeps working without edits.
 */
function addToCart(id) {
    fetch("cart_handler.php?add=" + encodeURIComponent(id), { credentials: "same-origin" })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                var badge = document.querySelector("[data-cart-badge]");
                if (badge) {
                    badge.textContent = data.count;
                    badge.classList.remove("bump");
                    void badge.offsetWidth;
                    badge.classList.add("bump");
                }
            } else {
                alert(data.message || "Hindi na-add sa cart.");
            }
        })
        .catch(function () {
            alert("Network error — subukan ulit.");
        });
}