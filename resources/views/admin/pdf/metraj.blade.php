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

        .print-bar { position: fixed; top: 0; left:0; right:0; background:#1e293b; padding:12px; width:100%; text-align:right;}
        .print-btn { background:#3b82f6; color:#fff; border:none; padding:10px 20px; font-size:14px; border-radius:5px; font-weight:bold; cursor:pointer;}

        @page { size: A4 landscape; margin: 8mm !important; }
        @media print {
            body { background:#fff; margin:0 !important; padding: 0 !important; }
            .print-bar { display:none !important; }
            .a4-landscape-container { width: 100% !important; min-height: auto; box-shadow:none; padding: 0mm !important; margin:0 auto; border:none;}
            td, th { padding: 5px 6px !important;}
        }
    </style>
</head>
<body>
    <div class="print-bar no-print"><button onclick="window.print()" class="print-btn">🖨️ Yazdır / PDF Kaydet</button></div>

    <div class="a4-landscape-container">
        <div class="table-wrapper">
            <div class="top-header">
                {{ $kurum ?? 'KURUM ADI' }} <br> ŞANLIURFA İL MÜDÜRLÜĞÜ <br> PROJE TESİS YÖNETİCİLİĞİ <br>
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
                    <th style="width:13%;">PROJE KODU</th>
                </tr>

                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row['sira'] ?? $loop->iteration }}</td>
                        <td>{{ $row['ilce'] ?? 'EYYÜBİYE' }}</td>
                        <td style="text-align: left; padding-left: 8px;">{{ mb_substr($row['mahalle'] ?? '', 0, 45) }}</td>
                        <td>{{ $row['cadde'] ?? 'Genel Tesis Yolu' }}</td>
                        <td>{{ $row['tarih'] ?? '' }}</td>
                        <td>{{ $row['genislik'] ?? '0,00' }}</td>
                        <td>{{ $row['uzunluk'] ?? '0,00' }}</td>
                        <td>{{ $row['m2'] ?? '0,00' }}</td>
                        <td>{{ $row['zemin'] ?? 'BİLİNMİYOR' }}</td>
                        <td>{{ $row['proje_kodu'] ?: ($proje_kodu ?: '00000') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" style="padding:15px; color:#555;">(Seçili Metraj Sahası Veritabanında Bulunmuyor veya Metraj aşamasındasınız)</td></tr>
                @endforelse

                <tr>
                    <td colspan="7" style="text-align: right; padding-right:15px;">TOPLAM M² : </td>
                    <td>{{ $toplam_m2 ?? '0,00' }}</td>
                    <td colspan="2"></td>
                </tr>
            </table>
        </div>

        <table class="sign-layout">
            <tr>
                <td style="width:50%; padding-left:20px;">
                    <table class="mini-sign">
                        <tr><th>KURUM/KURULUŞ</th></tr>
                        <tr>
                            <td>
                                {{ $talep_sahibi ?: 'Kurum Sorumlusu' }}<br>
                                <span style="font-size: 11px; font-weight:normal;">İl Tesis Mühendisi</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:50%; padding-right:20px;">
                    <table class="mini-sign">
                        <tr><th>AYKOME BİRİMİ</th></tr>
                        <tr>
                            <td>
                                Mahmut DOĞAN<br>
                                <span style="font-size: 11px; font-weight:normal;">Aykome Birim Sorumlusu</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

    </div>
</body>
</html>
