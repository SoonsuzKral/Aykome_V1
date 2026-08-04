<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kazı Metraj Cetveli ve Onay</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; background: #cbd5e1; display:flex; justify-content:center; padding-top:20px; }
        .a4-landscape-container { background: white; width: 297mm; min-height: 210mm; box-sizing: border-box; padding: 15mm; margin: 15px auto; box-shadow: 0px 5px 15px rgba(0,0,0,0.5); overflow:hidden;}

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

        .print-bar { position: fixed; top: 1rem; left: 1rem; right: auto; z-index: 99999; background:#1e293b; padding:8px 14px; border-radius:10px; box-shadow:0 5px 14px rgba(0,0,0,.35); display:flex; flex-direction:row; gap:8px; align-items:center; text-align:right;}
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
        // CELL-BASED AUTH: Belediye hücreleri (başlık/AYKOME imzası) altkuruma kilitli;
        // satırlar (cadde/mahalle/zemin) ve kurum imzası her iki tarafa açık.
        $isMuni = auth()->check() && auth()->user()->isMunicipalityPersonel();
    @endphp

    <div class="a4-landscape-container">
        <div class="table-wrapper">
            <div class="top-header" contenteditable="{{ $isMuni ? 'true' : 'false' }}">
                {{ $alici ?? 'EYYÜBİYE BELEDİYESİ FEN İŞLERİ MÜDÜRLÜĞÜ AYKOME BİRİMİ' }}
            </div>

            <table class="metraj-table">
                <tr>
                    <th style="width:3%;">SIRA</th>
                    <th style="width:8%;">İLÇE</th>
                    <th style="width:18%;">MAHALLE</th>
                    <th style="width:15%;">CADDE VE SOKAK</th>
                    <th style="width:12%;">KAZI BAŞLANGIÇ TARİHİ</th>
                    <th style="width:7%;">GENİŞLİK</th>
                    <th style="width:7%;">UZUNLUK</th>
                    <th style="width:7%;">M² / M</th>
                    <th style="width:10%;">ZEMİN CİNSİ</th>
                    <th style="width:13%;">PROJE / İŞİN ADI</th>
                </tr>

                @forelse($rows as $row)
                    <tr>
                        <td contenteditable="true">{{ $row['sira'] ?? $loop->iteration }}</td>
                        <td contenteditable="true">{{ $row['ilce'] ?? '' }}</td>
                        <td style="text-align: left; padding-left: 8px;" contenteditable="true">{{ mb_substr($row['mahalle'] ?? '', 0, 45) }}</td>
                        <td contenteditable="true">{{ $row['cadde'] ?? '' }}</td>
                        <td contenteditable="true">{{ $row['tarih'] ?? '' }}</td>
                        <td contenteditable="true">{{ $row['genislik'] ?? '0,00' }}</td>
                        <td contenteditable="true">{{ $row['uzunluk'] ?? '0,00' }}</td>
                        <td contenteditable="true">{{ $row['m2'] ?? '0,00' }}</td>
                        <td contenteditable="true">{{ $row['zemin'] ?? '' }}</td>
                        <td contenteditable="true">{{ $row['proje_kodu'] ?: ($proje_kodu ?? '') }}</td>
                    </tr>
                @empty
                    @for($i = 1; $i <= 4; $i++)
                    <tr>
                        <td contenteditable="true">{{ $i }}</td>
                        <td contenteditable="true"></td><td contenteditable="true"></td><td contenteditable="true"></td>
                        <td contenteditable="true"></td><td contenteditable="true"></td><td contenteditable="true"></td>
                        <td contenteditable="true"></td><td contenteditable="true"></td>
                        <td contenteditable="true">{{ $proje_kodu ?? '' }}</td>
                    </tr>
                    @endfor
                @endforelse

                <tr>
                    <td colspan="7" style="text-align: right; padding-right:15px;">TOPLAM M² : </td>
                    <td contenteditable="true">{{ $toplam_m2 ?? '0,00' }}</td>
                    <td colspan="2"></td>
                </tr>
            </table>
        </div>

        <table class="sign-layout">
            <tr>
                <td style="width:50%; padding-left:20px;">
                    <table class="mini-sign">
                        <tr><th contenteditable="true">KURUM/KURULUŞ</th></tr>
                        <tr>
                            <td contenteditable="true">
                                {{ $talep_sahibi ?? '' }}<br>
                                <span style="font-size: 11px; font-weight:normal;">{{ $signatories['tesis_sorumlusu']['unvan'] ?? '' }}</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:50%; padding-right:20px;">
                    <table class="mini-sign">
                        <tr><th contenteditable="{{ $isMuni ? 'true' : 'false' }}">AYKOME BİRİMİ</th></tr>
                        <tr>
                            <td contenteditable="{{ $isMuni ? 'true' : 'false' }}">
                                {{ $signatories['aykome_sorumlusu']['ad_soyad'] ?? '' }}<br>
                                <span style="font-size: 11px; font-weight:normal;">{{ $signatories['aykome_sorumlusu']['unvan'] ?? 'Aykome Birim Sorumlusu' }}</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

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
