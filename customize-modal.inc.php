<?php
/**
 * customize-modal.inc.php
 * -------------------------------------------------------------------------
 * The "customize before you add it" modal: size (10oz/16oz/22oz, each its
 * own price), Hot/Iced, Sugar Level, extra add-ons, quantity and a note.
 * All of these come from the DB per-product (product_sizes, option_groups
 * + option_choices via product_option_groups, product_addons) — a product
 * with no rows in a table simply doesn't show that section, so a croissant
 * only shows qty + note, while a Caffe Latte shows everything.
 *
 * Include this ONCE near the end of <body> on any page with an "Add" /
 * "Reserve" button, then call from JS:
 *
 *   openCustomizeModal(productId, {
 *     endpoint: 'cart_handler.php',      // or 'reservation_handler.php'
 *     actionLabel: 'Add to Cart',         // button text
 *     onAdded: function(data){ ... }      // called after a successful add
 *   });
 *
 * `onAdded(data)` receives the same JSON the handler returns
 * ({success, count, stock}). The modal itself shows its own error message
 * on failure (e.g. out of stock) and does not call onAdded in that case.
 *
 * If the host page already defines showBrewOverlay(category)/hideBrewOverlay()
 * and showToast(msg,isError) (menu.php and reservation-menu.php both do),
 * the modal reuses them automatically for a consistent loading/feedback feel.
 */
?>
<style>
#cstmModal{position:fixed;inset:0;z-index:450;display:flex;align-items:flex-end;justify-content:center;background:rgba(0,0,0,.75);backdrop-filter:blur(6px);opacity:0;pointer-events:none;transition:opacity .25s ease}
#cstmModal.show{opacity:1;pointer-events:all}
@media(min-width:640px){#cstmModal{align-items:center}}
.cstm-card{position:relative;background:var(--card);border:1px solid var(--border);border-top:1px solid var(--gold-dim);border-radius:10px 10px 0 0;width:100%;max-width:480px;max-height:92vh;overflow-y:auto;transform:translateY(24px);transition:transform .3s cubic-bezier(.22,.8,.28,1)}
#cstmModal.show .cstm-card{transform:translateY(0)}
@media(min-width:640px){.cstm-card{border-radius:10px;border-top:1px solid var(--border)}}
.cstm-close{position:absolute;top:12px;right:12px;width:30px;height:30px;border:1px solid var(--border);border-radius:50%;background:var(--card);color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:2;transition:all .2s}
.cstm-close:hover{border-color:var(--gold-dim);color:var(--cream)}
.cstm-media{position:relative;height:150px;background:var(--surface);overflow:hidden;flex-shrink:0}
.cstm-media img{width:100%;height:100%;object-fit:cover;display:block}
.cstm-media-fallback{width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--gold-dim);opacity:.5}
.cstm-body{padding:20px 22px 24px}
.cstm-name{font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;color:var(--cream);margin-bottom:4px}
.cstm-desc{font-size:12.5px;color:var(--muted);line-height:1.6;margin-bottom:18px}
.cstm-section{margin-bottom:20px}
.cstm-section-label{display:flex;align-items:baseline;gap:8px;font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);margin-bottom:10px}
.cstm-section-label .opt{font-size:10px;font-weight:400;letter-spacing:.04em;text-transform:none;color:var(--muted)}
.cstm-chip-row{display:flex;flex-wrap:wrap;gap:8px}
.cstm-chip{position:relative;display:flex;flex-direction:column;align-items:center;gap:2px;min-width:72px;padding:10px 14px;background:var(--surface);border:1px solid var(--border);border-radius:6px;cursor:pointer;color:var(--muted);font-size:12.5px;font-weight:500;transition:all .2s;user-select:none;text-align:center}
.cstm-chip input{position:absolute;opacity:0;pointer-events:none}
.cstm-chip .delta{font-size:10.5px;color:var(--gold-dim)}
.cstm-chip:has(input:checked){border-color:var(--gold);background:rgba(201,168,76,.1);color:var(--cream)}
.cstm-chip:has(input:focus-visible){outline:2px solid var(--gold);outline-offset:2px}
.cstm-addon-row{display:flex;align-items:center;gap:10px;padding:8px 2px;font-size:13px;color:var(--text);cursor:pointer}
.cstm-addon-row input{accent-color:var(--gold);width:16px;height:16px;cursor:pointer;flex-shrink:0}
.cstm-addon-row .nm{flex:1}
.cstm-addon-row .pr{color:var(--gold-dim);font-family:'Cormorant Garamond',serif;font-size:14px;font-weight:600}
.cstm-note{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:4px;color:var(--cream);font-family:'Jost',sans-serif;font-size:13px;padding:10px 12px;resize:vertical;min-height:52px;outline:none}
.cstm-note:focus{border-color:var(--gold-dim)}
.cstm-qty-row{display:flex;align-items:center;justify-content:space-between;gap:14px;padding-top:4px;border-top:1px solid var(--border);margin-top:4px}
.cstm-qty-wrap{display:flex;align-items:center;border:1px solid var(--border);border-radius:4px;overflow:hidden}
.cstm-qty-btn{width:38px;height:42px;background:var(--surface);border:none;color:var(--text);font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1}
.cstm-qty-btn:hover{background:var(--border)}
.cstm-qty-val{width:44px;text-align:center;font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:700;color:var(--cream)}
.cstm-total{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--gold)}
.cstm-error{display:none;background:rgba(139,46,46,.15);border:1px solid var(--red);border-radius:3px;padding:10px 14px;color:#e07b7b;font-size:12.5px;margin-top:14px}
.cstm-error.show{display:block}
.cstm-add-btn{display:flex;align-items:center;justify-content:center;gap:9px;width:100%;padding:15px;margin-top:18px;background:var(--green);border:none;border-radius:4px;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:.09em;text-transform:uppercase;color:#fff;cursor:pointer;transition:background .2s}
.cstm-add-btn:hover:not(:disabled){background:var(--green-lt)}
.cstm-add-btn:disabled{opacity:.6;cursor:not-allowed}
.cstm-loading{padding:60px 22px;text-align:center;color:var(--muted);font-size:13px}
.cstm-loading .spin{width:30px;height:30px;margin:0 auto 14px;border:2px solid var(--border);border-top-color:var(--gold);border-radius:50%;animation:cstmspin .8s linear infinite}
@keyframes cstmspin{to{transform:rotate(360deg)}}
</style>

<div id="cstmModal" role="dialog" aria-modal="true" aria-labelledby="cstmName" onclick="if(event.target===this)closeCustomizeModal()">
    <div class="cstm-card">
        <button type="button" class="cstm-close" onclick="closeCustomizeModal()" aria-label="Close">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div id="cstmLoading" class="cstm-loading">
            <div class="spin"></div>Loading options…
        </div>
        <div id="cstmContent" style="display:none">
            <div class="cstm-media" id="cstmMedia"></div>
            <div class="cstm-body">
                <div class="cstm-name" id="cstmName"></div>
                <div class="cstm-desc" id="cstmDesc"></div>

                <div class="cstm-section" id="cstmSizeSection" style="display:none">
                    <div class="cstm-section-label">Size <span class="opt">optional</span></div>
                    <div class="cstm-chip-row" id="cstmSizes"></div>
                </div>

                <div id="cstmGroups"></div>

                <div class="cstm-section" id="cstmAddonSection" style="display:none">
                    <div class="cstm-section-label">Add-ons <span class="opt">optional</span></div>
                    <div id="cstmAddons"></div>
                </div>

                <div class="cstm-section">
                    <div class="cstm-section-label">Note <span class="opt">optional</span></div>
                    <textarea class="cstm-note" id="cstmNote" maxlength="120" placeholder="e.g. less ice, extra hot…"></textarea>
                </div>

                <div class="cstm-qty-row">
                    <div class="cstm-qty-wrap">
                        <button type="button" class="cstm-qty-btn" onclick="cstmChangeQty(-1)">−</button>
                        <div class="cstm-qty-val" id="cstmQty">1</div>
                        <button type="button" class="cstm-qty-btn" onclick="cstmChangeQty(1)">+</button>
                    </div>
                    <div class="cstm-total"><small style="font-family:'Jost',sans-serif;font-size:12px;color:var(--muted)">₱</small><span id="cstmTotal">0.00</span></div>
                </div>

                <div class="cstm-error" id="cstmError"></div>
                <button type="button" class="cstm-add-btn" id="cstmAddBtn" onclick="cstmSubmit()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span id="cstmAddBtnLabel">Add to Cart</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var CSTM = { cfg: null, endpoint: '', onAdded: null, qty: 1 };

function cstmFmt(v){ return Number(v).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function cstmEsc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]}); }

function openCustomizeModal(productId, opts){
    opts = opts || {};
    CSTM.endpoint = opts.endpoint || 'cart_handler.php';
    CSTM.onAdded  = typeof opts.onAdded === 'function' ? opts.onAdded : null;
    CSTM.qty = 1;

    document.getElementById('cstmAddBtnLabel').textContent = opts.actionLabel || 'Add to Cart';
    document.getElementById('cstmLoading').style.display = '';
    document.getElementById('cstmContent').style.display = 'none';
    document.getElementById('cstmError').classList.remove('show');
    document.getElementById('cstmModal').classList.add('show');
    document.body.style.overflow = 'hidden';

    fetch('product_config.php?id=' + encodeURIComponent(productId))
        .then(function(r){ return r.json(); })
        .then(function(data){
            if(!data.success){ closeCustomizeModal(); if(typeof showToast==='function') showToast(data.message||'Could not load this item.', true); return; }
            CSTM.cfg = data;
            cstmRender();
        })
        .catch(function(){ closeCustomizeModal(); if(typeof showToast==='function') showToast('Network error — please try again.', true); });
}

function closeCustomizeModal(){
    document.getElementById('cstmModal').classList.remove('show');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeCustomizeModal(); });

function cstmRender(){
    var d = CSTM.cfg, p = d.product;
    document.getElementById('cstmLoading').style.display = 'none';
    document.getElementById('cstmContent').style.display = '';

    var media = document.getElementById('cstmMedia');
    media.innerHTML = p.image
        ? '<img src="'+cstmEsc(p.image)+'" alt="" onerror="this.parentElement.innerHTML=\'<div class=&quot;cstm-media-fallback&quot;><svg width=&quot;30&quot; height=&quot;30&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;1&quot;><path d=&quot;M3 11l19-9-9 19-2-8-8-2z&quot;/></svg></div>\'">'
        : '<div class="cstm-media-fallback"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg></div>';

    document.getElementById('cstmName').textContent = p.name;
    document.getElementById('cstmDesc').textContent = p.description || '';

    // sizes
    var sizeSec = document.getElementById('cstmSizeSection'), sizeWrap = document.getElementById('cstmSizes');
    if(d.sizes.length){
        sizeSec.style.display = '';
        var defaultSize = d.sizes.find(function(s){return s.is_default}) || d.sizes[0];
        sizeWrap.innerHTML = d.sizes.map(function(s){
            var checked = s.id === defaultSize.id ? 'checked' : '';
            var deltaTxt = '₱' + cstmFmt(s.price);
            return '<label class="cstm-chip"><input type="radio" name="cstmSize" value="'+s.id+'" data-price="'+s.price+'" '+checked+' onchange="cstmRecalc()">'
                 + '<span>'+cstmEsc(s.label)+'</span><span class="delta">'+deltaTxt+'</span></label>';
        }).join('');
    } else { sizeSec.style.display = 'none'; sizeWrap.innerHTML=''; }

    // option groups (Temperature, Sugar Level, etc.) — required=1 but every
    // group here has a default choice, so nothing blocks the customer; they
    // only need to touch it if they want something other than the default.
    var groupsWrap = document.getElementById('cstmGroups');
    groupsWrap.innerHTML = d.groups.map(function(g){
        var isOptional = !g.is_required; // shown as "optional" when it truly has no forced default
        var hasDefault = g.choices.some(function(c){return c.is_default});
        var tag = (isOptional || hasDefault) ? 'optional' : 'choose one';
        var rows = g.choices.map(function(c){
            var checked = c.is_default ? 'checked' : '';
            var inputType = g.type === 'multi' ? 'checkbox' : 'radio';
            var delta = c.price_delta != 0 ? '<span class="delta">'+(c.price_delta>0?'+':'')+'₱'+cstmFmt(c.price_delta)+'</span>' : '';
            return '<label class="cstm-chip"><input type="'+inputType+'" name="cstmGroup'+g.id+'" value="'+c.id+'" data-group="'+g.id+'" data-delta="'+c.price_delta+'" '+checked+' onchange="cstmRecalc()">'
                 + '<span>'+cstmEsc(c.label)+'</span>'+delta+'</label>';
        }).join('');
        return '<div class="cstm-section"><div class="cstm-section-label">'+cstmEsc(g.name)+' <span class="opt">'+tag+'</span></div><div class="cstm-chip-row">'+rows+'</div></div>';
    }).join('');

    // add-ons
    var addonSec = document.getElementById('cstmAddonSection'), addonWrap = document.getElementById('cstmAddons');
    if(d.addons.length){
        addonSec.style.display = '';
        addonWrap.innerHTML = d.addons.map(function(a){
            return '<label class="cstm-addon-row"><input type="checkbox" name="cstmAddon" value="'+a.id+'" data-price="'+a.price+'" onchange="cstmRecalc()">'
                 + '<span class="nm">'+cstmEsc(a.name)+'</span><span class="pr">+₱'+cstmFmt(a.price)+'</span></label>';
        }).join('');
    } else { addonSec.style.display = 'none'; addonWrap.innerHTML=''; }

    document.getElementById('cstmNote').value = '';
    CSTM.qty = 1;
    document.getElementById('cstmQty').textContent = '1';
    cstmRecalc();
}

function cstmChangeQty(delta){
    CSTM.qty = Math.max(1, Math.min(50, CSTM.qty + delta));
    document.getElementById('cstmQty').textContent = CSTM.qty;
    cstmRecalc();
}

function cstmRecalc(){
    var unit = CSTM.cfg.product.price;
    var sizeSel = document.querySelector('input[name="cstmSize"]:checked');
    if(sizeSel) unit = parseFloat(sizeSel.dataset.price);
    document.querySelectorAll('#cstmGroups input:checked').forEach(function(i){ unit += parseFloat(i.dataset.delta || 0); });
    var addonsTotal = 0;
    document.querySelectorAll('input[name="cstmAddon"]:checked').forEach(function(i){ addonsTotal += parseFloat(i.dataset.price || 0); });
    document.getElementById('cstmTotal').textContent = cstmFmt((unit + addonsTotal) * CSTM.qty);
}

function cstmCollect(){
    var options = {};
    CSTM.cfg.groups.forEach(function(g){
        var vals = [].slice.call(document.querySelectorAll('input[data-group="'+g.id+'"]:checked')).map(function(i){return parseInt(i.value)});
        if(vals.length) options[g.id] = vals;
    });
    var addons = [].slice.call(document.querySelectorAll('input[name="cstmAddon"]:checked')).map(function(i){return parseInt(i.value)});
    var sizeSel = document.querySelector('input[name="cstmSize"]:checked');
    return {
        product_id: CSTM.cfg.product.id,
        size_id: sizeSel ? sizeSel.value : 0,
        options: JSON.stringify(options),
        addons: JSON.stringify(addons),
        qty: CSTM.qty,
        note: document.getElementById('cstmNote').value
    };
}

function cstmSubmit(){
    var btn = document.getElementById('cstmAddBtn'), err = document.getElementById('cstmError');
    err.classList.remove('show');
    btn.disabled = true;

    var category = CSTM.cfg.product.category || 'mains';
    if(typeof showBrewOverlay === 'function') showBrewOverlay(category);
    var startedAt = Date.now(), MIN_MS = 700;

    var body = new URLSearchParams(cstmCollect());
    body.append('action', 'add_custom');

    fetch(CSTM.endpoint, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString() })
        .then(function(r){ return r.json(); })
        .then(function(data){
            var wait = Math.max(0, MIN_MS - (Date.now() - startedAt));
            setTimeout(function(){
                if(typeof hideBrewOverlay === 'function') hideBrewOverlay();
                btn.disabled = false;
                if(data.success){
                    closeCustomizeModal();
                    if(CSTM.onAdded) CSTM.onAdded(data);
                } else {
                    err.textContent = data.message || 'Could not add this item.';
                    err.classList.add('show');
                }
            }, wait);
        })
        .catch(function(){
            if(typeof hideBrewOverlay === 'function') hideBrewOverlay();
            btn.disabled = false;
            err.textContent = 'Network error — please try again.';
            err.classList.add('show');
        });
}
</script>