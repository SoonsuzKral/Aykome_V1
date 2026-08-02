<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Altyapı Tesisi Açım Ruhsatı</title>
    <style>
        /* DAHA FERAH, DOLGUN VE ESNEMİŞ A4 GÖRÜNÜMÜ */
        body { font-family: Arial, sans-serif; font-size: 12px !important; line-height: 1.25; margin: 0; color:#000; background:#f0f0f0; display:flex; justify-content:center; }
        
        /* Ekranda göreceğimiz sınırlandırılmış a4 kağıt boyutu */
        .a4-container { background: white; width: 210mm; min-height: 297mm; box-sizing: border-box; padding: 12mm 15mm; margin: 20px auto; box-shadow: 0px 5px 15px rgba(0,0,0,0.5); }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 7px; table-layout: fixed; }
        table, th, td { border: 1px solid #000; }
        /* ESNEME NOKTASI: td Paddinglerini artırarak tabloyu A4 uzunluğuna yastıklıyoruz */
        td { padding: 4px; vertical-align: middle; font-size: 12px !important; } 
        
        .bg-grey { background-color: #f2f2f2; font-weight: bold; text-align: center; }
        .font-bold { font-weight: bold; } .text-center { text-align: center; } .text-right { text-align: right; }
        
        /* Şartlar Kısmını A4 Dibini İtecek Şekilde Açtık */
        .sartlar-metni { font-size: 10.5px; padding: 8px 10px !important; line-height: 1.45; text-align: justify;}
        
        .print-bar { position: fixed; top: 0; right:0; background:#1e293b; padding:12px; width:100%; text-align:center;}
        .print-btn { background:#3b82f6; color:#fff; border:none; padding:10px 25px; font-size:14px; border-radius:5px; font-weight:bold; cursor:pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.3);}
        .print-btn:hover { background: #2563eb; }
        
        /* YAZICININ MOTORUNU KİLİTLEYEN EN ÖNEMLİ BASKI CSS'İ */
        @page { size: A4 portrait; margin: 6mm; }
        @media print { 
            body { background:#fff; margin:0 !important; font-size: 12px !important; } 
            .print-bar { display:none !important; } 
            .a4-container { width: 100% !important; box-shadow:none; padding: 2mm 5mm 0mm 5mm !important; margin:0; border:none; overflow:hidden; page-break-inside: avoid; height: 297mm; } 
        }
    </style>
</head>
<body>

    <!-- BACKEND HESABINI DİNLİYORUZ! Blade matematigi YAPILMIYOR -->
    @php
        $sl = collect($application->surfaceLines)->keyBy(function($item) { return trim(mb_strtoupper($item->surfaceType->name ?? '', 'UTF-8')); });
        if (! function_exists('sv')) {
            function sv($sl, $isim) { return isset($sl[$isim]) ? $sl[$isim] : null; }
        }
    @endphp

    <div class="print-bar no-print"><button onclick="window.print()" class="print-btn">🖨️ Yazdır / Kaydet</button></div>
    <div class="a4-container">
        
        <!-- 1. BÖLÜM HEADER -->
        <table style="border: 2px solid #000;">
            <tr>
                <td class="text-center font-bold" style="padding: 5px;">
                    T.C. <br>EYYÜBİYE BELEDİYE BAŞKANLIĞI <br>Fen İşleri Müdürlüğü (AYKOME) <br> <span style="font-size: 14.5px; text-decoration: underline;">ALTYAPI TESİSİ AÇIM RUHSATI</span>
                </td>
                <td style="width: 25%; font-size: 10px;">
                    Tarih : {{ $application->updated_at ? $application->updated_at->format('d.m.Y') : date('d.m.Y') }}<br>
                    Ruh. No: {{ date('Y') }}/{{ str_pad($application->id, 4, '0', STR_PAD_LEFT) }}
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="font-bold" style="width: 20%;">TALEP SAHİBİ</td>
                <td colspan="5">: {{ mb_strtoupper($application->institution?->name ?? $application->applicant_name, 'UTF-8') }}</td>
            </tr>
            <tr><td class="font-bold">İLÇE</td><td colspan="5">: EYYÜBİYE</td></tr>
            <tr><td class="font-bold">ADRES</td><td colspan="5">: {{ mb_substr(strip_tags($application->address), 0, 95) }}</td></tr>
            <tr>
                <td class="font-bold">AÇIM AMACI</td>
                <td colspan="2" style="width: 40%;">: {{ mb_strtoupper($application->project_name ?? 'ALTYAPI', 'UTF-8') }}</td>
                <td class="font-bold text-center" style="width: 14%;">ADA NO : {{ $application->ada ?? '-' }}</td>
                <td class="font-bold text-center" style="width: 13%;">PAR NO : {{ $application->parsel ?? '-' }}</td>
                <td class="font-bold text-center" style="width: 13%;">EV NO : {{ $application->ev_no ?? '-' }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <td style="border-right: none; width:17%;" class="font-bold">BAŞLAMA TARİHİ<br>BİTİŞ TARİHİ</td>
                <td style="border-left: none;">: {{ \Carbon\Carbon::parse($application->start_date ?? now())->format('d.m.Y') }} <br>: {{ \Carbon\Carbon::parse($application->end_date ?? now())->format('d.m.Y') }}</td>
                <td style="border-right: none; width:22%;" class="font-bold text-right">SÜRE UZATIMI &nbsp;:<br>BAŞLAMA TARİHİ :<br>BİTİŞ TARİHİ &nbsp;:</td>
                <td style="border-left: none;"> <br>.......<br>.......</td>
            </tr>
            <tr>
                <td class="font-bold">TANZİM EDEN</td>
                <td class="font-bold">: {{ mb_strtoupper($application->user?->name ?? 'GÖREVLİ', 'UTF-8') }}</td>
                <td class="font-bold text-right" style="border-right:none;">TANZİM TARİHİ</td>
                <td class="font-bold text-left" style="border-left:none;">: {{ date('d.m.Y') }}</td>
            </tr>
        </table>

        <table style="border: 2px solid #000;">
            <tr class="bg-grey">
                <td style="width: 29%;">AÇILACAK ZEMİN</td><td style="width: 8%;">BİRİM</td><td style="width: 11%;">MİKTAR</td><td style="width: 15%;">TUTAR</td>
                <td style="width: 22%; border-left: 3px solid black;">DİĞER BEDELLER</td><td style="width: 15%;">TOPLAM</td>
            </tr>
            <tr>
                <td>ASFALT (SICAK)</td> <td class="text-center">m2</td> <td class="text-center">{{ sv($sl, 'ASFALT (SICAK KARIŞIM)') ? number_format(sv($sl, 'ASFALT (SICAK KARIŞIM)')->quantity,2,',','.') : '0,00' }}</td> <td class="text-right">{{ sv($sl, 'ASFALT (SICAK KARIŞIM)') ? number_format(sv($sl, 'ASFALT (SICAK KARIŞIM)')->amount,2,',','.') : '0,00' }}</td>
                <td style="border-left: 3px solid black;">KDV (%20)</td> 
                <td class="text-right font-bold">{{ number_format($application->calculated_kdv ?? 0, 2, ',', '.') }} TL</td> 
            </tr>
            <tr>
                <td>ASFALT (SOĞUK)</td> <td class="text-center">m2</td> <td class="text-center">{{ sv($sl, 'ASFALT (SOĞUK ASFALT)') ? number_format(sv($sl, 'ASFALT (SOĞUK ASFALT)')->quantity,2,',','.') : '0,00' }}</td> <td class="text-right">{{ sv($sl, 'ASFALT (SOĞUK ASFALT)') ? number_format(sv($sl, 'ASFALT (SOĞUK ASFALT)')->amount,2,',','.') : '0,00' }}</td>
                <td style="border-left: 3px solid black;">RUHSAT HARCI</td> 
                <td class="text-right font-bold">{{ number_format($application->calculated_license_fee ?? 0, 2, ',', '.') }} TL</td>
            </tr>
            <tr>
                <td>PARKE</td> <td class="text-center">m2</td> <td class="text-center">{{ sv($sl, 'PARKE') ? number_format(sv($sl, 'PARKE')->quantity,2,',','.') : '0,00' }}</td> <td class="text-right">{{ sv($sl, 'PARKE') ? number_format(sv($sl, 'PARKE')->amount,2,',','.') : '0,00' }}</td>
                <td style="border-left: 3px solid black;">KEŞİF BEDELİ</td> 
                <td class="text-right font-bold">{{ number_format($application->calculated_discovery_fee ?? 0, 2, ',', '.') }} TL</td>
            </tr>
            <tr>
                <td>BETON</td> <td class="text-center">m2</td> <td class="text-center">{{ sv($sl, 'BETON') ? number_format(sv($sl, 'BETON')->quantity,2,',','.') : '0,00' }}</td> <td class="text-right">{{ sv($sl, 'BETON') ? number_format(sv($sl, 'BETON')->amount,2,',','.') : '0,00' }}</td>
                <td style="border-left: 3px solid black;">ZTB TOPLAM</td> 
                <td class="text-right font-bold">{{ number_format($application->calculated_ztb_total ?? 0, 2, ',', '.') }} TL</td>
            </tr>
            <tr>
                <td>STABİLİZE</td> <td class="text-center">m2</td> <td class="text-center">{{ sv($sl, 'STABİLİZE') ? number_format(sv($sl, 'STABİLİZE')->quantity,2,',','.') : '0,00' }}</td> <td class="text-right">{{ sv($sl, 'STABİLİZE') ? number_format(sv($sl, 'STABİLİZE')->amount,2,',','.') : '0,00' }}</td>
                <td style="border-left: 3px solid black;">TEMİNAT</td> 
                <td class="text-right font-bold">{{ number_format($application->calculated_deposit ?? 0, 2, ',', '.') }} TL</td>
            </tr>
            <tr>
                <td>TRETUAR (PARKE P)</td><td class="text-center">m2</td> <td class="text-center">{{ sv($sl, 'TRETUAR (PARKE PRİZMA)') ? number_format(sv($sl, 'TRETUAR (PARKE PRİZMA)')->quantity,2,',','.') : '0,00' }}</td> <td class="text-right">{{ sv($sl, 'TRETUAR (PARKE PRİZMA)') ? number_format(sv($sl, 'TRETUAR (PARKE PRİZMA)')->amount,2,',','.') : '0,00' }}</td>
                <td rowspan="3" class="text-center bg-grey" style="border-left: 3px solid black; font-size:14px;">GENEL TOPLAM</td>
                <td rowspan="3" class="text-center bg-grey font-bold" style="font-size:14px;">{{ number_format($application->calculated_general_total ?? 0, 2, ',', '.') }} TL</td>
            </tr>
            <tr>
                <td>TRETUAR (KARO)</td> <td class="text-center">m2</td> <td class="text-center">{{ sv($sl, 'TRETUAR (KARO)') ? number_format(sv($sl, 'TRETUAR (KARO)')->quantity,2,',','.') : '0,00' }}</td> <td class="text-right">{{ sv($sl, 'TRETUAR (KARO)') ? number_format(sv($sl, 'TRETUAR (KARO)')->amount,2,',','.') : '0,00' }}</td>
            </tr>
            <tr>
                <td>TRETUAR (MERMER)</td> <td class="text-center">m2</td> <td class="text-center">{{ sv($sl, 'TRETUAR (MERMER)') ? number_format(sv($sl, 'TRETUAR (MERMER)')->quantity,2,',','.') : '0,00' }}</td> <td class="text-right">{{ sv($sl, 'TRETUAR (MERMER)') ? number_format(sv($sl, 'TRETUAR (MERMER)')->amount,2,',','.') : '0,00' }}</td>
            </tr>
            <tr>
                <td>TRETUAR (BAZALT)</td> <td class="text-center">m2</td> <td class="text-center">{{ sv($sl, 'TRETUAR (BAZALT)') ? number_format(sv($sl, 'TRETUAR (BAZALT)')->quantity,2,',','.') : '0,00' }}</td> <td class="text-right">{{ sv($sl, 'TRETUAR (BAZALT)') ? number_format(sv($sl, 'TRETUAR (BAZALT)')->amount,2,',','.') : '0,00' }}</td>
                <td style="border-left: 3px solid black;" class="font-bold text-center">ZTB MAK & TRH</td> <td class="font-bold text-center" style="font-size:10px;">{{ $application->ztb_receipt_info ?? '---' }}</td>
            </tr>
            <tr>
                <td>BORDÜR (BETON)</td> <td class="text-center">m</td> <td class="text-center">{{ sv($sl, 'BORDÜR (BETON)') ? number_format(sv($sl, 'BORDÜR (BETON)')->quantity,2,',','.') : '0,00' }}</td> <td class="text-right">{{ sv($sl, 'BORDÜR (BETON)') ? number_format(sv($sl, 'BORDÜR (BETON)')->amount,2,',','.') : '0,00' }}</td>
                <td style="border-left: 3px solid black;" class="font-bold text-center">TEM MAK & TRH</td> <td class="font-bold text-center" style="font-size:10px;">{{ $application->deposit_receipt_info ?? '---' }}</td>
            </tr>
            <tr>
                <td>BORDÜR (BAZALT)</td> <td class="text-center">m</td> <td class="text-center">{{ sv($sl, 'BORDÜR (BAZALT)') ? number_format(sv($sl, 'BORDÜR (BAZALT)')->quantity,2,',','.') : '0,00' }}</td> <td class="text-right">{{ sv($sl, 'BORDÜR (BAZALT)') ? number_format(sv($sl, 'BORDÜR (BAZALT)')->amount,2,',','.') : '0,00' }}</td>
                <td colspan="2" rowspan="4" style="border-left: 3px solid black; background:#fff;"></td>
            </tr>
            <tr>
                <td>ÇİM</td> <td class="text-center">m2</td> <td class="text-center">{{ sv($sl, 'ÇİM') ? number_format(sv($sl, 'ÇİM')->quantity,2,',','.') : '0,00' }}</td> <td class="text-right">{{ sv($sl, 'ÇİM') ? number_format(sv($sl, 'ÇİM')->amount,2,',','.') : '0,00' }}</td>
            </tr>
            <tr>
                <td>TOPRAK</td> <td class="text-center">m2</td> <td class="text-center">{{ sv($sl, 'TOPRAK') ? number_format(sv($sl, 'TOPRAK')->quantity,2,',','.') : '0,00' }}</td> <td class="text-right">{{ sv($sl, 'TOPRAK') ? number_format(sv($sl, 'TOPRAK')->amount,2,',','.') : '0,00' }}</td>
            </tr>
            <tr>
                <td>GÖRM. ENG. KARO</td> <td class="text-center">m</td> <td class="text-center">{{ sv($sl, 'GÖRME ENGELLİ KARO') ? number_format(sv($sl, 'GÖRME ENGELLİ KARO')->quantity,2,',','.') : '0,00' }}</td> <td class="text-right">{{ sv($sl, 'GÖRME ENGELLİ KARO') ? number_format(sv($sl, 'GÖRME ENGELLİ KARO')->amount,2,',','.') : '0,00' }}</td>
            </tr>
            <tr class="bg-grey" style="border-top:2px solid #000;">
                <td class="text-center font-bold">ZEMİN TAHRİP B. (ZTB)</td><td class="text-center">m2/m</td><td class="text-center">{{ number_format(collect($application->surfaceLines)->sum('quantity'),2,',','.') }}</td><td class="text-right font-bold">{{ number_format(collect($application->surfaceLines)->sum('amount'),2,',','.') }} TL</td>
                <td colspan="2"></td>
            </tr>
        </table>

        <div style="font-weight:bold; font-size:12px; margin:2px 0;">ÖZEL ŞARTLAR</div>
        <table style="font-size: 11px;">
            <tr>
                <td style="padding: 3px 5px; line-height: 1.3; text-align: left; font-size: 11px !important;">
                    <b>1-</b> KAZIYA BAŞLAMADAN ÖNCE DİĞER ALTYAPI... TEDBİRLERİ ALINACAKTIR.<br>
                    <b>2-</b> ASFALT OLAN ZEMİNLERDE ASFALT KESME MAKİNASI KULLANILACAKTIR.<br>
                    <b>3-</b> KIŞ SEZONUNDA TAHRİP EDİLEN TOPRAK HARİCİ DİĞER ZEMİNLER BETONLANACAKTIR.<br>
                    <b>4-</b> GENEL KURUL KARARI GEREĞİ KAZI GENİŞLİĞİ EN AZ 0,60 (ALTMIŞ) CM DİR.<br>
                    <b>5-</b> RUHSAT TARİHİNDEN İTİBAREN 2 YIL İÇERİSİNDE ALINMAYAN TEMİNATLAR GELİR KAYDEDİLİR.<br>
                    <b>6-</b> RUHSAT SÜRESİ SONRA DOĞMUŞ/DOĞACAK TAZMİNAT BELEDİYEMİZE SAKLIDIR.<br>
                    <b>7-</b> İLAVE KAZILAR İÇİN EK RUHSAT ŞARTTIR.<br>
                    <b>8-</b> STABİLİZE HARİCİ ZEMİNLERDE ÇIKAN HAFRİYAT DOĞRUDAN YÜKLENECEKTİR. DOLGUDA STABİLİZE ZORUNLUDUR.<br>
                    <b>9-</b> AYKOME YÖNETMELİĞİNİ OKUDUM ŞARTLARI KABUL EDİYORUM.
                </td>
            </tr>
        </table>

        <!-- İMZALAR (KOMPAKT MİMAMRİ / BOŞLUKSUZ / DARALTILMIŞ TABLO YÜKSEKLİĞİ) -->
        <table style="margin-top: 1px; table-layout: fixed; margin-bottom: 0px;">
            <tr class="bg-grey font-bold text-center">
                <td style="width:25%; padding: 2px;" colspan="2">YAPILACAK İŞİN FENNİ MESULÜ</td>
                <td style="width:37%; padding: 2px;">AYKOME BİRİMİ</td>
                <td style="width:38%; padding: 2px;">EYYÜBİYE BELEDİYESİ</td>
            </tr>
            <tr>
                <td class="font-bold" style="width: 7%; padding: 2px;">FİRMA</td>
                <td style="width: 21%; font-size: 11px; font-weight:bold; padding: 2px;">{{ mb_strtoupper($application->institution?->name ?? 'Şahsi Başvuru', 'UTF-8') }}</td>
                <td rowspan="4" class="text-center" style="vertical-align: middle; padding: 0;">
                    <div style="margin-top: 5px;">{{ $signatories['aykome_sorumlusu']['ad_soyad'] ?? 'Yetkili' }}</div>
                    <div style="font-weight:bold; margin-top: 2px; margin-bottom:5px;">{{ $signatories['aykome_sorumlusu']['unvan'] ?? 'AYKOME Birim Sorumlusu' }}</div>
                </td>
                <td rowspan="4" class="text-center" style="vertical-align: middle; padding: 0;">
                    <div style="margin-top: 5px;">{{ $signatories['fen_isleri_muduru']['ad_soyad'] ?? 'Yetkili' }}</div>
                    <div style="font-weight:bold; margin-top: 2px; margin-bottom:5px;">{{ $signatories['fen_isleri_muduru']['unvan'] ?? 'Fen İşleri Müdürü' }}</div>
                </td>
            </tr>
            <tr><td class="font-bold" style="padding: 2px;">SORUMLU</td> <td style="font-size:11px; padding: 2px;">{{ mb_strtoupper(trim($application->tesis_sorumlusu ?? $application->institution?->tesis_sorumlusu_adi ?? 'Yetkili Görevli'), 'UTF-8') }}</td></tr>
            <tr><td class="font-bold" style="padding: 2px;">TELEFON</td> <td style="font-size:11px; padding: 2px;">{{ $application->applicant_phone ?? '-' }}</td></tr>
            <tr><td class="font-bold" style="padding: 2px;">İMZA</td> <td style="height: 12px; padding: 0px;"></td></tr>
            
            <tr style="background:#e5e5e5; font-size: 11px;">
                <td colspan="4" class="text-center font-bold" style="padding: 1px;">ALTYAPI TESİSİ AÇIMI İLE İLGİLİ UYULMASI GEREKEN ŞARTLAR</td>
            </tr>
            <tr>
                <td colspan="4" style="text-align: left; font-size: 10px; padding: 2px 4px; line-height: 1.15;">
                    <span class="font-bold">1 - Altyapı:</span> Yol üst kaplaması altında kalan yol kısmı ile İçme suyu... tüm tesisleri kapsar.<br>
                    <span class="font-bold">2 - EYYÜBİYE BELEDİYESİ:</span> Altyapı tesisi ile ilgili tüm işlemler... ŞANLIURFA BYŞ BELEDİYESİ AYKOME biriminin verilecek izne göre yürütülür.
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
