@php
    $title = 'Oracle Veritabani Yonetimi';
@endphp
@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Oracle Veritabani Yoneticisi</h1>
            <p class="text-slate-400 text-sm mt-1">Oracle 21c · FREEPDB1 · aykome_user</p>
        </div>
        <div class="flex gap-2">
            <a href="http://localhost:8080" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-[#02E0FB]/20 to-[#FA6001]/10 px-4 py-2 text-sm font-medium text-white border border-[#02E0FB]/30 hover:border-[#02E0FB]/60 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Oracle Browser (Adminer)
            </a>
            <button onclick="livewire.emit('openMigrateModal')" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600/20 px-4 py-2 text-sm font-medium text-emerald-300 border border-emerald-600/30 hover:border-emerald-600/60 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                MySQL → Oracle Aktar
            </button>
        </div>
    </div>

    @if(!$connectionOk)
        <div class="rounded-lg bg-red-900/20 border border-red-800/40 p-6 text-center">
            <svg class="w-12 h-12 mx-auto text-red-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            <h2 class="text-lg font-semibold text-red-300 mb-1">Oracle Baglantisi Kurulamadi</h2>
            <p class="text-red-400/80 text-sm mb-4">{{ $error }}</p>
            <div class="text-left max-w-lg mx-auto bg-slate-900/50 rounded-lg p-4 text-xs text-slate-400 font-mono space-y-1">
                <p># Oracle container'ini baslatmak icin:</p>
                <p class="text-emerald-400">docker compose up -d oracle adminer</p>
                <p class="mt-2"># Veya yardimci script ile:</p>
                <p class="text-emerald-400">./oracle.sh migrate</p>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="rounded-lg bg-slate-800/50 border border-slate-700/50 p-4 text-center">
                <div class="text-3xl font-bold text-[#60a5fa]">{{ $stats['tables'] ?? 0 }}</div>
                <div class="text-xs text-slate-500 uppercase tracking-wider mt-1">Tablo</div>
            </div>
            <div class="rounded-lg bg-slate-800/50 border border-slate-700/50 p-4 text-center">
                <div class="text-3xl font-bold text-[#60a5fa]">{{ $stats['views'] ?? 0 }}</div>
                <div class="text-xs text-slate-500 uppercase tracking-wider mt-1">View</div>
            </div>
            <div class="rounded-lg bg-slate-800/50 border border-slate-700/50 p-4 text-center">
                <div class="text-3xl font-bold text-[#60a5fa]">{{ $stats['sequences'] ?? 0 }}</div>
                <div class="text-xs text-slate-500 uppercase tracking-wider mt-1">Sequence</div>
            </div>
            <div class="rounded-lg bg-slate-800/50 border border-slate-700/50 p-4 text-center">
                <div class="text-3xl font-bold text-[#60a5fa]">{{ $stats['triggers'] ?? 0 }}</div>
                <div class="text-xs text-slate-500 uppercase tracking-wider mt-1">Trigger</div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-1">
                <div class="rounded-lg bg-slate-800/30 border border-slate-700/50 p-4">
                    <h3 class="text-sm font-semibold text-slate-300 mb-3 uppercase tracking-wider">Tablolar</h3>
                    <div class="space-y-0.5 max-h-[500px] overflow-y-auto">
                        @foreach($tables as $table)
                            <button onclick="loadTable('{{ $table->table_name }}')"
                                class="table-btn w-full text-left px-3 py-1.5 text-sm text-slate-400 hover:text-white hover:bg-slate-700/40 rounded transition"
                                data-table="{{ $table->table_name }}">
                                {{ $table->table_name }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="xl:col-span-2">
                <div class="rounded-lg bg-slate-800/30 border border-slate-700/50 p-4">
                    <div id="table-data-area">
                        <div class="text-center py-12 text-slate-500">
                            <p class="text-lg mb-2">Oracle Veritabani Yoneticisi</p>
                            <p class="text-sm">Soldan bir tablo secin veya asagidan SQL sorgusu calistirin</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-lg bg-slate-800/30 border border-slate-700/50 p-4">
                    <h3 class="text-sm font-semibold text-slate-300 mb-3 uppercase tracking-wider">SQL Sorgusu</h3>
                    <form onsubmit="runQuery(event)">
                        <textarea name="sql" rows="3" class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-sm text-slate-200 font-mono focus:border-[#02E0FB]/50 focus:outline-none" placeholder="SELECT * FROM users WHERE ROWNUM <= 10"></textarea>
                        <div class="flex justify-end mt-2">
                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-[#02E0FB]/20 px-4 py-2 text-sm font-medium text-[#02E0FB] border border-[#02E0FB]/30 hover:border-[#02E0FB]/60 transition">
                                Calistir
                            </button>
                        </div>
                    </form>
                    <div id="query-results" class="mt-3 overflow-x-auto"></div>
                </div>
            </div>
        </div>
    @endif
</div>

<div id="migrate-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="rounded-xl bg-slate-900 border border-slate-700/60 p-6 max-w-lg w-full mx-4 shadow-2xl">
        <h2 class="text-lg font-semibold text-white mb-2">MySQL → Oracle Veri Aktarimi</h2>
        <p class="text-sm text-slate-400 mb-4">Mevcut MySQL veritabanindaki tum tablolari Oracle'a aktarir. Tablolar yoksa otomatik olusturulur.</p>
        <div class="bg-amber-900/20 border border-amber-800/40 rounded-lg p-3 text-sm text-amber-300 mb-4">
            Bu islem mevcut MySQL verilerinizi Oracle'a kopyalar. Buyuk veri setlerinde zaman alabilir.
        </div>
        <div id="migrate-progress" class="hidden mb-4">
            <div class="flex items-center gap-2 text-emerald-400 text-sm mb-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Veriler aktariliyor...
            </div>
            <div class="w-full bg-slate-700 rounded-full h-2"><div class="bg-emerald-500 h-2 rounded-full w-0 transition-all" id="migrate-bar"></div></div>
        </div>
        <div id="migrate-result" class="hidden mb-4 p-3 rounded-lg text-sm"></div>
        <div class="flex justify-end gap-2">
            <button onclick="closeMigrateModal()" class="rounded-lg bg-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-600 transition">Iptal</button>
            <button onclick="startMigration()" id="migrate-btn" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-500 transition">Aktarimi Baslat</button>
        </div>
    </div>
</div>

@push('scripts')
<style>
#query-results table { @apply w-full text-sm; }
#query-results th { @apply text-left px-3 py-2 text-xs uppercase tracking-wider text-slate-500 bg-slate-800/50 font-semibold; }
#query-results td { @apply px-3 py-2 border-t border-slate-700/30 text-slate-300; }
</style>
<script>
function loadTable(table) {
    document.querySelectorAll('.table-btn').forEach(b => b.classList.remove('bg-slate-700/40', 'text-white'));
    document.querySelector(`[data-table="${table}"]`).classList.add('bg-slate-700/40', 'text-white');

    fetch('{{ route("admin.oracle.table-data") }}', {
        method: 'POST',
        headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({table})
    }).then(r => r.json()).then(res => {
        if (!res.success) { document.getElementById('table-data-area').innerHTML = `<div class="text-red-400">${res.error}</div>`; return; }

        let html = `<div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-white">${res.columns[0]?.TABLE_NAME || table} <span class="text-slate-500 font-normal">(${res.total} kayit)</span></h3>
        </div>
        <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr>`;
        res.columns.forEach(c => { html += `<th class="text-left px-3 py-2 text-xs uppercase tracking-wider text-slate-500 bg-slate-800/50 font-semibold">${c.column_name}</th>`; });
        html += '</tr></thead><tbody>';
        res.rows.forEach(row => {
            html += '<tr>';
            res.columns.forEach(c => {
                let val = row[c.column_name];
                html += `<td class="px-3 py-2 border-t border-slate-700/30 text-slate-300">${val !== null && val !== undefined ? val : '<span class="text-slate-600 italic">NULL</span>'}</td>`;
            });
            html += '</tr>';
        });
        html += '</tbody></table></div>';
        document.getElementById('table-data-area').innerHTML = html;
    }).catch(e => {
        document.getElementById('table-data-area').innerHTML = `<div class="text-red-400">Hata: ${e.message}</div>`;
    });
}

function runQuery(e) {
    e.preventDefault();
    const sql = e.target.sql.value;
    const target = document.getElementById('query-results');
    target.innerHTML = '<div class="text-slate-500 text-sm">Calistiriliyor...</div>';

    fetch('{{ route("admin.oracle.query") }}', {
        method: 'POST',
        headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({sql})
    }).then(r => r.json()).then(res => {
        if (!res.success) { target.innerHTML = `<div class="text-red-400 text-sm">${res.error}</div>`; return; }
        if (!res.data || res.data.length === 0) { target.innerHTML = '<div class="text-slate-500 text-sm">Sorgu calisti, sonuc yok.</div>'; return; }
        let html = `<div class="text-xs text-slate-500 mb-2">${res.count} satir</div><table><thead><tr>`;
        Object.keys(res.data[0]).forEach(k => { html += `<th>${k}</th>`; });
        html += '</tr></thead><tbody>';
        res.data.forEach(row => {
            html += '<tr>';
            Object.values(row).forEach(v => { html += `<td>${v !== null ? v : '<span class="text-slate-600 italic">NULL</span>'}</td>`; });
            html += '</tr>';
        });
        html += '</tbody></table>';
        target.innerHTML = html;
    }).catch(e => {
        target.innerHTML = `<div class="text-red-400 text-sm">Hata: ${e.message}</div>`;
    });
}

function openMigrateModal() { document.getElementById('migrate-modal').classList.remove('hidden'); document.getElementById('migrate-modal').classList.add('flex'); }
function closeMigrateModal() { document.getElementById('migrate-modal').classList.add('hidden'); document.getElementById('migrate-modal').classList.remove('flex'); }

function startMigration() {
    const btn = document.getElementById('migrate-btn');
    const progress = document.getElementById('migrate-progress');
    const result = document.getElementById('migrate-result');
    btn.disabled = true; btn.textContent = 'Aktariliyor...';
    progress.classList.remove('hidden');
    result.classList.add('hidden');

    fetch('{{ route("admin.oracle.migrate") }}', {
        method: 'POST',
        headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}'}
    }).then(r => r.json()).then(res => {
        progress.classList.add('hidden');
        result.classList.remove('hidden');
        if (res.success) {
            result.className = 'mb-4 p-3 rounded-lg text-sm bg-emerald-900/20 border border-emerald-800/40 text-emerald-300';
            result.innerHTML = `<strong>Basariyla aktarildi!</strong><br>${res.message}`;
            if (res.errors && res.errors.length) {
                result.innerHTML += '<br><br><strong>Hatalar:</strong><ul class="list-disc list-inside text-red-400 mt-1">';
                res.errors.forEach(e => { result.innerHTML += `<li>${e}</li>`; });
                result.innerHTML += '</ul>';
            }
        } else {
            result.className = 'mb-4 p-3 rounded-lg text-sm bg-red-900/20 border border-red-800/40 text-red-300';
            result.innerHTML = `<strong>Hata:</strong> ${res.error}`;
        }
        btn.disabled = false; btn.textContent = 'Aktarimi Baslat';
    }).catch(e => {
        progress.classList.add('hidden');
        result.classList.remove('hidden');
        result.className = 'mb-4 p-3 rounded-lg text-sm bg-red-900/20 border border-red-800/40 text-red-300';
        result.innerHTML = `<strong>Baglanti Hatasi:</strong> ${e.message}`;
        btn.disabled = false; btn.textContent = 'Aktarimi Baslat';
    });
}
</script>
@endpush
@endsection
