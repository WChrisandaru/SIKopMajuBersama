(() => {
    const panel = document.getElementById('payment-panel');
    if (!panel) return;
    const el = id => document.getElementById(id);
    const money = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
    const form = el('payment-form');
    const results = el('payment-member-results');
    const status = el('payment-load-status');
    const dialog = el('payment-review');
    let searchVersion = 0, itemVersion = 0, searchPage = 1, timer, member = null, items = [], selected = new Set(), submitting = false;
    const initialItems = new Set(JSON.parse(panel.dataset.oldItems || '[]').map(String));
    function node(tag, text, className) {
        const n = document.createElement(tag); n.textContent = text; if (className) n.className = className; return n;
    }
    async function json(url) {
        const response = await fetch(url, {headers:{Accept:'application/json'}});
        if (!response.ok) throw new Error(response.status === 401 ? 'Sesi berakhir. Muat ulang halaman dan masuk kembali.' : 'Data belum dapat dimuat. Silakan coba lagi.');
        return response.json();
    }
    function total() { return items.filter(i => selected.has(String(i.id))).reduce((s,i) => s + Number(i.amount), 0); }
    function refreshTotal() {
        el('payment-amount').value = total();
        el('payment-total').textContent = money(total());
        el('payment-count').textContent = selected.size + ' komponen dipilih';
        el('review-payment').disabled = !selected.size;
        el('payment-confirmed').value = '0';
    }
    function clearMember() {
        itemVersion++; searchVersion++; member = null; items = []; selected.clear();
        el('payment-member-id').value = ''; form.hidden = true;
        el('selected-payment-member').hidden = true; el('member-search-area').hidden = false;
        el('payment-bill-groups').replaceChildren(); results.replaceChildren();
        el('payment-search-pages').hidden = true; el('payment-verified').checked = false;
        el('reload-payment-items').hidden = true; status.textContent = '';
        el('payment-member-search').value = ''; refreshTotal(); el('payment-member-search').focus();
    }
    async function search(page = 1) {
        const q = el('payment-member-search').value.trim();
        const version = ++searchVersion; searchPage = page;
        results.replaceChildren(); el('payment-search-pages').hidden = true;
        if (q.length < 2) { status.textContent = 'Ketik minimal 2 karakter untuk mencari anggota.'; return; }
        status.textContent = 'Mencari anggota…';
        try {
            const data = await json('/payments/members?q=' + encodeURIComponent(q) + '&page=' + page);
            if (version !== searchVersion) return;
            status.textContent = data.data.length ? data.data.length + ' hasil pada halaman ' + page + '.' : 'Anggota tidak ditemukan. Coba nama atau nomor lain.';
            data.data.forEach(m => {
                const button = node('button', '', 'payment-member-result'); button.type = 'button';
                button.append(node('strong', m.name), node('small', m.number + ' · ' + (m.address || 'Alamat belum diisi') + ' · ' + m.status));
                button.addEventListener('click', () => loadMember(m.id)); results.append(button);
            });
            el('search-prev').disabled = !data.prev_page_url; el('search-next').disabled = !data.next_page_url;
            el('search-page').textContent = 'Halaman ' + page;
            el('payment-search-pages').hidden = !data.prev_page_url && !data.next_page_url;
        } catch (e) { if (version === searchVersion) status.textContent = e.message + ' Ketik ulang pencarian untuk mencoba lagi.'; }
    }
    async function loadMember(id, restore = false) {
        searchVersion++; const version = ++itemVersion;
        selected.clear(); items = []; member = null; form.hidden = true; el('payment-member-id').value = '';
        el('payment-verified').checked = false; refreshTotal(); status.textContent = 'Memuat tagihan anggota…';
        el('reload-payment-items').hidden = true;
        try {
            const data = await json('/payments/members/' + encodeURIComponent(id) + '/items');
            if (version !== itemVersion) return;
            member = data.member; items = data.items;
            el('payment-member-id').value = member.id;
            el('selected-member-name').textContent = member.name;
            el('selected-member-info').textContent = member.number + ' · ' + (member.address || 'Alamat belum diisi') + ' · ' + member.status;
            results.replaceChildren(); el('member-search-area').hidden = true;
            el('selected-payment-member').hidden = false;
            status.textContent = items.length ? '' : 'Tidak ada komponen tagihan final yang belum lunas untuk anggota ini.';
            if (restore) items.forEach(i => { if (initialItems.has(String(i.id))) selected.add(String(i.id)); });
            renderItems(); form.hidden = false; refreshTotal();
        } catch (e) {
            if (version !== itemVersion) return;
            status.textContent = e.message;
            el('reload-payment-items').hidden = false;
            el('reload-payment-items').onclick = () => loadMember(id, restore);
        }
    }
    function renderItems() {
        const target = el('payment-bill-groups'); target.replaceChildren();
        const periods = [...new Set(items.map(i => i.period))];
        periods.forEach(period => {
            const group = node('section', '', 'payment-period');
            const heading = node('div', '', 'panel-heading'); heading.append(node('h3', 'Periode ' + period));
            const all = node('button', 'Pilih semua periode ini'); all.type = 'button';
            all.addEventListener('click', () => { items.filter(i=>i.period===period).forEach(i=>selected.add(String(i.id))); renderItems(); refreshTotal(); }); heading.append(all); group.append(heading);
            const wrap = node('div', '', 'table-wrap'); const table = node('table');
            const head = node('thead'); const header = node('tr'); ['Pilih','Komponen tagihan','Jatuh tempo','Sisa tagihan'].forEach(t=>header.append(node('th', t))); head.append(header); table.append(head);
            const body = node('tbody');
            items.filter(i => i.period === period).forEach(item => {
                const row = node('tr'); const choice = node('td');
                const check = document.createElement('input'); check.type = 'checkbox'; check.name = 'items[]'; check.value = item.id; check.checked = selected.has(String(item.id));
                check.setAttribute('aria-label', 'Bayar ' + item.category + ' periode ' + period + ', ' + money(item.amount));
                check.addEventListener('change', () => { if(check.checked) selected.add(String(item.id)); else selected.delete(String(item.id)); refreshTotal(); }); choice.append(check);
                row.append(choice, node('td', item.category));
                const due = node('td', item.due_date || 'Belum ditetapkan');
                if (item.due_date && item.due_date < panel.dataset.today) due.append(node('small','Lewat jatuh tempo','field-error'));
                row.append(due, node('td', money(item.amount))); body.append(row);
            });
            table.append(body); wrap.append(table); group.append(wrap); target.append(group);
        });
    }
    el('payment-member-search').addEventListener('input', () => { clearTimeout(timer); searchVersion++; results.replaceChildren(); el('payment-search-pages').hidden = true; timer = setTimeout(() => search(), 250); });
    el('payment-member-search').addEventListener('keydown', e => { if (e.key === 'ArrowDown') { e.preventDefault(); results.querySelector('button')?.focus(); } if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); search(); } });
    results.addEventListener('keydown', e => { const buttons = [...results.querySelectorAll('button')]; const index = buttons.indexOf(document.activeElement); if (e.key==='ArrowDown') {e.preventDefault();buttons[Math.min(index+1,buttons.length-1)]?.focus();} if(e.key==='ArrowUp'){e.preventDefault();if(index===0)el('payment-member-search').focus();else buttons[index-1]?.focus();} });
    el('search-prev').onclick = () => search(searchPage-1); el('search-next').onclick = () => search(searchPage+1);
    el('change-payment-member').onclick = clearMember;
    el('clear-payment-items').onclick = () => { selected.clear(); renderItems(); refreshTotal(); };
    function methodChanged() { el('payment-verified').checked=false; el('payment-verified-label').textContent=el('payment-method').value==='Transfer'?'Saya sudah mencocokkan transfer dengan mutasi rekening dan memastikan dana diterima.':'Saya sudah memeriksa penerimaan uang tunai.'; }
    el('payment-method').addEventListener('change',methodChanged); methodChanged();
    el('review-payment').onclick = () => {
        if (!form.reportValidity() || !selected.size || !member) return;
        el('review-member').textContent = member.number + ' · ' + member.name;
        el('review-method-date').textContent = el('payment-method').value + ' · Tanggal pembayaran ' + el('payment-date').value;
        const tbody=el('review-items'); tbody.replaceChildren();
        items.filter(i=>selected.has(String(i.id))).forEach(i=>{ const tr=node('tr');tr.append(node('td',i.period),node('td',i.category),node('td',money(i.amount)));tbody.append(tr); });
        el('review-total').textContent=money(total());dialog.showModal();el('back-payment-review').focus();
    };
    const closeReview=()=>{ if (!submitting) {dialog.close();el('review-payment').focus();} };
    el('close-payment-review').onclick=closeReview;el('back-payment-review').onclick=closeReview;
    dialog.addEventListener('cancel',e=>{e.preventDefault();closeReview();});
    el('submit-payment').onclick=()=>{ if(submitting)return;el('payment-confirmed').value='1';form.requestSubmit(); };
    form.addEventListener('submit',e=>{ if(submitting || el('payment-confirmed').value!=='1'){e.preventDefault();return;}submitting=true;el('submit-payment').disabled=true;el('submit-payment').textContent='Menyimpan…'; });
    if (panel.dataset.member) loadMember(panel.dataset.member, true);
})();
