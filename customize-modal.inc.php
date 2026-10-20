<?php
/**
 * customize-modal.inc.php  (v4 — premium redesign)
 * Product customization modal: Size / Hot-Iced / Sugar / other option groups /
 * add-ons / note / quantity / Dine-in-Take-out-Pick-up-Delivery.
 *
 * Put before </body> in menu.php and reservation-menu.php:
 *     <?php $czMode = 'serve'; include 'customize-modal.inc.php'; ?>     // menu.php
 *     <?php $czMode = 'res';   include 'customize-modal.inc.php'; ?>     // reservation-menu.php
 *
 * Then from JS:   openCustomize(productId, { onAdded: function(data){ ... } })
 * Uses the theme CSS variables the pages already define.
 */
include_once 'customize-lib.php';
$czMode  = $czMode ?? 'serve';
$czTypes = custOrderTypes();
if ($czMode === 'res') unset($czTypes['delivery']);
$czSel = $_SESSION[$czMode === 'res' ? 'res_svc_type' : 'svc_type'] ?? 'dine-in';
if (!isset($czTypes[$czSel])) $czSel = 'dine-in';
$czSub = ['dine-in' => 'Eat at the branch', 'take-out' => 'Packed to go', 'pick-up' => 'Pick up at the counter', 'delivery' => 'Brought to you'];
?>
<style>
/* ══ MODAL SHELL ══ */
#czModal{position:fixed;inset:0;z-index:600;display:flex;align-items:center;justify-content:center;background:rgba(4,4,3,.82);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);opacity:0;pointer-events:none;transition:opacity .3s;padding:16px}
#czModal.show{opacity:1;pointer-events:all}
.cz-card{position:relative;background:linear-gradient(180deg,#1c1c17 0%,#16160f 100%);border:1px solid rgba(138,111,46,.55);border-radius:8px;width:100%;max-width:500px;max-height:92vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 40px 90px rgba(0,0,0,.65),0 0 0 1px rgba(201,168,76,.05);transform:translateY(22px) scale(.985);transition:transform .38s cubic-bezier(.2,.8,.3,1)}
#czModal.show .cz-card{transform:none}
.cz-card::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--gold),transparent);z-index:3}
.cz-close{position:absolute;top:14px;right:14px;z-index:4;width:30px;height:30px;border:1px solid rgba(240,234,216,.18);border-radius:50%;background:rgba(11,11,9,.7);backdrop-filter:blur(6px);color:var(--cream);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s}
.cz-close:hover{color:var(--gold);border-color:var(--gold-dim);transform:rotate(90deg)}
.cz-scroll{overflow-y:auto;padding:28px 28px 10px;counter-reset:czn;scrollbar-width:thin;scrollbar-color:var(--border) transparent}
.cz-scroll::-webkit-scrollbar{width:6px}
.cz-scroll::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}

/* ══ PRODUCT HEADER ══ */
.cz-hero{position:relative;margin:-28px -28px 22px;height:178px;overflow:hidden;background:var(--surface)}
.cz-hero img{width:100%;height:100%;object-fit:cover;display:block}
.cz-hero::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(11,11,9,.15) 0%,rgba(22,22,15,.35) 55%,#16160f 100%)}
.cz-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:10px;letter-spacing:.22em;text-transform:uppercase;color:var(--gold);margin-bottom:10px}
.cz-eyebrow::before{content:'';width:22px;height:1px;background:var(--gold-dim)}
.cz-title{font-family:'Cormorant Garamond',serif;font-size:30px;font-weight:700;color:var(--cream);line-height:1.1;letter-spacing:.005em;padding-right:34px}
.cz-desc{font-size:13px;color:var(--muted);line-height:1.7;font-weight:300;margin:10px 0 2px;max-width:92%}
.cz-rule{display:flex;align-items:center;gap:10px;margin:20px 0 2px}
.cz-rule span{flex:1;height:1px;background:linear-gradient(90deg,var(--border),transparent)}
.cz-rule i{width:5px;height:5px;background:var(--gold-dim);transform:rotate(45deg)}

/* ══ SECTIONS ══ */
.cz-sec{margin-top:22px}
.cz-sec h4{display:flex;align-items:baseline;gap:10px;font-size:11px;font-weight:500;letter-spacing:.16em;text-transform:uppercase;color:var(--cream);margin-bottom:11px}
.cz-sec h4::before{counter-increment:czn;content:counter(czn,decimal-leading-zero);font-family:'Cormorant Garamond',serif;font-size:15px;font-weight:600;letter-spacing:.04em;color:var(--gold-dim)}
.cz-sec h4 i{font-style:normal;font-size:10px;letter-spacing:.06em;text-transform:none;color:var(--muted);font-weight:300;margin-left:auto}
.cz-sec h4 i.req{color:var(--gold)}
.cz-opts{display:flex;flex-wrap:wrap;gap:9px}
.cz-opt{position:relative;display:inline-flex;align-items:center;gap:9px;padding:10px 16px;background:var(--surface);border:1px solid var(--border);border-radius:3px;font-size:13px;color:var(--muted);cursor:pointer;user-select:none;transition:all .22s}
.cz-opt:hover{border-color:var(--gold-dim);color:var(--text)}
.cz-opt input{position:absolute;opacity:0;pointer-events:none}
.cz-opt:has(input:checked){border-color:var(--gold);background:linear-gradient(180deg,rgba(201,168,76,.12),rgba(201,168,76,.05));color:var(--cream);box-shadow:inset 0 0 0 1px rgba(201,168,76,.18)}
.cz-opt:has(input:focus-visible){outline:2px solid var(--gold);outline-offset:2px}
.cz-opt small{color:var(--gold-dim);font-family:'Cormorant Garamond',serif;font-size:14.5px;font-weight:600}
.cz-opt:has(input:checked) small{color:var(--gold)}
.cz-opts.col{flex-direction:column}
.cz-opts.col .cz-opt{width:100%}
.cz-opts.col .cz-opt small{margin-left:auto}

/* ══ PREMIUM ADD-ONS ══ */
.cz-addon-meta{margin-left:auto;color:var(--gold);font-weight:500;text-transform:none;letter-spacing:0;font-size:10.5px;white-space:nowrap}
.cz-sec h4:has(.cz-addon-meta) i{margin-left:0}
.cz-addon-card{background:linear-gradient(180deg,rgba(201,168,76,.035),transparent 45%),var(--surface);border:1px solid var(--border);border-radius:6px;overflow:hidden;position:relative}
.cz-addon-card::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--gold-dim),transparent)}
.cz-addon-row{position:relative;display:flex;align-items:center;gap:14px;padding:14px 16px;cursor:pointer;user-select:none;border-bottom:1px solid rgba(44,44,36,.65);transition:background .2s}
.cz-addon-row:last-child{border-bottom:none}
.cz-addon-row:hover{background:rgba(201,168,76,.045)}
.cz-addon-row input{position:absolute;opacity:0;pointer-events:none}
.cz-addon-row.checked{background:rgba(201,168,76,.07)}
.cz-addon-check{width:20px;height:20px;border-radius:50%;border:1.5px solid var(--gold-dim);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .22s cubic-bezier(.34,1.56,.64,1);background:var(--card)}
.cz-addon-check svg{width:11px;height:11px;stroke:#1a1400;opacity:0;transform:scale(.4);transition:all .18s ease}
.cz-addon-row.checked .cz-addon-check{background:var(--gold);border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,168,76,.14)}
.cz-addon-row.checked .cz-addon-check svg{opacity:1;transform:scale(1)}
.cz-addon-name{flex:1;font-size:13.5px;color:var(--text);letter-spacing:.01em;transition:color .2s}
.cz-addon-row.checked .cz-addon-name{color:var(--cream);font-weight:500}
.cz-addon-price{font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--gold-dim);transition:color .2s;flex-shrink:0}
.cz-addon-row.checked .cz-addon-price{color:var(--gold)}
.cz-addon-pager{display:flex;align-items:center;justify-content:center;gap:18px;margin-top:12px}
.cz-addon-pager button{width:28px;height:28px;border-radius:50%;border:1px solid var(--border);background:var(--surface);color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;line-height:1;transition:all .2s}
.cz-addon-pager button:hover:not(:disabled){border-color:var(--gold-dim);color:var(--gold);background:rgba(201,168,76,.06)}
.cz-addon-pager button:disabled{opacity:.3;cursor:not-allowed}
.cz-addon-pager .cz-addon-dots{display:flex;align-items:center;gap:5px}
.cz-addon-pager .cz-addon-dot{width:5px;height:5px;border-radius:50%;background:var(--border);transition:all .25s}
.cz-addon-pager .cz-addon-dot.on{background:var(--gold);width:14px;border-radius:3px}

/* ══ NOTE ══ */
.cz-note{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:3px;color:var(--cream);font-family:'Jost',sans-serif;font-size:13.5px;font-weight:300;padding:12px 14px;outline:none;resize:none;color-scheme:dark;transition:border-color .2s}
.cz-note::placeholder{color:#55554a}
.cz-note:focus{border-color:var(--gold-dim)}

/* ══ FOOTER ══ */
.cz-foot{display:flex;align-items:center;gap:14px;padding:18px 28px 22px;border-top:1px solid var(--border);margin-top:18px;background:rgba(11,11,9,.45)}
.cz-qty{display:flex;align-items:center;border:1px solid var(--border);border-radius:3px;overflow:hidden;flex-shrink:0}
.cz-qty button{width:36px;height:42px;background:var(--surface);border:none;color:var(--muted);font-size:19px;cursor:pointer;transition:all .15s}
.cz-qty button:hover{background:var(--border);color:var(--cream)}
.cz-qty span{min-width:40px;text-align:center;font-weight:600;color:var(--cream);font-size:15px}
.cz-add{flex:1;display:flex;align-items:center;justify-content:center;gap:8px;height:42px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;color:#fff;cursor:pointer;transition:background .2s}
.cz-add:hover:not(:disabled){background:var(--green-lt)}
.cz-add:disabled{opacity:.55;cursor:not-allowed}
.cz-err{display:none;margin:14px 28px 0;background:rgba(139,46,46,.15);border:1px solid #8b2e2e;border-radius:3px;padding:10px 14px;color:#e07b7b;font-size:12.5px}
.cz-err.show{display:block}
.cz-load{padding:70px 0;text-align:center;color:var(--muted);font-size:13px;letter-spacing:.14em;text-transform:uppercase}

@media(max-width:560px){
    #czModal{padding:0;align-items:flex-end}
    .cz-card{max-width:100%;max-height:94vh;border-radius:14px 14px 0 0;border-bottom:none}
    .cz-scroll{padding:24px 20px 6px}
    .cz-hero{margin:-24px -20px 20px;height:150px}
    .cz-title{font-size:26px}
    .cz-foot{padding:14px 20px 20px}
    .cz-err{margin:12px 20px 0}
}
</style>

<div id="czModal" role="dialog" aria-modal="true" aria-labelledby="czTitle">

    <div class="cz-card">

        <button type="button" class="cz-close" id="czClose" aria-label="Close">

            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>

        </button>

        <div class="cz-scroll">

            <div id="czDyn"></div>
                <div class="cz-sec" id="czSvcWrap">

                <h4>How will you have it?</h4>

                    <div class="cz-opts">

                    <?php foreach ($czTypes as $k => $lab): ?>
                        <label class="cz-opt"><input type="radio" name="czsvc" value="<?= htmlspecialchars($k) ?>" <?= $czSel === $k ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($lab) ?></span></label>
                    <?php endforeach; ?>

                    </div>

                </div>

            <div class="cz-sec">

                <h4>Note <i>optional</i></h4>
                <textarea class="cz-note" id="czNote" rows="2" maxlength="120" placeholder="e.g. extra hot, less ice…"></textarea>

            </div>

        </div>

        <div class="cz-err" id="czErr" role="alert"></div>
        <div class="cz-foot">
            <div class="cz-qty"><button type="button" id="czMinus" aria-label="Less">−</button><span id="czQty">1</span><button type="button" id="czPlus" aria-label="More">+</button></div>
            <button type="button" class="cz-add" id="czAdd"></button>
        </div>
    </div>

</div>

<script>
(function(){
    var MODE = <?= json_encode($czMode) ?>;
    var ADDON_PAGE_SIZE = 3;
    var CZ = {cfg:null, qty:1, opt:{}, addonSel:new Set(), addonPage:0};
    function $(i){return document.getElementById(i)}
    function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
    function peso(v){return '₱'+Number(v).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})}
    function err(m){var e=$('czErr');e.textContent=m||'';e.classList.toggle('show',!!m)}

    window.openCustomize = function(id, opt){
        CZ.opt = opt || {}; CZ.qty = 1; CZ.cfg = null; CZ.addonSel = new Set(); CZ.addonPage = 0;
        $('czQty').textContent = 1; $('czNote').value = '';
        err(''); $('czDyn').innerHTML = '<div class="cz-load">Loading…</div>';
        $('czAdd').disabled = true; $('czAdd').textContent = 'Add';
        $('czModal').classList.add('show');
        fetch('customize_handler.php?action=config&id='+encodeURIComponent(id),{credentials:'same-origin'})
          .then(function(r){return r.json()}).then(function(cfg){
            if(cfg.error){ err(cfg.error); $('czDyn').innerHTML=''; return; }
            CZ.cfg = cfg; render(); calc(); $('czAdd').disabled = false;
          }).catch(function(){ err('Could not load this item. Please try again.'); });
    };
    function close(){ $('czModal').classList.remove('show'); }

    function render(){
        var c = CZ.cfg, p = c.product, h = '';
        if(p.image) h += '<div class="cz-hero"><img src="'+esc(p.image)+'" alt="" onerror="this.parentElement.style.display=\'none\'"></div>';
        h += '<div class="cz-eyebrow">'+(MODE==='res'?'Reservation':'Customize your order')+'</div>';
        h += '<div class="cz-title" id="czTitle">'+esc(p.name)+'</div>';
        if(p.description) h += '<div class="cz-desc">'+esc(p.description)+'</div>';
        h += '<div class="cz-rule"><span></span><i></i></div>';

        if(c.sizes.length){
            var hasDef = c.sizes.some(function(s){return +s.is_default===1});
            h += '<div class="cz-sec"><h4>Size <i>optional</i></h4><div class="cz-opts">';
            c.sizes.forEach(function(s,i){
                var on = hasDef ? +s.is_default===1 : i===0;
                h += '<label class="cz-opt"><input type="radio" name="czsize" value="'+s.id+'" data-price="'+s.price+'" '+(on?'checked':'')+'><span>'+esc(s.label)+'</span><small>'+peso(s.price)+'</small></label>';
            });
            h += '</div></div>';
        }
        c.groups.forEach(function(g){
            var multi = g.type==='multi', hasDef = g.choices.some(function(x){return +x.is_default===1});
            var isReq = (+g.is_required===1 && !hasDef);
            var tag = isReq ? 'required' : (multi ? 'optional, pick any' : 'optional');
            h += '<div class="cz-sec" data-gid="'+g.id+'" data-req="'+(isReq?1:0)+'" data-name="'+esc(g.name)+'"><h4>'+esc(g.name)+' <i'+(isReq?' class="req"':'')+'>'+tag+'</i></h4><div class="cz-opts">';
            g.choices.forEach(function(x){
                var d = parseFloat(x.price_delta)||0;
                h += '<label class="cz-opt"><input type="'+(multi?'checkbox':'radio')+'" name="czg'+g.id+'" value="'+x.id+'" data-delta="'+d+'" '+(+x.is_default===1?'checked':'')+'><span>'+esc(x.label)+'</span>'+(d?'<small>'+(d>0?'+':'')+peso(d)+'</small>':'')+'</label>';
            });
            h += '</div></div>';
        });
        if(c.addons.length){
            h += '<div class="cz-sec"><h4>Add-ons <i>optional</i><span class="cz-addon-meta" id="czAddonMeta"></span></h4>'
               +   '<div class="cz-addon-card" id="czAddonList"></div>'
               +   '<div class="cz-addon-pager" id="czAddonPager" style="display:none">'
               +     '<button type="button" id="czAddonPrev" aria-label="Previous add-ons">'
               +       '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>'
               +     '</button>'
               +     '<span class="cz-addon-dots" id="czAddonDots"></span>'
               +     '<button type="button" id="czAddonNext" aria-label="More add-ons">'
               +       '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>'
               +     '</button>'
               +   '</div></div>';
        }
        $('czDyn').innerHTML = h;

        if(c.addons.length){
            renderAddonPage();
            $('czAddonPrev').addEventListener('click', function(){ CZ.addonPage--; renderAddonPage(); });
            $('czAddonNext').addEventListener('click', function(){ CZ.addonPage++; renderAddonPage(); });
        }
    }

    // Renders only the current 3-item page of add-ons, keeping selections made on other
    // pages intact (CZ.addonSel is the single source of truth, not the rendered DOM).
    function renderAddonPage(){
        var c = CZ.cfg, list = $('czAddonList');
        if(!c || !list) return;
        var addons = c.addons, totalPages = Math.max(1, Math.ceil(addons.length / ADDON_PAGE_SIZE));
        if(CZ.addonPage >= totalPages) CZ.addonPage = totalPages - 1;
        if(CZ.addonPage < 0) CZ.addonPage = 0;
        var start = CZ.addonPage * ADDON_PAGE_SIZE, page = addons.slice(start, start + ADDON_PAGE_SIZE);

        var h = '';
        page.forEach(function(a){
            var on = CZ.addonSel.has(String(a.id));
            h += '<label class="cz-addon-row'+(on?' checked':'')+'">'
               +   '<input type="checkbox" name="czaddon" value="'+a.id+'" data-price="'+a.price+'" '+(on?'checked':'')+'>'
               +   '<span class="cz-addon-check"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>'
               +   '<span class="cz-addon-name">'+esc(a.name)+'</span>'
               +   '<span class="cz-addon-price">+'+peso(a.price)+'</span>'
               + '</label>';
        });
        list.innerHTML = h;
        list.querySelectorAll('input[name="czaddon"]').forEach(function(input){
            input.addEventListener('change', function(){
                var id = String(this.value);
                if(this.checked) CZ.addonSel.add(id); else CZ.addonSel.delete(id);
                this.closest('.cz-addon-row').classList.toggle('checked', this.checked);
            });
        });

        var pager = $('czAddonPager'), dots = $('czAddonDots');
        if(totalPages > 1){
            pager.style.display = 'flex';
            var dh = '';
            for(var i=0;i<totalPages;i++) dh += '<span class="cz-addon-dot'+(i===CZ.addonPage?' on':'')+'"></span>';
            dots.innerHTML = dh;
            $('czAddonPrev').disabled = CZ.addonPage === 0;
            $('czAddonNext').disabled = CZ.addonPage === totalPages - 1;
        } else {
            pager.style.display = 'none';
        }
        updateAddonMeta();
    }

    function updateAddonMeta(){
        var meta = $('czAddonMeta');
        if(!meta || !CZ.cfg) return;
        var n = CZ.addonSel.size, sum = 0;
        CZ.cfg.addons.forEach(function(a){ if(CZ.addonSel.has(String(a.id))) sum += parseFloat(a.price)||0; });
        meta.textContent = n ? (n + (n===1?' add-on':' add-ons') + ' · +' + peso(sum)) : '';
    }

    function calc(){
        if(!CZ.cfg) return 0;
        var root = $('czDyn'), unit = parseFloat(CZ.cfg.product.price)||0;
        var sz = root.querySelector('input[name="czsize"]:checked'); if(sz) unit = parseFloat(sz.dataset.price)||0;
        root.querySelectorAll('input[data-delta]:checked').forEach(function(i){unit += parseFloat(i.dataset.delta)||0});
        // Add-ons are summed from the persisted selection set, not the DOM, since only one
        // page of 3 is ever rendered at a time.
        CZ.cfg.addons.forEach(function(a){ if(CZ.addonSel.has(String(a.id))) unit += parseFloat(a.price)||0; });
        var total = unit * CZ.qty;
        $('czAdd').textContent = (MODE==='res' ? 'Reserve' : 'Add to cart') + ' · ' + peso(total);
        updateAddonMeta();
        return unit;
    }

    function submit(){
        if(CZ.busy || !CZ.cfg) return;
        var root = $('czDyn'), options = {}, bad = '';
        root.querySelectorAll('[data-gid]').forEach(function(sec){
            var ids = [].map.call(sec.querySelectorAll('input:checked'), function(i){return parseInt(i.value)});
            if(!ids.length && sec.dataset.req==='1') bad = bad || ('Please choose: '+sec.dataset.name);
            options[sec.dataset.gid] = ids;
        });
        var svc = document.querySelector('input[name="czsvc"]:checked');
        if(bad){ err(bad); return; }
        if(!svc){ err('Please choose how you will have it (Dine-in, Take-out, Pick-up...).'); return; }
        var sz = root.querySelector('input[name="czsize"]:checked');
        var addons = Array.from(CZ.addonSel).map(function(x){return parseInt(x)});
        var b = new URLSearchParams();
        b.set('action','add'); b.set('cart',MODE); b.set('product_id',CZ.cfg.product.id);
        b.set('size_id', sz ? sz.value : 0); b.set('options',JSON.stringify(options)); b.set('addons',JSON.stringify(addons));
        b.set('qty',CZ.qty); b.set('note',$('czNote').value); b.set('service_type',svc.value);
        err(''); CZ.busy = true; $('czAdd').disabled = true;
        fetch('customize_handler.php',{method:'POST',body:b,credentials:'same-origin'})
          .then(function(r){return r.json()}).then(function(d){
            CZ.busy = false; $('czAdd').disabled = false;
            if(d.success){ close(); if(CZ.opt.onAdded) CZ.opt.onAdded(d); }
            else err(d.message || 'Could not add this item.');
          }).catch(function(){ CZ.busy=false; $('czAdd').disabled=false; err('Network error. Please try again.'); });
    }

    $('czDyn').addEventListener('change', function(){ err(''); calc(); });
    $('czMinus').addEventListener('click', function(){ if(CZ.qty>1){CZ.qty--; $('czQty').textContent=CZ.qty; calc();} });
    $('czPlus').addEventListener('click', function(){
        var max = CZ.cfg ? Math.max(1, parseInt(CZ.cfg.product.stock)||1) : 1;
        if(CZ.qty<Math.min(50,max)){CZ.qty++; $('czQty').textContent=CZ.qty; calc();}
    });
    $('czAdd').addEventListener('click', submit);
    $('czClose').addEventListener('click', close);
    $('czModal').addEventListener('click', function(e){ if(e.target===this) close(); });
    document.addEventListener('keydown', function(e){ if(e.key==='Escape') close(); });
})();
</script>
<script>
// Page hooks: include this file at the very END of <body> (after the page's own <script>) and it
// takes over the old Add / Reserve buttons automatically. No other edit in the menu pages needed.
<?php if ($czMode === 'res'): ?>
window.addToReservation = function(id, btn){
    var card = btn.closest('.menu-card');
    openCustomize(id, { onAdded: function(d){
        if (typeof setCartCount === 'function') setCartCount(d.count || 0);
        var stock = Math.max(0, d.stock);
        if (card){ card.dataset.stock = stock; if (typeof updateStockUI === 'function') updateStockUI(card, stock); }
        if (stock <= 0) btn.disabled = true;
        if (typeof showToast === 'function') showToast('Added to your reservation!', false);
    }});
};
<?php else: ?>
window.addToCart = function(id, btn){
    var card = btn.closest('.menu-card');
    openCustomize(id, { onAdded: function(d){
        var c = d.count || 0, e = document.getElementById('cartCount'), sb = document.getElementById('sidebarCartCount');
        if (e) e.textContent = c; if (sb) sb.textContent = c;
        var stock = Math.max(0, d.stock);
        if (card){ card.dataset.stock = stock; if (typeof updateStockUI === 'function') updateStockUI(card, stock); }
        if (stock <= 0) btn.disabled = true;
        if (typeof showToast === 'function') showToast('Added to cart!', false);
    }});
};
<?php endif; ?>
</script>