{{-- ============================================================
    KAZI METRAJ TAHMİNİ (PRO) — Başvuru formuna gömülü panel
    create.blade.php + edit.blade.php'ye @include edilir.
    JS, yanında tanımlı surfaceLines/renderTable/recalculateAll
    API'sine bağlanır (addSurfaceLine satırları doldurur).
    ============================================================ --}}
<div id="metraj-tahmin-panel" class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="text-sm font-semibold text-indigo-900">🎯 Kazı Metraj Tahmini</h3>
            <p class="mt-0.5 text-xs text-indigo-700/70">
                Kurum + mahalle bazlı geçmiş başvurulardan öneri üretir; tek tıkla zemin satırlarına uygulanır.
            </p>
        </div>
        <button type="button" id="metraj-tahmin-btn"
            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" /></svg>
            Metraj Tahmini Al
        </button>
    </div>

    {{-- Sonuç alanı (JS doldurur) --}}
    <div id="metraj-tahmin-result" class="mt-3 hidden">
        <div class="flex flex-wrap items-center gap-2">
            <span id="mtr-level" class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-[11px] font-semibold text-indigo-700"></span>
            <span id="mtr-confidence" class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600"></span>
            <span id="mtr-message" class="text-xs text-slate-500"></span>
        </div>

        <div class="mt-3 overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-600">
                        <th class="px-3 py-2 font-medium">Zemin Tipi</th>
                        <th class="px-3 py-2 font-medium text-right">Pay</th>
                        <th class="px-3 py-2 font-medium text-right">Tahmin m²</th>
                        <th class="px-3 py-2 font-medium text-right">Birim Fiyat</th>
                        <th class="px-3 py-2 font-medium text-right">Öngörü Tutar (₺)</th>
                    </tr>
                </thead>
                <tbody id="mtr-rows"></tbody>
                <tfoot>
                    <tr class="border-t border-slate-200 bg-indigo-50 font-semibold text-indigo-900">
                        <td class="px-3 py-2" colspan="2">Toplam Öngörü</td>
                        <td class="px-3 py-2 text-right" id="mtr-total-m2">0</td>
                        <td class="px-3 py-2"></td>
                        <td class="px-3 py-2 text-right" id="mtr-total-amount">0.00 ₺</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
            <button type="button" id="mtr-apply-btn"
                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                ♻️ Zemin Satırlarına Uygula
            </button>
            <span id="mtr-apply-status" class="text-xs text-emerald-700"></span>
        </div>
    </div>

    {{-- Hata alanı --}}
    <div id="metraj-tahmin-error" class="mt-2 hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"></div>
</div>

<script>
    (function () {
        const btn = document.getElementById('metraj-tahmin-btn');
        if (!btn) return;

        const resultBox = document.getElementById('metraj-tahmin-result');
        const errBox = document.getElementById('metraj-tahmin-error');
        const rowsBody = document.getElementById('mtr-rows');
        let lastForecast = null;

        function showError(msg) {
            if (!errBox) return;
            errBox.textContent = msg;
            errBox.classList.remove('hidden');
            if (resultBox) resultBox.classList.add('hidden');
        }
        function clearError() {
            if (errBox) { errBox.textContent = ''; errBox.classList.add('hidden'); }
        }

        // Formdan mevcut kurumu oku (belediye kullanıcısı seçimli select, kurum kullanıcısı hidden/oturum)
        function currentInstitutionId() {
            const sel = document.getElementById('institution_id');
            if (sel) {
                const v = sel.value;
                if (v && v !== '') return v;
            }
            const hidden = document.querySelector('input[name="institution_id"]');
            if (hidden && hidden.value && hidden.value !== '') return hidden.value;
            return null;
        }

        // Formdan mevcut alanı oku (harita çiziminin doldurduğu input)
        function currentAreaM2() {
            const el = document.getElementById('total_area_m2');
            if (!el) return 0;
            const raw = String(el.value || '').trim();
            const num = parseFloat(raw.replace(',', '.'));
            return isNaN(num) || num <= 0 ? 0 : num;
        }

        // Mahalle: address_components'ın ilk mahallesini veya adres metnini dene
        function guessMahalle() {
            const compInput = document.querySelector('textarea[name="address_components_json"]');
            if (compInput && compInput.value) {
                try {
                    const parsed = JSON.parse(compInput.value);
                    if (Array.isArray(parsed) && parsed.length && parsed[0] && parsed[0].mahalle) {
                        return String(parsed[0].mahalle).trim();
                    }
                } catch (e) { /* yoksay */ }
            }
            const address = document.querySelector('textarea[name="address_text"], input[name="address_text"]');
            if (address && address.value) {
                const firstLine = String(address.value).split('\n')[0].trim();
                if (firstLine) return firstLine;
            }
            return null;
        }

        function fmt(n) {
            return Number(n || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        async function fetchForecast() {
            clearError();
            const inst = currentInstitutionId();
            const area = currentAreaM2();
            const mahalle = guessMahalle();

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            if (!csrf) { showError('CSRF token bulunamadı.'); return; }

            if (area <= 0) {
                showError('Önce haritadan bir alan çizin veya Alan (m²) değerini girin.');
                return;
            }

            btn.disabled = true;
            btn.textContent = '⏳ Hesaplanıyor...';
            lastForecast = null;
            if (resultBox) resultBox.classList.add('hidden');

            const payload = {
                total_area_m2: area,
                _token: csrf
            };
            if (inst) payload.institution_id = inst;
            if (mahalle) payload.mahalle = mahalle;

            try {
                const r = await fetch(@json(route('admin.applications.metraj-tahmin')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(payload)
                });
                if (!r.ok) {
                    let msg = 'Tahmin alınamadı (' + r.status + ').';
                    try { const j = await r.json(); if (j.message) msg = j.message; } catch (e) { /* yoksay */ }
                    throw new Error(msg);
                }
                const data = await r.json();
                renderForecast(data);
            } catch (e) {
                showError('Hata: ' + e.message);
            } finally {
                btn.disabled = false;
                btn.textContent = '🎯 Metraj Tahmini Al';
            }
        }

        function renderForecast(data) {
            lastForecast = data;
            if (!resultBox || !rowsBody) return;

            const lvl = document.getElementById('mtr-level');
            const conf = document.getElementById('mtr-confidence');
            const msg = document.getElementById('mtr-message');
            const totalM2 = document.getElementById('mtr-total-m2');
            const totalAmt = document.getElementById('mtr-total-amount');

            if (lvl) lvl.textContent = data.level_label || '';
            if (conf) conf.textContent = 'Güven: ' + (data.confidence || '');
            if (msg) msg.textContent = data.message || '';

            rowsBody.innerHTML = '';
            (data.rows || []).forEach(function (row) {
                const tr = document.createElement('tr');
                tr.className = 'border-b border-slate-100';
                tr.innerHTML =
                    '<td class="px-3 py-1.5 font-medium text-slate-700">' + (row.name || '—') + '</td>' +
                    '<td class="px-3 py-1.5 text-right text-slate-600">' + fmt(row.pct) + '%</td>' +
                    '<td class="px-3 py-1.5 text-right text-slate-700">' + fmt(row.m2) + '</td>' +
                    '<td class="px-3 py-1.5 text-right text-slate-500">' + fmt(row.price_per_m2) + ' ₺</td>' +
                    '<td class="px-3 py-1.5 text-right font-medium text-slate-800">' + fmt(row.amount) + '</td>';
                rowsBody.appendChild(tr);
            });

            if (totalM2) totalM2.textContent = fmt(data.total_m2);
            if (totalAmt) totalAmt.textContent = fmt(data.forecast_total) + ' ₺';

            resultBox.classList.remove('hidden');
        }

        function applyToSurfaceLines() {
            const applyStatus = document.getElementById('mtr-apply-status');
            if (applyStatus) { applyStatus.textContent = ''; }

            if (!lastForecast || !Array.isArray(lastForecast.rows) || lastForecast.rows.length === 0) {
                if (applyStatus) applyStatus.textContent = 'Uygulanacak tahmin yok.';
                return;
            }

            // Önce mevcut satırları koruyarak tahmini EKLE (addSurfaceLine API'si)
            let added = 0;
            lastForecast.rows.forEach(function (row) {
                if (typeof window.addSurfaceLine === 'function') {
                    window.addSurfaceLine({
                        surface_type_id: row.surface_type_id,
                        surface_type_name: row.name,
                        price_per_m2: row.price_per_m2,
                        width_m: null,
                        length_m: null,
                        quantity: row.quantity
                    });
                    added++;
                }
            });

            if (typeof window.recalculateAll === 'function') {
                window.recalculateAll();
            }

            if (applyStatus) {
                applyStatus.textContent = added > 0 ? '✓ ' + added + ' zemin satırı eklendi.' : 'Satır eklenemedi.';
                setTimeout(function () { applyStatus.textContent = ''; }, 4000);
            }
        }

        btn.addEventListener('click', fetchForecast);
        const applyBtn = document.getElementById('mtr-apply-btn');
        if (applyBtn) applyBtn.addEventListener('click', applyToSurfaceLines);
    })();
</script>
