<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AYKOME v6 — Veritabani Yonetimi</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; background:#0f172a; color:#e2e8f0; font-size:13px; }
.header { background:linear-gradient(135deg,#1e293b,#0f172a); border-bottom:1px solid #334155; padding:12px 20px; display:flex; align-items:center; justify-content:space-between; }
.header h1 { font-size:15px; font-weight:600; color:#f8fafc; }
.header h1 span { color:#64748b; font-weight:400; }
.header a { color:#64748b; text-decoration:none; font-size:12px; padding:5px 12px; border-radius:6px; }
.header a:hover { background:#1e293b; color:#f8fafc; }
.container { max-width:800px; margin:40px auto; padding:0 20px; }
.cards { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px; }
.card { background:#1e293b; border:1px solid #334155; border-radius:10px; padding:20px; }
.card.connected { border-color:#065f46; }
.card.connected .status { color:#6ee7b7; }
.card.error { border-color:#7f1d1d; }
.card.error .status { color:#fca5a5; }
.card.active { border-color:#2563eb; box-shadow:0 0 20px rgba(37,99,235,0.15); }
.card h2 { font-size:13px; font-weight:600; margin-bottom:12px; display:flex; align-items:center; gap:8px; }
.card h2 .badge { font-size:9px; background:#2563eb; color:#fff; padding:2px 6px; border-radius:4px; font-weight:400; }
.card .status { font-size:11px; font-weight:500; margin-bottom:8px; }
.card .info { font-size:11px; color:#64748b; line-height:1.6; }
.card .info strong { color:#94a3b8; }
.switch-section { background:#1e293b; border:1px solid #334155; border-radius:10px; padding:20px; margin-bottom:16px; }
.switch-section h3 { font-size:13px; font-weight:600; margin-bottom:12px; }
.switch-section .btns { display:flex; gap:8px; flex-wrap:wrap; }
.btn { padding:8px 20px; border:none; border-radius:6px; font-size:12px; font-weight:500; cursor:pointer; color:#fff; transition:.15s; }
.btn:disabled { opacity:.4; cursor:not-allowed; }
.btn-mysql { background:#2563eb; }
.btn-mysql:hover:not(:disabled) { background:#1d4ed8; }
.btn-oracle { background:#dc2626; }
.btn-oracle:hover:not(:disabled) { background:#b91c1c; }
.btn-outline { background:transparent; color:#94a3b8; border:1px solid #334155; }
.btn-outline:hover { background:#334155; color:#e2e8f0; }
.btn-green { background:#059669; }
.btn-green:hover:not(:disabled) { background:#047857; }
.btn-danger { background:#dc2626; }
.btn-danger:hover:not(:disabled) { background:#b91c1c; }
.btn-copy { background:#475569; padding:4px 10px; font-size:10px; margin-left:8px; border-radius:4px; }
.btn-copy:hover { background:#64748b; }
.migrate-section { background:#1e293b; border:1px solid #334155; border-radius:10px; padding:20px; }
.migrate-section h3 { font-size:13px; font-weight:600; margin-bottom:8px; }
.migrate-section p { font-size:11px; color:#64748b; margin-bottom:12px; }
.msg { padding:10px 14px; border-radius:6px; font-size:12px; margin-bottom:12px; position:relative; }
.msg.success { background:#065f4620; border:1px solid #065f46; color:#6ee7b7; }
.msg.error { background:#7f1d1d20; border:1px solid #7f1d1d; color:#fca5a5; }
.msg.info { background:#1e3a5f20; border:1px solid #1e3a5f; color:#93c5fd; }
.spinner { display:inline-block; width:12px; height:12px; border:2px solid #64748b; border-top-color:#fff; border-radius:50%; animation:spin .6s linear infinite; vertical-align:middle; margin-right:6px; }
@keyframes spin { to { transform:rotate(360deg); } }
.hidden { display:none !important; }
.modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,.6); display:flex; align-items:center; justify-content:center; z-index:100; }
.modal { background:#1e293b; border:1px solid #334155; border-radius:12px; padding:24px; max-width:420px; width:90vw; }
.modal h3 { font-size:14px; margin-bottom:8px; }
.modal p { font-size:12px; color:#94a3b8; margin-bottom:16px; }
.modal .btns { display:flex; gap:8px; justify-content:flex-end; }
.copy-toast { position:fixed; bottom:20px; right:20px; background:#1e293b; border:1px solid #334155; padding:8px 16px; border-radius:8px; font-size:12px; color:#6ee7b7; z-index:200; animation:fadeInOut 2s ease; }
@keyframes fadeInOut { 0%{opacity:0;transform:translateY(10px)} 15%{opacity:1;transform:translateY(0)} 85%{opacity:1;transform:translateY(0)} 100%{opacity:0;transform:translateY(-10px)} }
</style>
</head>
<body>
<div class="header">
    <h1>AYKOME v6 <span>Veritabani Yonetimi</span></h1>
    <div>
        <span style="color:#64748b;font-size:11px;margin-right:12px;" id="current-db-label">Mevcut: <strong style="color:#e2e8f0;">{{ $currentDb }}</strong></span>
        <a href="{{ route('db-switch.logout') }}">Cikis</a>
    </div>
</div>

<div class="container">
    <div class="cards">
        <div class="card @if($connections['mysql']['status'] === 'connected') connected @else error @endif @if($currentDb === 'mysql') active @endif">
            <h2>MySQL <span class="badge">Mevcut</span></h2>
            <div class="status">{{ $connections['mysql']['status'] === 'connected' ? 'Bagli' : 'Hata' }}</div>
            <div class="info">
                @if($connections['mysql']['status'] === 'connected')
                    <strong>Host:</strong> {{ $connections['mysql']['host'] }}:{{ $connections['mysql']['port'] }}<br>
                    <strong>Database:</strong> {{ $connections['mysql']['database'] }}<br>
                    <strong>User:</strong> {{ $connections['mysql']['username'] }}
                @else
                    {{ $connections['mysql']['error'] ?? 'Baglanti yok' }}
                @endif
            </div>
        </div>
        <div class="card @if(isset($connections['oracle']['status']) && $connections['oracle']['status'] === 'connected') connected @else error @endif @if($currentDb === 'oracle') active @endif">
            <h2>Oracle <span class="badge">21c</span></h2>
            <div class="status">{{ isset($connections['oracle']['status']) && $connections['oracle']['status'] === 'connected' ? 'Bagli' : 'Hata' }}</div>
            <div class="info">
                @if(isset($connections['oracle']['status']) && $connections['oracle']['status'] === 'connected')
                    <strong>Host:</strong> {{ $connections['oracle']['host'] }}:{{ $connections['oracle']['port'] }}<br>
                    <strong>Service:</strong> {{ $connections['oracle']['database'] }}<br>
                    <strong>User:</strong> {{ $connections['oracle']['username'] }}
                @else
                    {{ $connections['oracle']['error'] ?? 'Baglanti yok' }}
                @endif
            </div>
        </div>
    </div>

    <div class="switch-section">
        <h3>Veritabani Degistir</h3>
        <p style="font-size:11px;color:#64748b;margin-bottom:12px;">Uygulamanin varsayilan veritabanini degistirir. Degisiklik hemen etkin olur.</p>
        <div class="btns">
            <button class="btn btn-mysql" onclick="switchDb('mysql')" @if($currentDb === 'mysql') disabled @endif>MySQL'e Gec</button>
            <button class="btn btn-oracle" onclick="switchDb('oracle')" @if($currentDb === 'oracle') disabled @endif>Oracle'a Gec</button>
            <button class="btn btn-outline" onclick="refreshStatus()">Durumu Yenile</button>
        </div>
        <div id="switch-msg" class="hidden" style="margin-top:12px;"></div>
    </div>

    <div class="migrate-section">
        <h3>MySQL → Oracle Veri Aktarimi</h3>
        <p>Mevcut MySQL veritabanindaki tum tablolari Oracle'a kopyalar. Tablolar yoksa otomatik olusturulur, varsa uzerine yazilmaz.</p>
        @if($connections['mysql']['status'] !== 'connected')
            <div class="msg info">MySQL bagli degil. Veri aktarimi icin MySQL'in (XAMPP) calistigindan emin olun.</div>
        @endif
        <div class="btns">
            <button class="btn btn-green" id="migrate-btn" onclick="confirmMigrate()" @if($connections['mysql']['status'] !== 'connected') disabled @endif>Aktarimi Baslat</button>
        </div>
        <div id="migrate-msg" class="hidden" style="margin-top:12px;"></div>
    </div>
</div>

<div id="confirm-modal" class="modal-backdrop hidden" onclick="backdropClick(event)">
    <div class="modal" onclick="event.stopPropagation()">
        <h3>Veri Aktarimini Onayla</h3>
        <p>MySQL'deki tum veriler Oracle veritabanina aktarilacak. Bu islem geri alinamaz. Tablo isimleri buyuk harfe cevrilir. Devam etmek istediginize emin misiniz?</p>
        <div class="btns">
            <button class="btn btn-outline" onclick="closeModal()">Iptal</button>
            <button class="btn btn-danger" onclick="startMigrate()">Evet, Aktar</button>
        </div>
    </div>
</div>

<script>
var CSRF = '{{ csrf_token() }}';

function showMsg(el, type, text, raw) {
    el.className = 'msg ' + type;
    if (raw) {
        el.innerHTML = text;
    } else {
        el.textContent = text;
    }
    el.classList.remove('hidden');
}

function copyText(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            var t = document.createElement('div');
            t.className = 'copy-toast';
            t.textContent = 'Kopyalandi!';
            document.body.appendChild(t);
            setTimeout(function() { t.remove(); }, 2000);
        }).catch(function() { fallbackCopy(text); });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch(e) {}
    document.body.removeChild(ta);
    var t = document.createElement('div');
    t.className = 'copy-toast';
    t.textContent = 'Kopyalandi!';
    document.body.appendChild(t);
    setTimeout(function() { t.remove(); }, 2000);
}

function backdropClick(e) {
    if (e.target.id === 'confirm-modal') closeModal();
}

function switchDb(target) {
    var msg = document.getElementById('switch-msg');
    msg.className = 'msg info';
    msg.innerHTML = '<span class="spinner"></span> Degistiriliyor...';
    msg.classList.remove('hidden');
    document.querySelectorAll('.switch-section .btn').forEach(function(b) { b.disabled = true; });

    fetch('{{ route("db-switch.switch") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF},
        body: JSON.stringify({target: target})
    }).then(function(r) { return r.json(); }).then(function(res) {
        if (res.success) {
            showMsg(msg, 'success', res.message);
            setTimeout(function() { location.reload(); }, 1500);
        } else {
            showMsg(msg, 'error', res.error || 'Bilinmeyen hata');
            document.querySelectorAll('.switch-section .btn').forEach(function(b) { b.disabled = false; });
        }
    }).catch(function(e) {
        showMsg(msg, 'error', 'Baglanti hatasi: ' + e.message);
        document.querySelectorAll('.switch-section .btn').forEach(function(b) { b.disabled = false; });
    });
}

function refreshStatus() { location.reload(); }

function confirmMigrate() {
    document.getElementById('confirm-modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('confirm-modal').classList.add('hidden');
}

function showMsgWithCopy(el, type, title, detail) {
    var html = '<strong>' + title + '</strong><br>' + detail;
    if (detail.length > 50) {
        html += '<br><button class="btn btn-copy" onclick="copyText(\'' + detail.replace(/'/g, "\\'").replace(/"/g, '&quot;') + '\')">Kopyala</button>';
    }
    el.className = 'msg ' + type;
    el.innerHTML = html;
    el.classList.remove('hidden');
}

function startMigrate() {
    closeModal();
    var btn = document.getElementById('migrate-btn');
    var msg = document.getElementById('migrate-msg');
    btn.disabled = true;
    msg.className = 'msg info';
    msg.innerHTML = '<span class="spinner"></span> Onaylaniyor...';
    msg.classList.remove('hidden');

    fetch('{{ route("db-switch.confirm-migrate") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF}
    }).then(function(r) { return r.json(); }).then(function(res) {
        if (!res.success) {
            showMsgWithCopy(msg, 'error', 'Hata', res.error || 'Onay basarisiz');
            btn.disabled = false;
            return;
        }
        msg.innerHTML = '<span class="spinner"></span> Veriler Oracle\'a aktariliyor...';
        return fetch('{{ route("db-switch.migrate") }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF}
        });
    }).then(function(r) {
        if (!r) return null;
        return r.json();
    }).then(function(res) {
        if (!res) return;
        if (res.success) {
            var html = '<strong>Basariyla aktarildi!</strong><br>' + res.message;
            if (res.errors && res.errors.length) {
                var errText = res.errors.join('\n');
                html += '<br><br><strong>Hatalar:</strong><ul style="margin:4px 0 0 16px;color:#fca5a5;">';
                res.errors.forEach(function(e) {
                    html += '<li>' + e + '</li>';
                });
                html += '</ul>';
                html += '<button class="btn btn-copy" onclick="copyText(\'' + errText.replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/\n/g, '\\n') + '\')">Hatalari Kopyala</button>';
            }
            msg.className = 'msg success';
            msg.innerHTML = html;
        } else {
            showMsgWithCopy(msg, 'error', 'Aktarim Hatasi', res.error || 'Bilinmeyen hata');
        }
        btn.disabled = false;
    }).catch(function(e) {
        showMsgWithCopy(msg, 'error', 'Baglanti Hatasi', e.message);
        btn.disabled = false;
    });
}
</script>
</body>
</html>
