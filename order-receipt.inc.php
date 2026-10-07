<?php
/**
 * order-receipt.inc.php — receipt modal (CSS + markup + JS) for serve orders.
 * Place before </body> in checkout.php and orders.php, after defining
 *     var ORD_LOGGED_IN = true|false;
 * Then call:  openOrderReceipt(data)  or  openOrderReceipt(data, {fresh:true})
 * `data` comes from ordReceiptData() in order-lib.php.
 * Uses the theme CSS variables the pages already define.
 */
?>
<style>
#ordModal{position:fixed;inset:0;z-index:650;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.78);backdrop-filter:blur(6px);opacity:0;pointer-events:none;transition:opacity .3s;padding:16px}
#ordModal.show{opacity:1;pointer-events:all}
.ord-card{position:relative;background:var(--card);border:1px solid var(--gold-dim);border-radius:8px;padding:30px 28px 24px;max-width:420px;width:100%;max-height:92vh;overflow-y:auto;box-shadow:0 30px 80px rgba(0,0,0,.65);transform:translateY(20px) scale(.98);transition:transform .35s cubic-bezier(.34,1.56,.64,1)}
#ordModal.show .ord-card{transform:none}
.ord-close{position:absolute;top:12px;right:12px;width:28px;height:28px;border:1px solid var(--border);border-radius:50%;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center}
.ord-close:hover{border-color:var(--gold-dim);color:var(--cream)}
.ord-head{text-align:center;margin-bottom:14px}
.ord-brand{font-family:'Cormorant Garamond',serif;font-size:21px;color:var(--gold)}
.ord-title{font-family:'Cormorant Garamond',serif;font-size:25px;font-weight:700;color:var(--cream);margin-top:6px;line-height:1.15}
.ord-code{font-size:11px;color:var(--gold-dim);letter-spacing:.14em;margin-top:4px;text-transform:uppercase}
.ord-stamp{display:inline-flex;align-items:center;gap:7px;margin-top:12px;padding:5px 14px;border:1.5px solid currentColor;border-radius:4px;font-size:11px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;transform:rotate(-3deg)}
.ord-stamp i{width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block}
.ord-meta{display:grid;grid-template-columns:1fr 1fr;gap:10px 14px;padding:14px 0;border-top:1px dashed var(--border);font-size:12.5px}
.ord-meta .k{display:block;font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin-bottom:2px}
.ord-meta .v{color:var(--cream);word-break:break-word}
.ord-meta .full{grid-column:1/-1}
.ord-body{border-top:1px dashed var(--border);border-bottom:1px dashed var(--border);padding:10px 0;margin-bottom:12px}
.ord-line{display:flex;justify-content:space-between;gap:10px;font-size:12.5px;color:var(--text);padding:4px 0 0}
.ord-sub{font-size:10.5px;color:var(--gold-dim);padding:0 0 4px 10px;font-style:italic}
.ord-row{display:flex;justify-content:space-between;font-size:12.5px;color:var(--muted);padding:3px 0}
.ord-total{display:flex;justify-content:space-between;font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--gold);font-weight:700;margin-top:4px}
.ord-pay{margin-top:12px;padding:10px 12px;border-radius:4px;font-size:12px;line-height:1.6;text-align:center;border:1px solid var(--border);background:var(--surface);color:var(--muted)}
.ord-pay strong{color:var(--cream)}
.ord-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:16px}
.ord-btn{display:inline-flex;align-items:center;padding:11px 20px;border-radius:3px;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;text-decoration:none;cursor:pointer}
.ord-btn.primary{background:var(--green);border:none;color:#fff}.ord-btn.primary:hover{background:var(--green-lt)}
.ord-btn.ghost{background:transparent;border:1px solid var(--border);color:var(--muted)}.ord-btn.ghost:hover{border-color:var(--gold-dim);color:var(--gold)}
.ord-foot{text-align:center;font-size:11px;color:var(--muted);margin-top:14px;line-height:1.6}
</style>

<div id="ordModal" role="dialog" aria-modal="true" aria-labelledby="ordTitle" onclick="if(event.target===this)closeOrderReceipt()">
    <div class="ord-card">
        <button type="button" class="ord-close" onclick="closeOrderReceipt()" aria-label="Close receipt">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="ord-head">
            <div class="ord-brand">SIPPERÉ Café</div>
            <div class="ord-title" id="ordTitle">Order receipt</div>
            <div class="ord-code" id="ordCode"></div>
            <div class="ord-stamp" id="ordStamp"><i></i><span id="ordStampText"></span></div>
        </div>
        <div class="ord-meta" id="ordMeta"></div>
        <div class="ord-body" id="ordBody"></div>
        <div id="ordSums"></div>
        <div class="ord-pay" id="ordPay"></div>
        <div class="ord-actions" id="ordActions"></div>
        <div class="ord-foot" id="ordFoot"></div>
    </div>
</div>

<script>
function ordEsc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
function ordPeso(v){return '₱'+Number(v).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})}
function openOrderReceipt(d, opts){
    if(!d) return;
    opts = opts || {};
    var $ = function(i){return document.getElementById(i)};
    $('ordTitle').textContent = opts.fresh ? 'Thank you! Order placed' : 'Order receipt';
    $('ordCode').textContent = d.code;
    $('ordStamp').style.color = d.status_color;
    $('ordStampText').textContent = d.status_label;

    var m = '<div><span class="k">Name</span><span class="v">'+ordEsc(d.name)+'</span></div>'
          + '<div><span class="k">Placed</span><span class="v">'+ordEsc(d.created)+'</span></div>';
    if(d.service_label) m += '<div><span class="k">Order type</span><span class="v">'+ordEsc(d.service_label)+'</span></div>';
    if(d.branch)        m += '<div><span class="k">Branch</span><span class="v">'+ordEsc(d.branch)+'</span></div>';
    if(d.service==='delivery'){
        m += '<div class="full"><span class="k">Deliver to</span><span class="v">'+ordEsc(d.address)+'</span></div>';
        if(d.phone)     m += '<div><span class="k">Contact</span><span class="v">'+ordEsc(d.phone)+'</span></div>';
        if(d.scheduled) m += '<div><span class="k">Scheduled for</span><span class="v">'+ordEsc(d.scheduled)+'</span></div>';
    }
    if(d.notes) m += '<div class="full"><span class="k">Notes</span><span class="v">'+ordEsc(d.notes)+'</span></div>';
    $('ordMeta').innerHTML = m;

    var b = '';
    d.items.forEach(function(it){
        b += '<div class="ord-line"><span>'+ordEsc(it.name)+' ×'+it.qty+'</span><span>'+ordPeso(it.line)+'</span></div>';
        var sub = [];
        if(it.spec) sub.push(it.spec);
        if(it.addons && it.addons.length) sub.push('+ '+it.addons.join(', '));
        if(sub.length) b += '<div class="ord-sub">'+ordEsc(sub.join(' · '))+'</div>';
        if(it.note) b += '<div class="ord-sub">“'+ordEsc(it.note)+'”</div>';
    });
    $('ordBody').innerHTML = b || '<div class="ord-sub">No item details available.</div>';

    var s = '';
    if(d.fee > 0) s += '<div class="ord-row"><span>Subtotal</span><span>'+ordPeso(d.subtotal)+'</span></div><div class="ord-row"><span>Delivery fee</span><span>'+ordPeso(d.fee)+'</span></div>';
    s += '<div class="ord-total"><span>Total</span><span>'+ordPeso(d.total)+'</span></div>';
    $('ordSums').innerHTML = s;

    var p = '<strong>'+ordEsc(d.pay_label)+'</strong>';
    if(d.pay_ref) p += ' · '+ordEsc(d.pay_ref);
    if(d.tendered != null){ p += ' · cash '+ordPeso(d.tendered); if(d.change>0) p += ' · change '+ordPeso(d.change); }
    $('ordPay').innerHTML = p;

    var a = '';
    if(typeof ORD_LOGGED_IN !== 'undefined' && ORD_LOGGED_IN) a += '<a class="ord-btn primary" href="orders.php">My Orders</a>';
    if(d.track_url) a += '<a class="ord-btn '+((typeof ORD_LOGGED_IN!=='undefined'&&ORD_LOGGED_IN)?'ghost':'primary')+'" href="'+ordEsc(d.track_url)+'">Track order</a>';
    a += '<button type="button" class="ord-btn ghost" onclick="closeOrderReceipt()">Close</button>';
    $('ordActions').innerHTML = a;
    $('ordFoot').textContent = (d.service==='delivery') ? 'Keep this tracking link. The staff will approve your delivery and tell you how long it will take.' : 'Please keep this receipt. Thank you for choosing us ☕';
    $('ordModal').classList.add('show');
}
function closeOrderReceipt(){ document.getElementById('ordModal').classList.remove('show'); }
document.addEventListener('keydown',function(e){ if(e.key==='Escape') closeOrderReceipt(); });
</script>