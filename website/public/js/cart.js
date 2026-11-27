(() => {
  const S = window.SITE, $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const money = v => (Math.round(v * 100) / 100).toLocaleString('en-US', {maximumFractionDigits: 2});

  let cart = [];
  try { cart = JSON.parse(localStorage.getItem('cart') || '[]'); } catch (e) {}
  const save = () => { try { localStorage.setItem('cart', JSON.stringify(cart)); } catch (e) {} render(); };
  const count = () => cart.reduce((a, l) => a + l.qty, 0);
  const total = () => cart.reduce((a, l) => a + l.qty * l.price, 0);

  // addons: [{id, name, price}] picked in the sheet (rice type, serving, extras)
  function add(item, option, addons = []) {
    const ids = addons.map(a => a.id).sort((a, b) => a - b);
    const key = item.id + ':' + (option ? option.id : '') + ':' + ids.join(',');
    const ex = cart.find(l => l.key === key);
    if (ex) ex.qty++;
    else cart.push({key, item_id: item.id, option_id: option ? option.id : null, addon_ids: ids, name: item.name,
      option: option ? option.name : null, details: addons.map(a => a.name).join('، '),
      price: (option ? option.price : item.price) + addons.reduce((t, a) => t + a.price, 0), image: item.image, qty: 1});
    save(); toast(S.t.added);
  }

  function toast(msg) {
    const t = $('[data-toast]'); if (!t) return;
    t.textContent = msg; t.classList.add('show'); clearTimeout(t._h); t._h = setTimeout(() => t.classList.remove('show'), 1400);
  }

  function render() {
    $$('[data-cart-count]').forEach(e => e.textContent = count());
    $$('[data-cart-total]').forEach(e => e.textContent = money(total()));
    $$('.cartbar').forEach(e => e.classList.toggle('show', count() > 0 && !document.querySelector('[data-checkout]')));
    $$('[data-cart-foot]').forEach(e => e.hidden = !cart.length);
    $$('[data-cart-lines]').forEach(box => {
      if (!cart.length) {
        box.innerHTML = `<div class="empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg><b>${esc(S.t.empty)}</b><div>${esc(S.t.emptySub)}</div></div>`;
        return;
      }
      box.innerHTML = cart.map((l, i) => `
        <div class="line">
          ${l.image ? `<img src="${esc(l.image)}" alt="">` : ''}
          <div class="nm"><b>${esc(l.name)}</b>${l.option ? `<small>${esc(l.option)}</small><br>` : ''}${l.details ? `<small class="det">${esc(l.details)}</small>` : ''}<small>${money(l.price)} ${esc(S.aed)}</small></div>
          <div class="qty"><button type="button" data-dec="${i}" aria-label="-">−</button><span>${l.qty}</span><button type="button" data-inc="${i}" aria-label="+">+</button></div>
        </div>`).join('');
    });
    const sub = $('[data-submit]');
    if (sub) { const pm = $('input[name=payment_method]:checked'); sub.textContent = pm && pm.value === 'card' ? sub.dataset.card : sub.dataset.wa; sub.disabled = !cart.length; }
  }

  // drawer + option sheet
  const scrim = $('[data-scrim]'), drawer = $('[data-drawer]'), sheet = $('[data-sheet]'), mnav = $('[data-mnav]');
  const open = el => { el.classList.add('open'); scrim.classList.add('open'); document.body.style.overflow = 'hidden'; };
  const closeAll = () => { [drawer, sheet, scrim, mnav].forEach(e => e && e.classList.remove('open')); document.body.style.overflow = ''; };
  scrim && scrim.addEventListener('click', closeAll);
  document.addEventListener('keydown', e => e.key === 'Escape' && closeAll());

  const plus = v => v > 0 ? `+${money(v)} <small>${esc(S.aed)}</small>` : '';
  const head = (title, required) => `<div class="grp-h"><b>${esc(title)}</b><span class="${required ? 'req' : 'opt-l'}">${esc(required ? S.t.required : S.t.optional)}</span></div>`;

  function openOptions(item) {
    const groups = item.groups || [];
    sheet.innerHTML = `
      <div class="ph${item.image && /\/(photos|menu)\//.test(item.image) ? ' menu' : ''}">${item.image ? `<img src="${esc(item.image)}" alt="">` : ''}<button class="x" data-close>&times;</button></div>
      <div class="in">
        <h3 style="font-size:1.4rem">${esc(item.name)}</h3>
        ${item.desc ? `<p style="color:var(--muted);margin:4px 0 6px">${esc(item.desc)}</p>` : ''}
        ${item.options.length ? `<div class="grp">${head(S.t.chooseOne, true)}
          ${item.options.map((o, i) => `<label class="opt"><span class="l"><input type="radio" name="opt" value="${i}" ${i === 0 ? 'checked' : ''}>${esc(o.name)}</span><span class="price">${money(o.price)} <small>${esc(S.aed)}</small></span></label>`).join('')}</div>` : ''}
        ${groups.map((g, gi) => `<div class="grp" data-g="${gi}">${head(g.name, g.required)}
          ${g.addons.map((a, ai) => `<label class="opt"><span class="l"><input type="${g.multiple ? 'checkbox' : 'radio'}" name="g${gi}" value="${ai}">${esc(a.name)}</span><span class="price">${plus(a.price)}</span></label>`).join('')}</div>`).join('')}
        <button class="btn btn-primary btn-block" data-confirm style="margin-top:8px"></button>
      </div>`;
    open(sheet);

    const btn = $('[data-confirm]', sheet);
    const picked = () => {
      const oi = $('input[name=opt]:checked', sheet);
      const option = item.options.length ? item.options[+(oi ? oi.value : 0)] : null;
      const addons = [];
      let missing = null;
      groups.forEach((g, gi) => {
        const sel = $$(`input[name=g${gi}]:checked`, sheet).map(x => g.addons[+x.value]);
        if (g.required && !sel.length && missing === null) missing = gi;
        addons.push(...sel);
      });
      return {option, addons, missing};
    };
    const refresh = () => {
      const {option, addons, missing} = picked();
      const total = (option ? option.price : item.price) + addons.reduce((t, a) => t + a.price, 0);
      btn.innerHTML = `+ ${esc(S.t.add)} · ${money(total)} <small>${esc(S.aed)}</small>`;
      btn.classList.toggle('muted', missing !== null);
    };
    // an optional single-choice group can be cleared by tapping the chosen one again
    $$('.grp[data-g] input[type=radio]', sheet).forEach(r => r.addEventListener('click', () => {
      const g = groups[+r.closest('.grp').dataset.g];
      if (!g.required && r.dataset.was === '1') r.checked = false;
      $$(`input[name="${r.name}"]`, sheet).forEach(x => { x.dataset.was = x.checked ? '1' : ''; });
      refresh();
    }));
    sheet.onchange = refresh;
    refresh();

    btn.onclick = () => {
      const {option, addons, missing} = picked();
      if (missing !== null) {
        const box = $(`.grp[data-g="${missing}"]`, sheet);
        box.classList.remove('need'); void box.offsetWidth; box.classList.add('need');
        box.scrollIntoView({block: 'center', behavior: 'smooth'});
        return;
      }
      add(item, option, addons); closeAll();
    };
  }

  document.addEventListener('click', e => {
    const t = e.target.closest('[data-add],[data-open-cart],[data-open-nav],[data-close],[data-inc],[data-dec]');
    if (!t) return;
    if (t.matches('[data-open-nav]')) open(mnav);
    else if (t.matches('[data-open-cart]')) { e.preventDefault(); if (mnav) mnav.classList.remove('open'); render(); open(drawer); }
    else if (t.matches('[data-close]')) closeAll();
    else if (t.matches('[data-inc]')) { cart[+t.dataset.inc].qty++; save(); }
    else if (t.matches('[data-dec]')) { const l = cart[+t.dataset.dec]; if (--l.qty <= 0) cart.splice(+t.dataset.dec, 1); save(); }
    else if (t.matches('[data-add]')) {
      const item = (window.ITEMS || {})[t.dataset.add]; if (!item) return;
      if ((item.options && item.options.length) || (item.groups && item.groups.length)) openOptions(item);
      else { add(item, null); t.classList.add('done'); setTimeout(() => t.classList.remove('done'), 700); }
    }
  });

  // checkout
  const form = $('[data-checkout]');
  if (form) {
    const addr = $('[data-address]', form);
    form.addEventListener('change', () => { addr.hidden = $('input[name=fulfillment]:checked', form).value !== 'delivery'; render(); });
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const err = $('[data-err]', form), btn = $('[data-submit]', form);
      err.hidden = true;
      const body = Object.fromEntries(new FormData(form).entries());
      body.items = cart.map(l => ({item_id: l.item_id, option_id: l.option_id, addon_ids: l.addon_ids || [], quantity: l.qty}));
      const label = btn.textContent; btn.disabled = true; btn.textContent = S.t.sending;
      try {
        const res = await fetch(form.dataset.action, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content}, body: JSON.stringify(body)});
        const data = await res.json();
        if (!res.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' · ') : (data.message || 'Error'));
        if (body.payment_method === 'whatsapp') {
          cart = []; save();
          // go to the order page; it opens WhatsApp with the order text
          location.href = data.status_url + '?wa=1';
        } else {
          location.href = data.redirect;
        }
      } catch (ex) {
        err.textContent = ex.message; err.hidden = false; btn.disabled = false; btn.textContent = label;
      }
    });
  }

  render();
})();
