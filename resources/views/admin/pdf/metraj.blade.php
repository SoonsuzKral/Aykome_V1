<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kazı Metraj Cetveli ve Onay</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; background: #cbd5e1; display:block; padding-top:0; }
        .a4-landscape-container { background: white; width: 297mm; min-height: 210mm; box-sizing: border-box; padding: 15mm; margin: 0 auto; box-shadow: 0px 5px 15px rgba(0,0,0,0.5); overflow:hidden;}

        .table-wrapper { border: 2px solid #000; padding: 0px; margin-bottom: 25px;}
        .top-header { text-align: center; font-weight: bold; font-size: 13.5px; line-height: 1.4; margin: 18px 0; }

        table.metraj-table { width: 100%; border-collapse: collapse; table-layout: auto;}
        table.metraj-table th, table.metraj-table td { border: 1px solid #000; padding: 4px 6px; text-align: center; font-size:10.5px; }
        table.metraj-table th { font-weight: bold; }

        table.sign-layout { width: 100%; margin-top: 10px; border:none; table-layout: fixed;}
        table.sign-layout td { border:none; padding: 0; text-align:center;}

        table.mini-sign { width: 55%; margin: 0 auto; border-collapse: collapse; border: 2px solid #000;}
        table.mini-sign th, table.mini-sign td { border: 1px solid #000; padding: 5px; }
        table.mini-sign th { font-size: 12px; font-weight:bold; }
        table.mini-sign td { height: 50px; vertical-align: bottom; padding-bottom:5px; font-size:12px; font-weight:bold; color: #0f172a;}

        /* ÇÖZÜM_10 §3: fixed → sticky. Akıştan çıkan bar kendi yüksekliğine yer
           ayırmadığı için kâğıdın üst anteti/logosu barın altında kalıyordu. */
        .print-bar { position: sticky; top: 8px; width: fit-content; max-width: calc(100% - 16px); margin: 8px auto 12px; z-index: 50; background: rgba(15,23,42,.92); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); color: #fff; display: flex; align-items: center; gap: 12px; padding: 8px 16px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,.3); border: 1px solid #334155; }
        .print-btn { background:#3b82f6; color:#fff; border:none; padding:10px 20px; font-size:14px; border-radius:5px; font-weight:bold; cursor:pointer;}

        @page { size: A4 landscape; margin: 8mm !important; }
        @media print {
            body { background:#fff; margin:0 !important; padding: 0 !important; }
            .print-bar { display:none !important; }
            .a4-landscape-container { width: 100% !important; min-height: auto; box-shadow:none; padding: 0mm !important; margin:0 auto; border:none;}
            td, th { padding: 5px 6px !important;}
        }
        .no-print { display: flex; }
        @media print { .no-print { display: none !important; } }

        /* Vanilla JS Mini Format Toolbar */
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
    </style>
</head>
<body>
    <div class="print-bar no-print">
        <button onclick="window.print()" class="print-btn">🖨️ Yazdır / PDF Kaydet</button>
        <button onclick="window.print()" class="print-btn">💾 Şablonu Düzenle (Kaydet)</button>
    </div>

    @php
        // CELL-BASED AUTH: Belediye hücreleri (başlık/AYKOME imzası + metraj satırları) altkuruma
        // kilitli; TEK istisna en alttaki "KURUM/KURULUŞ (YETKİLİ GÖREVLİ)" imza kutusudur —
        // o kutu her iki tarafça düzenlenebilir (kurumun kendi yetkilisi imzalar).
        // $forceMuni=true sunucu tarafı imza-kaydetme tabanında kullanılır: belediye hücreleri
        // de düzenlenebilir üretilir (böylece kaydedilen içerik belediyece sonradan açılır).
        $isMuni = ($forceMuni ?? false) || (auth()->check() && auth()->user()->isMunicipalityPersonel());
        $c = $isMuni ? 'true' : 'false';
    @endphp

    <div class="a4-landscape-container">
        <div class="table-wrapper">
            <div class="top-header" contenteditable="{{ $c }}">
                {{ $alici ?? 'EYYÜBİYE BELEDİYESİ FEN İŞLERİ MÜDÜRLÜĞÜ AYKOME BİRİMİ' }}
            </div>

            @php
                // KAT sütunu gerekli mi? (Herhangi bir satırda multiplier > 1 varsa)
                $hasKat = collect($rows)->some(fn($r) => !empty($r['kat']) && $r['kat'] !== '' && $r['kat'] !== '1');
                // AÇIKLAMA: toplam satırının hemen üstünde tek satır olarak gösterilir
                $katiAciklamaText = $application->kati_aciklama ?? '';
                if (!$katiAciklamaText) {
                    $katiAciklamaText = collect($rows)->filter(fn($r) => !empty($r['aciklama']))->pluck('aciklama')->unique()->implode(' | ');
                }
                // Toplam kolon sayısı: 10 (KAT yoksa) veya 11 (KAT varsa)
                $totalCols = 10 + ($hasKat ? 1 : 0);
                // TOPLAM satırında M² değerinden önce kaç kolon boş
                $toplamColspan = 7 + ($hasKat ? 1 : 0); // SIRA..UZUNLUK + (KAT varsa KAT kolonu)
            @endphp
            <table class="metraj-table">
                <tr>
                    <th contenteditable="{{ $c }}" style="width:3%;">SIRA</th>
                    <th contenteditable="{{ $c }}" style="width:7%;">İLÇE</th>
                    <th contenteditable="{{ $c }}" style="width:15%;">MAHALLE</th>
                    <th contenteditable="{{ $c }}" style="width:13%;">CADDE VE SOKAK</th>
                    <th contenteditable="{{ $c }}" style="width:10%;">KAZI BAŞLANGIÇ TARİHİ</th>
                    <th contenteditable="{{ $c }}" style="width:7%;">GENİŞLİK</th>
                    <th contenteditable="{{ $c }}" style="width:7%;">UZUNLUK</th>
                    @if($hasKat)
                    <th contenteditable="{{ $c }}" style="width:5%;background:#fffbeb;">KAT</th>
                    <th contenteditable="{{ $c }}" style="width:8%;background:#fffbeb;">M² (EFEKTİF)</th>
                    @else
                    <th contenteditable="{{ $c }}" style="width:8%;">M² / M</th>
                    @endif
                    <th contenteditable="{{ $c }}" style="width:11%;">ZEMİN CİNSİ</th>
                    <th contenteditable="{{ $c }}" style="width:14%;">PROJE / İŞİN ADI</th>
                </tr>

                @forelse($rows as $row)
                    <tr data-aykome-surface="{{ $row['zemin'] ?? '' }}">
                        <td data-aykome-col="sira" contenteditable="{{ $c }}">{{ $row['sira'] ?? $loop->iteration }}</td>
                        <td data-aykome-col="ilce" contenteditable="{{ $c }}">{{ $row['ilce'] ?? '' }}</td>
                        <td style="text-align: left; padding-left: 8px;" contenteditable="{{ $c }}">{{ mb_substr($row['mahalle'] ?? '', 0, 45) }}</td>
                        <td data-aykome-col="cadde" contenteditable="{{ $c }}">{{ $row['cadde'] ?? '' }}</td>
                        <td data-aykome-col="tarih" contenteditable="{{ $c }}">{{ $row['tarih'] ?? '' }}</td>
                        <td data-aykome-col="genislik" contenteditable="{{ $c }}">{{ $row['genislik'] ?? '0,00' }}</td>
                        <td data-aykome-col="uzunluk" contenteditable="{{ $c }}">{{ $row['uzunluk'] ?? '0,00' }}</td>
                        @if($hasKat)
                        <td data-aykome-col="kat" contenteditable="{{ $c }}" style="background:#fffbeb;font-weight:bold;">{{ $row['kat'] ?? '' }}</td>
                        <td data-aykome-col="m2" class="sync-dom-value sync-miktar-td" data-id="{{ $row['surface_line_id'] ?? '' }}" data-type="miktar" contenteditable="{{ $c }}" style="background:#fffbeb;">{{ $row['efektif_m2'] ?? $row['m2'] ?? '0,00' }}</td>
                        @else
                        <td data-aykome-col="m2" class="sync-dom-value sync-miktar-td" data-id="{{ $row['surface_line_id'] ?? '' }}" data-type="miktar" contenteditable="{{ $c }}">{{ $row['m2'] ?? '0,00' }}</td>
                        @endif
                        <td data-aykome-col="zemin" contenteditable="{{ $c }}">{{ $row['zemin'] ?? '' }}</td>
                        <td data-aykome-col="proje" contenteditable="{{ $c }}">{{ $row['proje_kodu'] ?: ($proje_kodu ?? '') }}</td>
                    </tr>
                @empty
                    @for($i = 1; $i <= 4; $i++)
                    <tr data-aykome-surface="">
                        <td data-aykome-col="sira" contenteditable="{{ $c }}">{{ $i }}</td>
                        <td contenteditable="{{ $c }}"></td><td contenteditable="{{ $c }}"></td><td contenteditable="{{ $c }}"></td>
                        <td contenteditable="{{ $c }}"></td><td data-aykome-col="genislik" contenteditable="{{ $c }}"></td><td data-aykome-col="uzunluk" contenteditable="{{ $c }}"></td>
                        <td data-aykome-col="m2" contenteditable="{{ $c }}"></td><td data-aykome-col="zemin" contenteditable="{{ $c }}"></td>
                        <td contenteditable="{{ $c }}">{{ $proje_kodu ?? '' }}</td>
                    </tr>
                    @endfor
                @endforelse

                {{-- AÇIKLAMA SATIRI: TOPLAM'dan hemen önce, tüm genişlikte tek satır --}}
                @if($katiAciklamaText)
                <tr>
                    <td colspan="{{ $totalCols }}" contenteditable="{{ $c }}" style="text-align:left; padding:5px 8px; font-size:9.5px; border-top:1.5px solid #000;">
                        <strong>AÇIKLAMA (KURUL KARARI):</strong> {{ $katiAciklamaText }}
                    </td>
                </tr>
                @endif

                <tr>
                    <td colspan="{{ $toplamColspan }}" contenteditable="{{ $c }}" style="text-align: right; padding-right:15px;">TOPLAM M² : </td>
                    <td data-aykome-fee="toplam_m2" contenteditable="{{ $c }}">{{ $toplam_m2 ?? '0,00' }}</td>
                    <td colspan="2"></td>
                </tr>
            </table>
        </div>

        <table class="sign-layout">
            <tr>
                <td style="width:50%; padding-left:20px;">
                    <table class="mini-sign">
                        <tr><th contenteditable="true" data-sign-editable="1">KURUM/KURULUŞ</th></tr>
                        <tr>
                            <td contenteditable="true" data-sign-editable="1">
                                {{ $talep_sahibi ?? '' }}<br>
                                <span style="font-size: 11px; font-weight:normal;">{{ $signatories['tesis_sorumlusu']['unvan'] ?? '' }}</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:50%; padding-right:20px;">
                    <table class="mini-sign">
                        <tr><th contenteditable="{{ $c }}">AYKOME BİRİMİ</th></tr>
                        <tr>
                            <td contenteditable="{{ $c }}">
                                {{ $signatories['aykome_sorumlusu']['ad_soyad'] ?? '' }}<br>
                                <span style="font-size: 11px; font-weight:normal;">{{ $signatories['aykome_sorumlusu']['unvan'] ?? 'Aykome Birim Sorumlusu' }}</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

    <p style="text-align:center;font-size:8px;color:#888;margin:6px 0 0;">BELGE DOĞRULAMA KODU: <b style="color:#d97706;">{{ $application->verification_code ?? 'GEÇERSİZ/TASLAK' }}</b> | KONTROL ADRESİ: <b>aykome.eyyubiye.bel.tr/dogrulama</b></p>

    </div>

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
