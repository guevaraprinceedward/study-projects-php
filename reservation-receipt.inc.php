<?php
/**
 * reservation-receipt.inc.php  (v2)
 * -------------------------------------------------------------------------
 * Reservation receipt modal card (CSS + markup + JS) shared by:
 *   reservation-cart.php, reservation-checkout.php,
 *   my-reservations.php, reservation-orders.php
 *
 * Place `<?php include 'reservation-receipt.inc.php'; ?>` just before </body>.
 * Then from JS call:
 *
 *   openReservationReceipt(data)                  -> plain receipt view
 *   openReservationReceipt(data, {fresh:true})    -> "Your order has been reserved!"
 *                                                    (unpaid: shows Pick up now / Pick up later)
 *   openReservationReceipt(data, {fresh:true, title:'Payment received'})
 *
 * `data` comes from PHP's resReceiptData() (see reservation-init.php).
 *
 * Relies on the theme CSS variables the pages already define
 * (--card, --border, --gold, --gold-dim, --green, --green-lt, --cream, --muted, --text, --surface).
 */
?>
<style>
#rsvModal{position:fixed;inset:0;z-index:650;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.78);backdrop-filter:blur(6px);opacity:0;pointer-events:none;transition:opacity .3s ease;padding:16px}
#rsvModal.show{opacity:1;pointer-events:all}
.rsv-card{position:relative;background:var(--card);border:1px solid var(--gold-dim);border-radius:8px;padding:30px 28px 26px;max-width:410px;width:100%;max-height:92vh;overflow-y:auto;box-shadow:0 30px 80px rgba(0,0,0,.65);transform:translateY(20px) scale(.98);transition:transform .35s cubic-bezier(.34,1.56,.64,1)}
#rsvModal.show .rsv-card{transform:translateY(0) scale(1)}
.rsv-close{position:absolute;top:12px;right:12px;width:28px;height:28px;border:1px solid var(--border);border-radius:50%;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s}
.rsv-close:hover{border-color:var(--gold-dim);color:var(--cream)}
.rsv-head{text-align:center;margin-bottom:16px}
.rsv-brand{font-family:'Cormorant Garamond',serif;font-size:21px;color:var(--gold)}
.rsv-title{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--cream);line-height:1.15;margin-top:8px}
.rsv-sub{font-size:10.5px;color:var(--muted);letter-spacing:.14em;margin-top:4px;text-transform:uppercase}
.rsv-stamp{display:inline-flex;align-items:center;gap:7px;margin:14px auto 0;padding:5px 14px;border:1.5px solid currentColor;border-radius:4px;font-size:11px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;transform:rotate(-3deg)}
.rsv-stamp i{width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block}
.rsv-meta{display:grid;grid-template-columns:1fr 1fr;gap:10px 14px;padding:14px 0;border-top:1px dashed var(--border);font-size:12.5px}
.rsv-meta .k{display:block;font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin-bottom:2px}
.rsv-meta .v{color:var(--cream);word-break:break-word}
.rsv-meta .full{grid-column:1/-1}
.rsv-body{border-top:1px dashed var(--border);border-bottom:1px dashed var(--border);padding:12px 0;margin-bottom:14px}
.rsv-line{display:flex;justify-content:space-between;gap:10px;font-size:12.5px;color:var(--text);padding:4px 0}
.rsv-line-addon{font-size:10.5px;color:var(--gold-dim);padding:0 0 4px 10px;font-style:italic}
.rsv-total{display:flex;justify-content:space-between;font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--gold);font-weight:700}
.rsv-pay{margin-top:14px;padding:10px 12px;border-radius:4px;font-size:12px;line-height:1.6;text-align:center;border:1px solid var(--border);background:var(--surface);color:var(--muted)}
.rsv-pay strong{color:var(--cream)}
.rsv-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:18px}
.rsv-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border-radius:3px;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;text-decoration:none;cursor:pointer;transition:background .2s,border-color .2s,color .2s}
.rsv-btn.primary{background:var(--green);border:none;color:#fff}
.rsv-btn.primary:hover{background:var(--green-lt)}
.rsv-btn.ghost{background:transparent;border:1px solid var(--border);color:var(--muted)}
.rsv-btn.ghost:hover{border-color:var(--gold-dim);color:var(--gold)}
/* the two pick-up options (shown right after reserving) */
.rsv-choices{display:flex;flex-direction:column;gap:10px;margin-top:18px}
.rsv-choice{display:block;width:100%;text-align:left;background:var(--surface);border:1px solid var(--border);border-radius:4px;padding:14px 16px;color:var(--text);font-family:'Jost',sans-serif;text-decoration:none;cursor:pointer;transition:border-color .2s,background .2s}
.rsv-choice:hover{border-color:var(--gold-dim);background:rgba(201,168,76,.06)}
.rsv-choice.now{border-color:var(--gold-dim);background:rgba(74,122,58,.14)}
.rsv-choice.now:hover{background:rgba(74,122,58,.24)}
.rsv-choice .t{display:block;font-size:14px;font-weight:500;color:var(--cream);margin-bottom:3px}
.rsv-choice .d{display:block;font-size:12px;color:var(--muted);line-height:1.55}
.rsv-foot{text-align:center;font-size:11px;color:var(--muted);margin-top:16px;line-height:1.6}
</style>

<div id="rsvModal" role="dialog" aria-modal="true" aria-labelledby="rsvTitle" onclick="if(event.target===this)closeReservationReceipt()">
    <div class="rsv-card">
        <button type="button" class="rsv-close" onclick="closeReservationReceipt()" aria-label="Close receipt">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="rsv-head">
            <div class="rsv-brand">AyosCoffeeNegosyo</div>
            <div class="rsv-title" id="rsvTitle">Reservation receipt</div>
            <div class="rsv-sub" style="color:var(--gold-dim)" id="rsvCode"></div>
            <div class="rsv-stamp" id="rsvStamp"><i></i><span id="rsvStampText"></span></div>
        </div>
        <div class="rsv-meta">
            <div><span class="k">Name</span><span class="v" id="rsvName"></span></div>
            <div><span class="k">Phone</span><span class="v" id="rsvPhone"></span></div>
            <div><span class="k">Branch</span><span class="v" id="rsvBranch"></span></div>
            <div><span class="k">Party size</span><span class="v" id="rsvParty"></span></div>
            <div class="full"><span class="k">Pick-up date &amp; time</span><span class="v" id="rsvWhen"></span></div>
            <div class="full" id="rsvOrderWrap" style="display:none"><span class="k">Order code</span><span class="v" id="rsvOrder"></span></div>
            <div class="full" id="rsvNotesWrap" style="display:none"><span class="k">Notes</span><span class="v" id="rsvNotes"></span></div>
        </div>
        <div class="rsv-body" id="rsvBody"></div>
        <div class="rsv-total"><span>Total</span><span id="rsvTotal"></span></div>
        <div class="rsv-pay" id="rsvPay"></div>
        <div id="rsvActions"></div>
        <div class="rsv-foot" id="rsvFoot"></div>
    </div>
</div>

<script>
function rsvEsc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
function rsvPeso(v){return '₱'+Number(v).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})}

function openReservationReceipt(d, opts){
    if(!d) return;
    opts = opts || {};
    var fresh = !!opts.fresh;

    document.getElementById('rsvCode').textContent = d.code;
    var stamp = document.getElementById('rsvStamp');
    stamp.style.color = d.status_color;
    document.getElementById('rsvStampText').textContent = d.status_label;
    document.getElementById('rsvName').textContent  = d.name;
    document.getElementById('rsvPhone').textContent = d.phone || '—';
    document.getElementById('rsvBranch').textContent = d.branch + ' Branch';
    document.getElementById('rsvParty').textContent  = d.party + (d.party === 1 ? ' guest' : ' guests');
    document.getElementById('rsvWhen').textContent   = d.date + ' · ' + d.time;

    var ow = document.getElementById('rsvOrderWrap');
    if(d.order_code){ document.getElementById('rsvOrder').textContent = d.order_code; ow.style.display=''; } else { ow.style.display='none'; }
    var nw = document.getElementById('rsvNotesWrap');
    if(d.notes){ document.getElementById('rsvNotes').textContent = d.notes; nw.style.display=''; } else { nw.style.display='none'; }

    var html = '';
    d.items.forEach(function(it){
        html += '<div class="rsv-line"><span>'+rsvEsc(it.name)+' ×'+it.qty+'</span><span>'+rsvPeso(it.subtotal)+'</span></div>';
        if(it.addons && it.addons.length){
            html += '<div class="rsv-line-addon">+ '+it.addons.map(function(a){return rsvEsc(a.name)}).join(', ')+'</div>';
        }
    });
    document.getElementById('rsvBody').innerHTML = html;
    document.getElementById('rsvTotal').textContent = rsvPeso(d.total);

    var isCancelled = d.status_key === 'cancelled';
    var isPaid      = d.payment_status === 'paid';
    var isPicked    = d.status_key === 'completed';

    // heading
    var title = opts.title || (fresh ? 'Your order has been reserved!' : 'Reservation receipt');
    document.getElementById('rsvTitle').textContent = title;

    var pay = document.getElementById('rsvPay'), actions = document.getElementById('rsvActions'), foot = document.getElementById('rsvFoot');
    var act = '';

    if(isCancelled){
        pay.innerHTML = '<strong>This reservation was cancelled.</strong>';
        act = '<div class="rsv-actions"><button type="button" class="rsv-btn ghost" onclick="closeReservationReceipt()">Close</button></div>';
        foot.textContent = '';
    } else if(isPaid){
        var line = '<strong>' + (isPicked ? 'Paid and picked up' : 'Paid') + '</strong> via ' + rsvEsc(d.payment_label);
        if(d.payment_ref)  line += ' · Ref. ' + rsvEsc(d.payment_ref);
        if(d.card_last4)   line += ' · card ending ' + rsvEsc(d.card_last4);
        if(d.cash_tendered != null){
            line += ' · cash ' + rsvPeso(d.cash_tendered);
            if(d.change > 0) line += ' · change ' + rsvPeso(d.change);
        }
        line += isPicked ? '.' : '.<br>Show this receipt at the counter to pick up your order.';
        pay.innerHTML = line;
        act = '<div class="rsv-actions">'
            + '<a class="rsv-btn primary" href="reservation-orders.php#order-'+d.id+'">My reservation orders</a>'
            + '<button type="button" class="rsv-btn ghost" onclick="closeReservationReceipt()">Close</button></div>';
        foot.textContent = 'Thank you for choosing us ☕';
    } else if(fresh){
        // just reserved and not paid yet -> the two pick-up options
        pay.innerHTML = '<strong>Unpaid.</strong> Your items are being held at the counter for you.';
        act = '<div class="rsv-choices">'
            + '<a class="rsv-choice now" href="'+rsvEsc(d.pay_url)+'"><span class="t">Pick up now</span><span class="d">Pay now (cash, GCash, Maya or card) and collect your order at the counter.</span></a>'
            + '<a class="rsv-choice" href="my-reservations.php"><span class="t">Pick up later</span><span class="d">Not yet. Your order stays at the counter until you pick it up. You can pay when you arrive.</span></a>'
            + '</div>';
        foot.textContent = 'Keep your reservation code. You can find this receipt again in My Reservations.';
    } else {
        pay.innerHTML = '<strong>Unpaid.</strong> Your items are held at the counter. Pick up and pay now, or pay when you arrive.';
        act = '<div class="rsv-actions">'
            + '<a class="rsv-btn primary" href="'+rsvEsc(d.pay_url)+'">Pick up now</a>'
            + '<button type="button" class="rsv-btn ghost" onclick="closeReservationReceipt()">Close</button></div>';
        foot.textContent = 'Your items stay at the counter until you pick them up.';
    }
    actions.innerHTML = act;

    document.getElementById('rsvModal').classList.add('show');
}
function closeReservationReceipt(){ document.getElementById('rsvModal').classList.remove('show'); }
document.addEventListener('keydown',function(e){ if(e.key==='Escape') closeReservationReceipt(); });
</script>