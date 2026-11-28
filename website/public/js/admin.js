// Admin: rings for new orders, accept / reject from the window, quick status buttons, live board.
(() => {
  const A = window.ADMIN, $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
  const toast = msg => { const t = $('[data-toast]'); t.textContent = msg; t.style.display = 'block'; clearTimeout(t._h); t._h = setTimeout(() => t.style.display = 'none', 3500); };

  // ---- sound: browsers only allow it after a click on the page, so the first click turns it on
  let ctx = null;
  const soundBtn = $('[data-sound]');
  const unlock = () => {
    if (!ctx) { try { ctx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) { return; } }
    if (ctx.state === 'suspended') ctx.resume();
    if (soundBtn) { soundBtn.classList.add('sound-ok'); soundBtn.querySelector('span').textContent = A.t.soundOn; }
  };
  document.addEventListener('click', unlock, {capture: true});
  const beep = () => {
    if (!ctx || ctx.state !== 'running') return;
    const t0 = ctx.currentTime;
    [[880, 0], [1175, .18], [880, .36], [1175, .54]].forEach(([f, d]) => {
      const o = ctx.createOscillator(), g = ctx.createGain();
      o.type = 'triangle'; o.frequency.value = f;
      g.gain.setValueAtTime(0, t0 + d); g.gain.linearRampToValueAtTime(.35, t0 + d + .02); g.gain.exponentialRampToValueAtTime(.001, t0 + d + .16);
      o.connect(g).connect(ctx.destination); o.start(t0 + d); o.stop(t0 + d + .17);
    });
  };

  // ---- change an order's status without leaving the page
  async function setStatus(url, status) {
    const res = await fetch(url, {method: 'PATCH', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': A.csrf}, body: JSON.stringify({status})});
    if (!res.ok) throw new Error(A.t.error);
    return res.json();
  }
  const printTicket = url => { const w = window.open(url + '?auto=1', 'ticket', 'width=420,height=700'); if (!w) location.href = url; };

  // ---- the new-order window
  const ring = $('[data-ring]');
  const later = new Map(); // order id -> time until which it stays quiet
  let shown = null, ringTimer = null, queue = [];
  const title = document.title;

  function show(o) {
    shown = o;
    $('[data-r-num]', ring).textContent = '#' + o.number;
    $('[data-r-sub]', ring).textContent = o.customer + ' · ' + o.phone;
    $('[data-r-meta]', ring).innerHTML = [o.branch, o.fulfillment, o.when].map(v => `<span>${esc(v)}</span>`).join('');
    $('[data-r-items]', ring).innerHTML = o.items.map(i => `<li>${esc(i)}</li>`).join('');
    const n = $('[data-r-notes]', ring); n.hidden = !o.notes; n.textContent = o.notes || '';
    $('[data-r-total]', ring).textContent = o.total;
    ring.classList.add('open');
    beep(); clearInterval(ringTimer); ringTimer = setInterval(beep, 2500);
    let flip = false; clearInterval(show._t); show._t = setInterval(() => { document.title = (flip = !flip) ? '🔔 ' + A.t.newToast.replace(':n', o.number) : title; }, 1000);
  }
  function hide() {
    ring.classList.remove('open'); clearInterval(ringTimer); clearInterval(show._t); document.title = title; shown = null;
    next();
  }
  function next() {
    const now = Date.now();
    const o = queue.find(q => !(later.get(q.id) > now));
    if (o && !shown) show(o);
  }
  async function act(status) {
    if (!shown) return;
    const o = shown;
    if (status === 'cancelled' && !confirm(A.t.rejectConfirm)) return;
    $$('.ft button', ring).forEach(b => b.disabled = true);
    try {
      await setStatus(o.update, status);
      queue = queue.filter(q => q.id !== o.id);
      if (status === 'confirmed' && A.autoPrint) printTicket(o.url + '/ticket');
      hide(); refreshBoard(); poll();
    } catch (e) { toast(e.message); } finally { $$('.ft button', ring).forEach(b => b.disabled = false); }
  }
  $('[data-r-accept]', ring).addEventListener('click', () => act('confirmed'));
  $('[data-r-reject]', ring).addEventListener('click', () => act('cancelled'));
  $('[data-r-later]', ring).addEventListener('click', () => { if (shown) later.set(shown.id, Date.now() + 2 * 60 * 1000); hide(); });

  // ---- polling
  let busy = false;
  async function poll() {
    if (busy) return; busy = true;
    try {
      const res = await fetch(A.poll, {headers: {'Accept': 'application/json'}});
      if (res.status === 401 || res.redirected) { location.reload(); return; }
      const d = await res.json();
      $$('[data-new-badge]').forEach(b => { b.textContent = d.new; d.new ? b.removeAttribute('data-zero') : b.setAttribute('data-zero', ''); });
      queue = d.orders;
      if (shown && !queue.some(q => q.id === shown.id)) hide(); // handled on another screen
      next();
    } catch (e) { /* offline for a moment: try again on the next tick */ } finally { busy = false; }
  }
  poll(); setInterval(poll, 12000);

  // ---- live board: reload the columns every 15 seconds and after each action
  const board = $('[data-board]');
  async function refreshBoard() {
    if (!board) return;
    try {
      const res = await fetch(board.dataset.board, {headers: {'Accept': 'text/html'}});
      if (res.ok) board.innerHTML = await res.text();
    } catch (e) {}
  }
  if (board) setInterval(refreshBoard, 15000);

  // quick buttons on cards and rows: <button data-step="url" data-status="preparing">
  document.addEventListener('click', async e => {
    const b = e.target.closest('[data-step]');
    if (b) {
      e.preventDefault(); e.stopPropagation();
      if (b.dataset.status === 'cancelled' && !confirm(b.dataset.confirm || A.t.rejectConfirm)) return;
      b.disabled = true;
      try {
        await setStatus(b.dataset.step, b.dataset.status);
        if (b.dataset.status === 'confirmed' && A.autoPrint && b.dataset.ticket) printTicket(b.dataset.ticket);
        board ? (refreshBoard(), poll()) : location.reload();
      } catch (ex) { toast(ex.message); b.disabled = false; }
      return;
    }
    const p = e.target.closest('[data-print]');
    if (p) { e.preventDefault(); printTicket(p.dataset.print); return; }
    // whole table row opens the order
    const row = e.target.closest('tr[data-href]');
    if (row && !e.target.closest('a,button,form')) location.href = row.dataset.href;
  });
})();
