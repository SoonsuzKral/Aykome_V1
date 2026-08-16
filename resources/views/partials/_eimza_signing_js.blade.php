{{--
    Paylaşılan E-İmza JS Mekanizması
    --------------------------------
    Aslen resources/views/admin/applications/show.blade.php içindeydi (16.08
    oturumu). 16.08 (2. tur) itibarıyla Makam Masası'nda da AYNI gerçek
    e-imza akışının kullanılabilmesi için ortak partial'a taşındı — iki
    yerde ayrı ayrı bakım yapmak yerine TEK kaynaktan yönetilir.

    Bu script iki bağımsız IIFE içerir:
    1) checkEimzaServer() — masaüstü Electron uygulamasının hangi portta
       çalıştığını tarar (58910-58930), sonucu #eimza-status elementine
       (varsa) yazar ve window.eimzaPort / window.buildEimzaPorts()'u
       global olarak sağlar.
    2) .e-imza-btn tıklama işleyicisi — /api/e-imza/baslat çağırır, Electron
       local server'a (veya aykome:// protokolüne) yönlendirir, işlemi
       /api/e-imza/durum/{id} ile polling'ler, tamamlanınca imzalı PDF'i
       iframe tabanlı viewer'da açar.

    Kullanım: sayfanıza @push('scripts') @include('partials._eimza_signing_js') @endpush
    ekleyin. Sayfada #eimza-status (opsiyonel) ve .e-imza-btn (data-app-id,
    data-pdf-type, data-vice-mayor-name, data-update-vice-mayor-url ile)
    elementleri olmalı. CSRF meta ve SweetAlert2 admin layout'ta zaten mevcut.
--}}
<script>
// ── E-İmza Server kontrolu (sayfa acilirken) ────────────────────
(function () {
    // ── E-İmza local server port taraması ─────────────────────────────
    // Electron src/server 58910'dan başlar, port doluysa küçük aralıkta
    // +1 atlar (Windows 58055-58354'ü Docker/Hyper-V için rezerve ettiği
    // için eski 58210 portu ASLA kullanılamıyordu). Üst sınır 58930
    // (server.js MAX_PORT ile aynı) — tarama yalnızca 21 portu kapsar,
    // milisaniyeler içinde biter, konsol hata yağmuru olmaz.
    // Bulunan port window.eimzaPort'a saklanır ve imza butonu aynı portu
    // kullanır. NOT: window'a global atanır — imza butonu farklı bir IIFE
    // bloğunda olduğu için yerel fonksiyona erişemez (ReferenceError olurdu).
    window.buildEimzaPorts = function () {
        var ports = [];
        for (var p = 58910; p <= 58930; p++) ports.push(p);
        return ports;
    };

    var checking = false;
    function checkEimzaServer() {
        if (checking) return;          // çakışan taramayı önle (15sn interval + manuel tetikleme)
        checking = true;
        var ports = window.buildEimzaPorts();
        function finish() { checking = false; }
        function tryPort(i) {
            if (i >= ports.length) {
                window.eimzaPort = null;
                var el = document.getElementById('eimza-status');
                if (el) { el.innerHTML = '<span class="text-red-500">● E-İmza Uygulaması: Bulunamadı — masaüstü uygulamasını başlatın</span>'; }
                finish();
                return;
            }
            // Port yanıt vermezse 1.2sn sonra iptal et → "kontrol ediliyor" asla takılı kalmaz
            var ctrl = new AbortController();
            var timer = setTimeout(function () { ctrl.abort(); }, 1200);
            fetch('http://127.0.0.1:' + ports[i] + '/health', { signal: ctrl.signal })
                .then(function (r) {
                    clearTimeout(timer);
                    if (r.ok) {
                        window.eimzaPort = ports[i];
                        var el = document.getElementById('eimza-status');
                        if (el) { el.innerHTML = '<span class="text-green-600">● E-İmza Uygulaması: Çalışıyor</span>'; }
                        finish();
                    } else {
                        tryPort(i + 1);
                    }
                }).catch(function () {
                    clearTimeout(timer);
                    tryPort(i + 1);
                });
        }
        tryPort(0);
    }
    document.addEventListener('DOMContentLoaded', checkEimzaServer);
    // Masaüstü uygulaması sayfa açıldıktan SONRA başlatılırsa durum 15sn içinde kendiliğinden yeşile döner
    setInterval(checkEimzaServer, 15000);
})();

// ── E-İmza Buton ────────────────────────────────────────────────
(function () {
    var buttons = document.querySelectorAll('.e-imza-btn');
    if (!buttons.length) return;

    buttons.forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var appId = this.dataset.appId;
            var pdfType = this.dataset.pdfType;

            // 16.08 FIX: Ön Kazı İzni (pre_permit / on_kazi_signed) belgesi, PDF
            // içinde Başkan Yrd. adını (vice_mayor_name) basıyor. Bu alan boşsa
            // e-imza BAŞLAMADAN ÖNCE sorulur — eskiden hiç sorulmuyordu, PIN ekranı
            // direkt açılıyordu.
            var pdfTypeGerektirirBaskanAdi = (pdfType === 'pre_permit' || pdfType === 'on_kazi_signed');
            var mevcutBaskanAdi = (this.dataset.viceMayorName || '').trim();
            if (pdfTypeGerektirirBaskanAdi && !mevcutBaskanAdi) {
                var girilenAd = null;
                if (typeof Swal !== 'undefined') {
                    var promptResult = await Swal.fire({
                        title: 'Başkan Yrd. / Müdür V. Adı Soyadı',
                        input: 'text',
                        inputPlaceholder: 'Örn: Ahmet Kaan Karataş',
                        text: 'Bu belgeye (Ön Kazı İzni) basılacak Başkan Yardımcısı/Müdür V. adını girin.',
                        showCancelButton: true,
                        confirmButtonText: 'Kaydet ve İmzala',
                        cancelButtonText: 'Vazgeç',
                        inputValidator: function (value) {
                            return (!value || !value.trim()) ? 'Ad soyad boş bırakılamaz' : undefined;
                        }
                    });
                    if (!promptResult.isConfirmed) { return; }
                    girilenAd = promptResult.value.trim();
                } else {
                    girilenAd = (window.prompt('Başkan Yrd. / Müdür V. Adı Soyadı:') || '').trim();
                    if (!girilenAd) { return; }
                }

                try {
                    var vmRes = await fetch(this.dataset.updateViceMayorUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({ vice_mayor_name: girilenAd })
                    });
                    if (!vmRes.ok) { throw new Error('Başkan Yrd. adı kaydedilemedi.'); }
                    this.dataset.viceMayorName = girilenAd;
                } catch (vmErr) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Hata', text: vmErr.message });
                    }
                    return;
                }
            }

            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> İmza başlatılıyor...';

            // GÖREV 6: İmzalayan formu SORULMAZ; ad/soyad/unvan arka planda
            // giriş yapmış kullanıcıdan alınır (EImzaController::baslat → Auth::user()).

            try {
                var res = await fetch('/api/e-imza/baslat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ application_id: appId, pdf_type: pdfType })
                });

                var data = await res.json();
                if (!res.ok) throw new Error(data.message || 'İmza başlatılamadı');

                var serverUrl = window.location.origin.replace('localhost', '127.0.0.1');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: 'E-İmza işlemi başlatıldı',
                        text: 'Lütfen açılan uygulamada PIN\'inizi girin.',
                        timer: 5000,
                        showConfirmButton: false
                    });
                }

                // Electron local HTTP server'a istek gonder
                // Önce checkEimzaServer'ın bulduğu port (varsa), yoksa tüm aralık taranır
                // Önce checkEimzaServer'ın bulduğu portu dene; başarısızsa TÜM aralığı tara;
                // hepsi başarısızsa aykome:// protocol fallback (port GEREKTİRMEZ, her zaman çalışır)
                var electronPorts = window.buildEimzaPorts();
                if (window.eimzaPort) {
                    electronPorts = [window.eimzaPort].concat(
                        electronPorts.filter(function (p) { return p !== window.eimzaPort; })
                    );
                }
                function tryElectronServer(portIndex) {
                    if (portIndex >= electronPorts.length) {
                        // Hepsi basarisizsa protocol URL dene (fallback)
                        var protocolUrl = 'aykome://sign?tid=' + encodeURIComponent(data.transaction_id) + '&token=' + encodeURIComponent(data.token) + '&server=' + encodeURIComponent(serverUrl);
                        window.location.href = protocolUrl;
                        return;
                    }
                    var ctrl = new AbortController();
                    var timer = setTimeout(function () { ctrl.abort(); }, 1500);
                    fetch('http://127.0.0.1:' + electronPorts[portIndex] + '/sign', {
                        method: 'POST',
                        signal: ctrl.signal,
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            transaction_id: data.transaction_id,
                            token: data.token,
                            server_url: serverUrl
                        })
                    }).then(function (r) {
                        clearTimeout(timer);
                        if (r.ok) {
                            console.log('E-Imza istegi gonderildi (port ' + electronPorts[portIndex] + ')');
                        } else {
                            // Non-OK yanıt (ör. başka bir servis) → taramaya devam et, takılma
                            tryElectronServer(portIndex + 1);
                        }
                    }).catch(function () {
                        clearTimeout(timer);
                        tryElectronServer(portIndex + 1);
                    });
                }
                tryElectronServer(0);

                // Polling
                var pollInterval = setInterval(async function () {
                    try {
                        var durumRes = await fetch('/api/e-imza/durum/' + data.transaction_id, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        var durumData = await durumRes.json();
                        if (durumData.status === 'completed') {
                            clearInterval(pollInterval);
                            // GÖREV 3 + 16.08 FIX: İmzalı nüsha artık ham PDF URL'si yerine
                            // iframe tabanlı viewer'da açılır — tarayıcının "PDF her zaman
                            // indir" ayarı olsa bile bu sayfa HTML olduğu için indirme
                            // tetiklenmez, belge her zaman görüntülenir.
                            if (durumData.imzali_url) {
                                window.open('{{ route("admin.pdf-viewer") }}?url=' + encodeURIComponent(durumData.imzali_url), '_blank');
                            }
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'success', title: 'İmza tamamlandı!', timer: 2000, showConfirmButton: false });
                            }
                            setTimeout(function () { location.reload(); }, 1500);
                        }
                    } catch (e) {}
                }, 3000);

                // 10dk timeout
                setTimeout(function () { clearInterval(pollInterval); btn.disabled = false; btn.innerHTML = 'E-İmza ile İmzala'; }, 600000);

            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = 'E-İmza ile İmzala';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Hata', text: err.message || 'İmza başlatılamadı.' });
                }
            }
        });
    });
})();
</script>
