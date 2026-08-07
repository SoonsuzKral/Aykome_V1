<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kurum Başvuru Yazısı</title>
    <style>
        body { font-family: "Times New Roman", Times, serif; font-size: 14.5px; line-height: 1.4; margin: 0; color:#000; background:#e2e8f0; display:flex; justify-content:center; }
        .a4-container { background: white; width: 210mm; min-height: 297mm; max-height:297mm; overflow:hidden; box-sizing: border-box; padding: 18mm 20mm; margin: 15px auto; box-shadow: 0px 5px 15px rgba(0,0,0,0.5); }

        table { width: 100%; border-collapse: collapse; table-layout: fixed;}
        td { vertical-align: top; }

        .mahalle-title { font-weight: bold; font-size: 14px; text-decoration: underline; margin-bottom: 2px; text-transform: uppercase; font-family: Arial, sans-serif;}
        .list-table { border-collapse: collapse; margin-bottom: 20px; font-family: Arial, sans-serif; font-size: 13px; }
        .list-table td { border: 1.5px solid black; padding: 4px; width: 50%; vertical-align: middle; font-weight:bold;}

        .text-center { text-align: center; } .text-right { text-align: right;} .font-bold { font-weight: bold; }

        .print-bar { position: fixed; top: 1rem; left: 1rem; right: auto; z-index: 99999; background:#1f2937; padding:8px 14px; border-radius:10px; box-shadow:0 5px 14px rgba(0,0,0,.35); display:flex; flex-direction:row; gap:8px; align-items:center; text-align:right;}
        .print-btn { background:#3b82f6; color:#fff; border:none; padding:10px 25px; border-radius:5px; font-weight:bold; cursor:pointer;}

        /* Ortak kurum logosu + Vanilla JS Toolbar */
        .print-logo { max-width: 140px !important; width: auto; height: auto; object-fit: contain; }
        .toolbar {
            position: fixed; bottom: 14px; right: 14px; z-index: 99999;
            display: flex; gap: 5px; align-items: center;
            background: #1e3a8a; padding: 7px 9px; border-radius: 9px;
            box-shadow: 0 5px 14px rgba(0,0,0,.35);
        }
        .toolbar button {
            background: #2563eb; color: #fff; border: none;
            min-width: 32px; height: 32px; border-radius: 6px;
            font-weight: 700; cursor: pointer; font-size: 13px; line-height: 1;
        }
        .toolbar button:hover { background: #1d4ed8; }
        .toolbar .sep { width: 1px; height: 20px; background: rgba(255,255,255,.25); margin: 0 2px; }
        @media print { .toolbar { display: none !important; } }

        /* EK-1 sayfası stilleri */
        .ek-sayfa { page-break-before: always; }
        .ek-baslik { font-size: 15px; font-weight: 900; text-align: center; margin-bottom: 20px; text-decoration: underline; }
        .ek-mahalle-td { font-size: 14px; font-weight: bold; padding: 10px 8px; }
        .ek-tablo { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .ek-tablo th, .ek-tablo td { border: 1px solid #000; padding: 6px 8px; }
        .ek-tablo th { text-align: left; font-weight: bold; }

        @page { size: A4 portrait; margin: 0 !important; }
        @media print {
            body { background:#fff; margin:0 !important; font-size: 14.5px !important; display: block;}
            .print-bar { display:none !important; }
            .a4-container { width: 100% !important; padding: 12mm 20mm 0 20mm !important; height:100% !important; box-shadow:none; margin:0; page-break-after: avoid; }
        }
        .no-print { display: block; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>

    <div class="print-bar no-print">
        <button onclick="window.print()" class="print-btn">🖨️ YAZDIR</button>
        <button onclick="window.print()" class="print-btn">💾 ŞABLONU DÜZENLE (KAYDET)</button>
    </div>
    @php
        // CELL-BASED AUTH: Üst Yazı (Dilekçe) altkurumun kendi başvuru evrakı olduğundan
        // tüm alanlar iki tarafa açıktır; helper tutarlılık için tanımlıdır.
        $isMuni = auth()->check() && auth()->user()->isMunicipalityPersonel();
    @endphp
    <div class="a4-container">

        @if(($application->status instanceof \BackedEnum ? $application->status->value : $application->status) === 'cancelled')
        <div style="border:3px solid #dc2626; padding:14px 16px; margin-bottom:28px; background:#fef2f2; text-align:center;">
            <div style="font-size:18px; font-weight:900; color:#b91c1c; letter-spacing:1px;">BU BAŞVURU İPTAL EDİLMİŞTİR</div>
            <div style="margin-top:8px; font-size:13.5px; color:#7f1d1d;">
                <b>İptal Tarihi :</b> {{ $application->updated_at?->format('d.m.Y H:i') ?? '—' }}
            </div>
            @if($application->rejection_reason)
            <div style="margin-top:8px; font-size:13px; color:#7f1d1d;">
                <b>İptal Sebebi :</b> {{ $application->rejection_reason }}
            </div>
            @endif
        </div>
        @endif

        <table>
            <tr>
                <td style="width:25%;">
                    @if($logo_base64)
                        <img src="{{ $logo_base64 }}" alt="Logo" class="print-logo" style="max-height:85px; width:auto;">
                    @else
                        <div style="font-size: 26px; font-weight: 900; color: #0f172a; text-align:left;" contenteditable="true">{!! mb_strtoupper(mb_substr($application->institution?->name ?? $application->applicant_name ?? '', 0, 5), 'UTF-8') !!}...</div>
                    @endif
                </td>
                <td style="width:50%; text-align:center; padding-top:10px;">
                    <span class="font-bold" style="font-size: 16px;" contenteditable="true">{{ mb_strtoupper($application->institution?->name ?? $application->applicant_name ?? '', 'UTF-8') }}</span><br>
                    <span style="font-size: 15px;">Şanlıurfa Tesis Yöneticiliği</span>
                </td>
                <td style="width:25%;"></td>
            </tr>
        </table>

        <table style="margin-top: 50px; margin-bottom: 50px;">
            <tr>
                <td style="width:80%; padding:0; line-height: 1.5;">
                    <span contenteditable="true">Sayı &nbsp;&nbsp;&nbsp;: {{ $application->application_no ?? '' }}</span><br>
                    <span contenteditable="true">Konu : {{ mb_strtoupper($application->excavation_reason ?? '', 'UTF-8') }} PROJESİ KAZI ÖN</span> <br> <span contenteditable="true">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;İZNİ TALEP REVİZE</span>
                </td>
                <td style="width:20%; text-align:right; padding:0; padding-top: 5px;">
                    <span contenteditable="true">{{ $application->created_at ? $application->created_at->format('d.m.Y') : '' }}</span>
                </td>
            </tr>
        </table>

        <div class="text-center font-bold" style="font-size: 15px; margin-bottom: 40px; letter-spacing: 0.5px;" contenteditable="true">
            EYYÜBİYE BELEDİYE BAŞKANLIĞI<br>AYKOME ŞUBE MÜDÜRLÜĞÜ
        </div>

        <div>
            <b contenteditable="true">İlgi &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</b> <span contenteditable="true">Belediyeniz yatırım programlarına istinaden iletilen &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; tarihli talep ve onaylı formunuz.</span><br><br>
            <p contenteditable="true" style="text-indent: 40px; margin-bottom: 10px;">İlgi sayılı form / talebiniz üzerine; kurumumuza bildirdiğiniz lokasyon adreslerinin sokak ve numarataj ilişkileri planımıza yansıtılmıştır.</p>

            <p contenteditable="true" style="text-indent: 40px; text-align: justify; line-height:1.5;">
                Şirketimiz {{ date('Y') }} yılı altyapı-üstyapı yatırım ve kurulum bakım-onarım çalışma programında <b>{{ $application->project_code ?? '' }}</b> Pyp referans numarası ile işlem gören; ŞANLIURFA İLİ EYYÜBİYE İLÇESİ adreslerindeki faaliyet alanımız ve projeye dahil tesis çalışmalarımız kapsamında planlamalar değerlendirilmiş olup; yüklenicimizde ({{ $application->institution?->name ?? '' }}) kalan faaliyetlerin ve ihale sürçlerinin idamesi adına Şanlıurfa EYYÜBİYE Belediyesi'nin sınırlarında (sorumluluğunda) bulunan kazı işlem ruhsat ve izinlerinin tarafımıza tahsis edilmesi kurumumuzca / ilgilimizce talep edilmektedir.
            </p>
            <p contenteditable="true" style="text-indent: 40px; margin-bottom:10px;">Belirtilen tesislerin yapım süreciyle alakalı ilgili cadde ve sokaklar alttaki tabloda gösterilmektedir. Gerekli ön ruhsat onayları hakkında,<br>Gereğini arz ederim.</p>
        </div>

        <div style="margin-top:25px;">
            @if($application->isMuhtelif())
                <div class="mahalle-title" contenteditable="true">MUHTELİF CADDE VE SOKAK</div>
                <table class="list-table">
                    <tr><td colspan="2" contenteditable="true" style="font-weight:normal; font-size:12.5px;">Başvuru kapsamındaki tüm cadde ve sokaklar EK-1: KAZI ADRESLERİ LİSTESİ sayfasında detaylandırılmıştır.</td></tr>
                </table>
            @elseif(is_array($application->address_components) && count($application->address_components) > 0)
                @foreach($application->address_components as $adres)
                    <div class="mahalle-title" contenteditable="true">{{ mb_strtoupper($adres['mahalle'] ?? '', 'UTF-8') }}</div>
                    <table class="list-table">
                        @php
                            $sokaklar = isset($adres['streets']) && is_array($adres['streets']) ? $adres['streets'] : [];
                            $satirlar = array_chunk($sokaklar, 2);
                        @endphp
                        @forelse($satirlar as $satir)
                            <tr>
                                <td contenteditable="true">{{ mb_strtoupper($satir[0] ?? '', 'UTF-8') }}</td>
                                <td contenteditable="true">{{ mb_strtoupper($satir[1] ?? '', 'UTF-8') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" contenteditable="true" style="font-weight:normal;"></td></tr>
                        @endforelse
                    </table>
                @endforeach
            @else
                <div class="mahalle-title" contenteditable="true">KAYITLI ADRES BLOĞU</div>
                <table class="list-table">
                    <tr><td colspan="2" contenteditable="true" style="font-weight:normal; font-size:12.5px;">{{ $application->address_text ?? '' }}</td></tr>
                </table>
            @endif
        </div>

        <table style="width:100%; margin-top: 50px;">
            <tr>
                <td style="width:60%; line-height:1.4;" contenteditable="true">
                    Tesis Kontrol / Yetkilisi : <b>{{ mb_strtoupper($application->tesis_sorumlusu_adi ?? '', 'UTF-8') }}</b><br>
                    Evrağı Düzenleyen &nbsp;: <b>{{ mb_strtoupper($application->duzenleyen_kisi ?? auth()->user()?->name ?? '', 'UTF-8') }}</b><br>
                    Yaklaşık Kazı &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <b>{{ collect($application->surfaceLines ?? [])->sum('quantity') }} m² / m. </b>
                </td>
                <td style="width:40%; text-align:center;">
                    <b style="text-transform:uppercase;">{{ mb_strtoupper($application->mudur_adi ?? 'KURUM YÖNETİCİSİ', 'UTF-8') }}</b><br>
                    <span style="font-size:14px;">{{ $application->mudur_unvani ?? 'Bölge Sorumlusu/İl Müdürü' }}</span><br>
                    <span style="font-size:12.5px;">{{ mb_strtoupper($application->institution?->name ?? '', 'UTF-8') }}</span>
                </td>
            </tr>
        </table>

        <div style="margin-top:20px; border-top: 1.5px dashed #444; padding-top:5px; text-align: center; font-family: monospace; font-size: 10px; color:#1f2937;">
            Bu belge Eyyübiye Belediyesi AYKOME (Altyapı Koordinasyon) Elektronik Yönetim Sistemi ile güvenli ve benzersiz olarak oluşturulmuştur.<br>
            BELGE DOĞRULAMA KODU : <span style="color:#d97706; font-size:12px; font-weight:bold;">{{ $application->verification_code ?? 'GEÇERSİZ/TASLAK' }}</span>
            | KONTROL ADRESİ: <b>aykome.eyyubiye.bel.tr/dogrulama</b>
        </div>

    </div>

    @if($application->isMuhtelif())
    <!-- EK-1: KAZI ADRESLERİ LİSTESİ (yalnızca muhtelif/çok sokaklı başvurularda) -->
    <div class="a4-container ek-sayfa">
        <div class="ek-baslik" contenteditable="true">EK-1: KAZI ADRESLERİ LİSTESİ</div>
        <table class="ek-tablo">
            @foreach($application->streetLinesGroupedByMahalle() as $mahalle => $sokaklar)
                @php $mahalleUst = strtoupper($mahalle); $mahalleSon = preg_replace('/[İIıi]/u', 'I', $mahalleUst); @endphp
                <tr><td colspan="2" style="background: #eef2f5; font-weight: 800; font-size: 14px; text-align: center; border-bottom: 2px solid #333;" contenteditable="true">{{ $mahalleUst }}{{ str_ends_with($mahalleSon, 'MAHALLE') || str_ends_with($mahalleSon, 'MAHALLESI') ? '' : ' MAHALLESİ' }}</td></tr>
                @foreach($sokaklar as $sokak)
                    <tr>
                        <td contenteditable="true">{{ $sokak }}</td>
                        <td></td>
                    </tr>
                @endforeach
            @endforeach
        </table>
    </div>
    @endif

    <!-- Vanilla JS Mini Format Toolbar -->
    <div class="toolbar no-print">
        <button onclick="fmtCmd('bold')" title="Kalın (Ctrl+B)"><b>B</b></button>
        <button onclick="fmtCmd('italic')" title="İtalik (Ctrl+I)"><i>I</i></button>
        <button onclick="fmtCmd('underline')" title="Altı Çizili (Ctrl+U)"><u>U</u></button>
        <span class="sep"></span>
        <button onclick="fmtSize(1)" title="Yazıyı Büyüt">A+</button>
        <button onclick="fmtSize(-1)" title="Yazıyı Küçült">A−</button>
    </div>

    <script>
    function fmtCmd(cmd) {
        document.execCommand(cmd, false, null);
    }
    function fmtSize(delta) {
        var sel = document.getSelection();
        var el = sel && sel.anchorNode
            ? (sel.anchorNode.nodeType === 3 ? sel.anchorNode.parentNode : sel.anchorNode)
            : null;
        var size = 3;
        if (el && el.style && el.style.fontSize) {
            var px = parseFloat(el.style.fontSize);
            if (!isNaN(px)) size = Math.min(7, Math.max(1, Math.round(px / 2)));
        }
        size = Math.min(7, Math.max(1, size + delta));
        document.execCommand('fontSize', false, String(size));
        if (el && el.focus) el.focus();
    }
    </script>
</body>
</html>
