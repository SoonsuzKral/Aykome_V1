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
        
        .print-bar { position: fixed; top: 1rem; left: 1rem; right: auto; z-index: 99999; background:#1e293b; padding:8px 14px; border-radius:10px; box-shadow:0 5px 14px rgba(0,0,0,.35); display:flex; flex-direction:row; gap:8px; align-items:center; text-align:center;}
        .print-btn { background:#3b82f6; color:#fff; border:none; padding:10px 25px; font-size:14px; border-radius:5px; font-weight:bold; cursor:pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.3);}
        .print-btn:hover { background: #2563eb; }
        
/* YAZICININ MOTORUNU KİLİTLEYEN EN ÖNEMLİ BASKI CSS'İ */
        @page { size: A4 portrait; margin: 6mm; }
        @media print { 
            body { background:#fff; margin:0 !important; font-size: 12px !important; } 
            .print-bar { display:none !important; } 
            .a4-container { width: 100% !important; box-shadow:none; padding: 2mm 5mm 0mm 5mm !important; margin:0; border:none; overflow:hidden; page-break-inside: avoid; height: 297mm; } 
        }
        .no-print { display: flex; }
        @media print { .no-print { display: none !important; } }

        /* Ortak kurum logosu + Vanilla JS Mini Format Toolbar */
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
    </style>
</head>
<body>

    <!-- BACKEND HESABINI DİNLİYORUZ! Blade matematigi YAPILMIYOR -->
    @php
        // CELL-BASED AUTH: Belediye personeli tüm alanları düzenler; alt kurum yalnızca
        // kendi (firma/sorumlu) hücrelerine dokunabilir. Makam hücreleri belediyeye kilitli.
        // KURUM İMZA TABANI: signatureSaveBase() belediyeye açık üretim için forceMuni gönderir.
        $isMuni = ($forceMuni ?? false) || (auth()->check() && auth()->user()->isMunicipalityPersonel());
        $application->loadMissing(['institution', 'surfaceLines.surfaceType']);
        $sl = collect($application->surfaceLines)->keyBy(function($item) { return trim(mb_strtoupper($item->surfaceType->name ?? '', 'UTF-8')); });
        if (! function_exists('sv')) {
            function sv($sl, $isim) { return isset($sl[$isim]) ? $sl[$isim] : null; }
        }
    @endphp

    <div class="print-bar no-print">
        <button onclick="window.print()" class="print-btn">🖨️ Yazdır</button>
        <button onclick="window.print()" class="print-btn">💾 Şablonu Düzenle (Kaydet)</button>
    </div>
    <div class="a4-container">
        
        <!-- 1. BÖLÜM HEADER -->
        <!-- CELL-BASED AUTH: Belediye başlığı/tarih/ilçe/tanzim → belediyeye kilitli; talep sahibi/adres → kuruma açık. -->
        <table style="border: 2px solid #000;">
            <tr>
                <td class="text-center font-bold" style="padding: 5px;">
                    <span contenteditable="{{ $isMuni ? 'true' : 'false' }}">T.C.</span> <br><span contenteditable="{{ $isMuni ? 'true' : 'false' }}">EYYÜBİYE BELEDİYE BAŞKANLIĞI</span> <br><span contenteditable="{{ $isMuni ? 'true' : 'false' }}">Fen İşleri Müdürlüğü (AYKOME)</span> <br> <span contenteditable="{{ $isMuni ? 'true' : 'false' }}" style="font-size: 14.5px; text-decoration: underline;">ALTYAPI TESİSİ AÇIM RUHSATI</span>
                </td>
                <td style="width: 25%; font-size: 10px;">
                    <span contenteditable="{{ $isMuni ? 'true' : 'false' }}">Tarih : {{ $application->updated_at ? $application->updated_at->format('d.m.Y') : '' }}</span><br>
                    <span contenteditable="{{ $isMuni ? 'true' : 'false' }}">Ruh. No: {{ date('Y') }}/{{ str_pad($application->id, 4, '0', STR_PAD_LEFT) }}</span>
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="font-bold" style="width: 20%;">TALEP SAHİBİ</td>
                <td colspan="5"><span contenteditable="true">: {{ mb_strtoupper($application->institution?->name ?? $application->applicant_name ?? '', 'UTF-8') }}</span></td>
            </tr>
            <tr><td class="font-bold">İLÇE</td><td colspan="5"><span contenteditable="{{ $isMuni ? 'true' : 'false' }}">: {{ $application->district_name ?? 'EYYÜBİYE' }}</span></td></tr>
            <tr><td class="font-bold">ADRES</td><td colspan="5"><span contenteditable="true">: {{ $application->isMuhtelif() ? 'MUHTELİF CADDE VE SOKAK' : mb_substr(strip_tags($application->address_text ?? ''), 0, 95) }}</span></td></tr>
            <tr>
                <td class="font-bold">AÇIM AMACI</td>
                <td colspan="2" style="width: 40%;"><span contenteditable="{{ $isMuni ? 'true' : 'false' }}">: {{ mb_strtoupper($application->project_name ?? '', 'UTF-8') }}</span></td>
                <td class="font-bold text-center" style="width: 14%;"><span contenteditable="{{ $isMuni ? 'true' : 'false' }}">ADA NO : {{ $application->ada ?? '' }}</span></td>
                <td class="font-bold text-center" style="width: 13%;"><span contenteditable="{{ $isMuni ? 'true' : 'false' }}">PAR NO : {{ $application->parsel ?? '' }}</span></td>
                <td class="font-bold text-center" style="width: 13%;"><span contenteditable="{{ $isMuni ? 'true' : 'false' }}">EV NO : {{ $application->ev_no ?? '' }}</span></td>
            </tr>
        </table>

        <table>
            <tr>
                <td style="border-right: none; width:17%;" class="font-bold">BAŞLAMA TARİHİ<br>BİTİŞ TARİHİ</td>
                <td style="border-left: none;" contenteditable="true">: {{ $application->start_date ? $application->start_date->format('d.m.Y') : '' }} <br>: {{ $application->end_date ? $application->end_date->format('d.m.Y') : '' }}</td>
                <td style="border-right: none; width:22%;" class="font-bold text-right">SÜRE UZATIMI &nbsp;:<br>BAŞLAMA TARİHİ :<br>BİTİŞ TARİHİ &nbsp;:</td>
                <td style="border-left: none;" contenteditable="{{ $isMuni ? 'true' : 'false' }}"> <br>.......<br>.......</td>
            </tr>
            <tr>
                <td class="font-bold">TANZİM EDEN</td>
                <td class="font-bold" contenteditable="{{ $isMuni ? 'true' : 'false' }}">: {{ $application->institution ? (mb_strtoupper(auth()->user()->name ?? 'OSMAN ZAMAN', 'UTF-8')) : (mb_strtoupper($application->applicant_name ?? 'OSMAN ZAMAN', 'UTF-8')) }}</td>
                <td class="font-bold text-right" style="border-right:none;">TANZİM TARİHİ</td>
                <td class="font-bold text-left" style="border-left:none;" contenteditable="{{ $isMuni ? 'true' : 'false' }}">: {{ date('d.m.Y') }}</td>
            </tr>
        </table>

        <table style="border: 2px solid #000;">
            <tr class="bg-grey">
                <td style="width: 29%;">AÇILACAK ZEMİN</td><td style="width: 8%;">BİRİM</td><td style="width: 11%;">MİKTAR</td><td style="width: 15%;">TUTAR</td>
                <td style="width: 22%; border-left: 3px solid black;">DİĞER BEDELLER</td><td style="width: 15%;">TOPLAM</td>
            </tr>
            <tr data-aykome-surface="ASFALT (SICAK KARIŞIM)">
                <td data-aykome-col="ad" contenteditable="{{ $isMuni ? 'true' : 'false' }}">ASFALT (SICAK)</td> <td data-aykome-col="birim" class="text-center" contenteditable="{{ $isMuni ? 'true' : 'false' }}">m2</td> <td data-aykome-col="miktar" class="sync-dom-value sync-miktar-td text-center" data-id="{{ sv($sl, 'ASFALT (SICAK KARIŞIM)')?->id }}" data-type="miktar" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'ASFALT (SICAK KARIŞIM)') ? number_format(sv($sl, 'ASFALT (SICAK KARIŞIM)')->quantity,2,',','.') : '0,00' }}</td> <td data-aykome-col="tutar" class="text-right" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'ASFALT (SICAK KARIŞIM)') ? number_format(sv($sl, 'ASFALT (SICAK KARIŞIM)')->amount,2,',','.') : '0,00' }}</td>
                <td style="border-left: 3px solid black;"><span contenteditable="{{ $isMuni ? 'true' : 'false' }}">KDV (%20)</span></td>
                <td data-aykome-fee="kdv_amount" class="text-right font-bold" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ number_format((float)($application->kdv_amount ?? 0), 2, ',', '.') }} TL</td>
            </tr>
            <tr data-aykome-surface="ASFALT (SOĞUK ASFALT)">
                <td data-aykome-col="ad" contenteditable="{{ $isMuni ? 'true' : 'false' }}">ASFALT (SOĞUK)</td> <td data-aykome-col="birim" class="text-center" contenteditable="{{ $isMuni ? 'true' : 'false' }}">m2</td> <td data-aykome-col="miktar" class="sync-dom-value sync-miktar-td text-center" data-id="{{ sv($sl, 'ASFALT (SOĞUK ASFALT)')?->id }}" data-type="miktar" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'ASFALT (SOĞUK ASFALT)') ? number_format(sv($sl, 'ASFALT (SOĞUK ASFALT)')->quantity,2,',','.') : '0,00' }}</td> <td data-aykome-col="tutar" class="text-right" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'ASFALT (SOĞUK ASFALT)') ? number_format(sv($sl, 'ASFALT (SOĞUK ASFALT)')->amount,2,',','.') : '0,00' }}</td>
                <td style="border-left: 3px solid black;"><span contenteditable="{{ $isMuni ? 'true' : 'false' }}">RUHSAT HARCI</span></td>
                <td data-aykome-fee="license_fee" class="text-right font-bold" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ number_format((float)($application->license_fee ?? 0), 2, ',', '.') }} TL</td>
            </tr>
            <tr data-aykome-surface="PARKE">
                <td data-aykome-col="ad" contenteditable="{{ $isMuni ? 'true' : 'false' }}">PARKE</td> <td data-aykome-col="birim" class="text-center" contenteditable="{{ $isMuni ? 'true' : 'false' }}">m2</td> <td data-aykome-col="miktar" class="sync-dom-value sync-miktar-td text-center" data-id="{{ sv($sl, 'PARKE')?->id }}" data-type="miktar" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'PARKE') ? number_format(sv($sl, 'PARKE')->quantity,2,',','.') : '0,00' }}</td> <td data-aykome-col="tutar" class="text-right" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'PARKE') ? number_format(sv($sl, 'PARKE')->amount,2,',','.') : '0,00' }}</td>
                <td style="border-left: 3px solid black;"><span contenteditable="{{ $isMuni ? 'true' : 'false' }}">KEŞİF BEDELİ</span></td>
                <td data-aykome-fee="discovery_fee" class="text-right font-bold" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ number_format((float)($application->discovery_fee ?? 0), 2, ',', '.') }} TL</td>
            </tr>
            <tr data-aykome-surface="BETON">
                <td data-aykome-col="ad" contenteditable="{{ $isMuni ? 'true' : 'false' }}">BETON</td> <td data-aykome-col="birim" class="text-center" contenteditable="{{ $isMuni ? 'true' : 'false' }}">m2</td> <td data-aykome-col="miktar" class="sync-dom-value sync-miktar-td text-center" data-id="{{ sv($sl, 'BETON')?->id }}" data-type="miktar" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'BETON') ? number_format(sv($sl, 'BETON')->quantity,2,',','.') : '0,00' }}</td> <td data-aykome-col="tutar" class="text-right" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'BETON') ? number_format(sv($sl, 'BETON')->amount,2,',','.') : '0,00' }}</td>
                <td style="border-left: 3px solid black;"><span contenteditable="{{ $isMuni ? 'true' : 'false' }}">ZTB TOPLAM</span></td>
                <td data-aykome-fee="ztb_total" class="text-right font-bold" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ number_format((float)($application->ztb_total ?? 0), 2, ',', '.') }} TL</td>
            </tr>
            <tr data-aykome-surface="STABİLİZE">
                <td data-aykome-col="ad" contenteditable="{{ $isMuni ? 'true' : 'false' }}">STABİLİZE</td> <td data-aykome-col="birim" class="text-center" contenteditable="{{ $isMuni ? 'true' : 'false' }}">m2</td> <td data-aykome-col="miktar" class="sync-dom-value sync-miktar-td text-center" data-id="{{ sv($sl, 'STABİLİZE')?->id }}" data-type="miktar" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'STABİLİZE') ? number_format(sv($sl, 'STABİLİZE')->quantity,2,',','.') : '0,00' }}</td> <td data-aykome-col="tutar" class="text-right" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'STABİLİZE') ? number_format(sv($sl, 'STABİLİZE')->amount,2,',','.') : '0,00' }}</td>
                <td style="border-left: 3px solid black;"><span contenteditable="{{ $isMuni ? 'true' : 'false' }}">TEMİNAT</span></td>
                <td data-aykome-fee="teminat" class="text-right font-bold" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ number_format((float)($application->teminat_amount ?? 0), 2, ',', '.') }} TL</td>
            </tr>
            <tr data-aykome-surface="TRETUAR (PARKE PRİZMA)">
                <td data-aykome-col="ad" contenteditable="{{ $isMuni ? 'true' : 'false' }}">TRETUAR (PARKE P)</td><td data-aykome-col="birim" class="text-center" contenteditable="{{ $isMuni ? 'true' : 'false' }}">m2</td> <td data-aykome-col="miktar" class="sync-dom-value sync-miktar-td text-center" data-id="{{ sv($sl, 'TRETUAR (PARKE PRİZMA)')?->id }}" data-type="miktar" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'TRETUAR (PARKE PRİZMA)') ? number_format(sv($sl, 'TRETUAR (PARKE PRİZMA)')->quantity,2,',','.') : '0,00' }}</td> <td data-aykome-col="tutar" class="text-right" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'TRETUAR (PARKE PRİZMA)') ? number_format(sv($sl, 'TRETUAR (PARKE PRİZMA)')->amount,2,',','.') : '0,00' }}</td>
                <td rowspan="3" class="text-center bg-grey" style="border-left: 3px solid black; font-size:14px;">GENEL TOPLAM</td>
                <td data-aykome-fee="general_total" rowspan="3" class="text-center bg-grey font-bold" style="font-size:14px;" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ number_format((float)($application->general_total ?? 0), 2, ',', '.') }} TL</td>
            </tr>
            <tr data-aykome-surface="TRETUAR (KARO)">
                <td data-aykome-col="ad" contenteditable="{{ $isMuni ? 'true' : 'false' }}">TRETUAR (KARO)</td> <td data-aykome-col="birim" class="text-center" contenteditable="{{ $isMuni ? 'true' : 'false' }}">m2</td> <td data-aykome-col="miktar" class="sync-dom-value sync-miktar-td text-center" data-id="{{ sv($sl, 'TRETUAR (KARO)')?->id }}" data-type="miktar" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'TRETUAR (KARO)') ? number_format(sv($sl, 'TRETUAR (KARO)')->quantity,2,',','.') : '0,00' }}</td> <td data-aykome-col="tutar" class="text-right" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'TRETUAR (KARO)') ? number_format(sv($sl, 'TRETUAR (KARO)')->amount,2,',','.') : '0,00' }}</td>
            </tr>
            <tr data-aykome-surface="TRETUAR (MERMER)">
                <td data-aykome-col="ad" contenteditable="{{ $isMuni ? 'true' : 'false' }}">TRETUAR (MERMER)</td> <td data-aykome-col="birim" class="text-center" contenteditable="{{ $isMuni ? 'true' : 'false' }}">m2</td> <td data-aykome-col="miktar" class="sync-dom-value sync-miktar-td text-center" data-id="{{ sv($sl, 'TRETUAR (MERMER)')?->id }}" data-type="miktar" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'TRETUAR (MERMER)') ? number_format(sv($sl, 'TRETUAR (MERMER)')->quantity,2,',','.') : '0,00' }}</td> <td data-aykome-col="tutar" class="text-right" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'TRETUAR (MERMER)') ? number_format(sv($sl, 'TRETUAR (MERMER)')->amount,2,',','.') : '0,00' }}</td>
            </tr>
            <tr data-aykome-surface="TRETUAR (BAZALT)">
                <td data-aykome-col="ad" contenteditable="{{ $isMuni ? 'true' : 'false' }}">TRETUAR (BAZALT)</td> <td data-aykome-col="birim" class="text-center" contenteditable="{{ $isMuni ? 'true' : 'false' }}">m2</td> <td data-aykome-col="miktar" class="sync-dom-value sync-miktar-td text-center" data-id="{{ sv($sl, 'TRETUAR (BAZALT)')?->id }}" data-type="miktar" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'TRETUAR (BAZALT)') ? number_format(sv($sl, 'TRETUAR (BAZALT)')->quantity,2,',','.') : '0,00' }}</td> <td data-aykome-col="tutar" class="text-right" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'TRETUAR (BAZALT)') ? number_format(sv($sl, 'TRETUAR (BAZALT)')->amount,2,',','.') : '0,00' }}</td>
                <td style="border-left: 3px solid black;" class="font-bold text-center">ZTB MAK & TRH</td> <td class="font-bold text-center" style="font-size:10px;" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $application->ztb_receipt_info ?? '' }}</td>
            </tr>
            <tr data-aykome-surface="BORDÜR (BETON)">
                <td data-aykome-col="ad" contenteditable="{{ $isMuni ? 'true' : 'false' }}">BORDÜR (BETON)</td> <td data-aykome-col="birim" class="text-center" contenteditable="{{ $isMuni ? 'true' : 'false' }}">m</td> <td data-aykome-col="miktar" class="sync-dom-value sync-miktar-td text-center" data-id="{{ sv($sl, 'BORDÜR (BETON)')?->id }}" data-type="miktar" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'BORDÜR (BETON)') ? number_format(sv($sl, 'BORDÜR (BETON)')->quantity,2,',','.') : '0,00' }}</td> <td data-aykome-col="tutar" class="text-right" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'BORDÜR (BETON)') ? number_format(sv($sl, 'BORDÜR (BETON)')->amount,2,',','.') : '0,00' }}</td>
                <td style="border-left: 3px solid black;" class="font-bold text-center">TEM MAK & TRH</td> <td class="font-bold text-center" style="font-size:10px;" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $application->deposit_receipt_info ?? '' }}</td>
            </tr>
            <tr data-aykome-surface="BORDÜR (BAZALT)">
                <td data-aykome-col="ad" contenteditable="{{ $isMuni ? 'true' : 'false' }}">BORDÜR (BAZALT)</td> <td data-aykome-col="birim" class="text-center" contenteditable="{{ $isMuni ? 'true' : 'false' }}">m</td> <td data-aykome-col="miktar" class="sync-dom-value sync-miktar-td text-center" data-id="{{ sv($sl, 'BORDÜR (BAZALT)')?->id }}" data-type="miktar" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'BORDÜR (BAZALT)') ? number_format(sv($sl, 'BORDÜR (BAZALT)')->quantity,2,',','.') : '0,00' }}</td> <td data-aykome-col="tutar" class="text-right" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'BORDÜR (BAZALT)') ? number_format(sv($sl, 'BORDÜR (BAZALT)')->amount,2,',','.') : '0,00' }}</td>
                <td colspan="2" rowspan="4" style="border-left: 3px solid black; background:#fff;"></td>
            </tr>
            <tr data-aykome-surface="ÇİM">
                <td data-aykome-col="ad" contenteditable="{{ $isMuni ? 'true' : 'false' }}">ÇİM</td> <td data-aykome-col="birim" class="text-center" contenteditable="{{ $isMuni ? 'true' : 'false' }}">m2</td> <td data-aykome-col="miktar" class="sync-dom-value sync-miktar-td text-center" data-id="{{ sv($sl, 'ÇİM')?->id }}" data-type="miktar" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'ÇİM') ? number_format(sv($sl, 'ÇİM')->quantity,2,',','.') : '0,00' }}</td> <td data-aykome-col="tutar" class="text-right" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'ÇİM') ? number_format(sv($sl, 'ÇİM')->amount,2,',','.') : '0,00' }}</td>
            </tr>
            <tr data-aykome-surface="TOPRAK">
                <td data-aykome-col="ad" contenteditable="{{ $isMuni ? 'true' : 'false' }}">TOPRAK</td> <td data-aykome-col="birim" class="text-center" contenteditable="{{ $isMuni ? 'true' : 'false' }}">m2</td> <td data-aykome-col="miktar" class="sync-dom-value sync-miktar-td text-center" data-id="{{ sv($sl, 'TOPRAK')?->id }}" data-type="miktar" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'TOPRAK') ? number_format(sv($sl, 'TOPRAK')->quantity,2,',','.') : '0,00' }}</td> <td data-aykome-col="tutar" class="text-right" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'TOPRAK') ? number_format(sv($sl, 'TOPRAK')->amount,2,',','.') : '0,00' }}</td>
            </tr>
            <tr data-aykome-surface="GÖRME ENGELLİ KARO">
                <td data-aykome-col="ad" contenteditable="{{ $isMuni ? 'true' : 'false' }}">GÖRM. ENG. KARO</td> <td data-aykome-col="birim" class="text-center" contenteditable="{{ $isMuni ? 'true' : 'false' }}">m</td> <td data-aykome-col="miktar" class="sync-dom-value sync-miktar-td text-center" data-id="{{ sv($sl, 'GÖRME ENGELLİ KARO')?->id }}" data-type="miktar" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'GÖRME ENGELLİ KARO') ? number_format(sv($sl, 'GÖRME ENGELLİ KARO')->quantity,2,',','.') : '0,00' }}</td> <td data-aykome-col="tutar" class="text-right" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ sv($sl, 'GÖRME ENGELLİ KARO') ? number_format(sv($sl, 'GÖRME ENGELLİ KARO')->amount,2,',','.') : '0,00' }}</td>
            </tr>
            <tr class="bg-grey" style="border-top:2px solid #000;">
                <td class="text-center font-bold" contenteditable="{{ $isMuni ? 'true' : 'false' }}">ZEMİN TAHRİP B. (ZTB)</td><td class="text-center" contenteditable="{{ $isMuni ? 'true' : 'false' }}">m2/m</td><td data-aykome-fee="toplam_miktar" class="text-center" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ number_format((float)($application->toplam_miktar ?? 0), 2, ',', '.') }}</td><td data-aykome-fee="ztb_amount" class="text-right font-bold" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ number_format((float)($application->ztb_amount ?? 0), 2, ',', '.') }} TL</td>
                <td colspan="2"></td>
            </tr>
        </table>

        <div style="font-weight:bold; font-size:12px; margin:2px 0;" contenteditable="true">ÖZEL ŞARTLAR</div>
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
        <!-- CELL-BASED AUTH: Belediye hücreleri (AYKOME/EYYÜBİYE) altkuruma kilitli; kurum hücreleri (FENNİ MESUL/FİRMA/SORUMLU) her iki tarafa açık. -->
        <table style="margin-top: 1px; table-layout: fixed; margin-bottom: 0px;">
            <tr class="bg-grey font-bold text-center">
                <td style="width:25%; padding: 2px;" colspan="2" contenteditable="true">YAPILACAK İŞİN FENNİ MESULÜ</td>
                <td style="width:37%; padding: 2px;" contenteditable="{{ $isMuni ? 'true' : 'false' }}">AYKOME BİRİMİ</td>
                <td style="width:38%; padding: 2px;" contenteditable="{{ $isMuni ? 'true' : 'false' }}">EYYÜBİYE BELEDİYESİ</td>
            </tr>
            <tr>
                <td class="font-bold" style="width: 7%; padding: 2px;">FİRMA</td>
                <td style="width: 21%; font-size: 11px; font-weight:bold; padding: 2px;" contenteditable="true" data-sign-editable="1">{{ mb_strtoupper($application->institution?->name ?? 'Şahsi Başvuru', 'UTF-8') }}</td>
                <td rowspan="4" class="text-center" style="vertical-align: middle; padding: 0;">
                    <div style="margin-top: 5px;" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $signatories['aykome_sorumlusu']['ad_soyad'] ?? 'Yetkili' }}</div>
                    <div style="font-weight:bold; margin-top: 2px; margin-bottom:5px;" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $signatories['aykome_sorumlusu']['unvan'] ?? 'AYKOME Birim Sorumlusu' }}</div>
                </td>
                <td rowspan="4" class="text-center" style="vertical-align: middle; padding: 0;">
                    <div style="margin-top: 5px;" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $signatories['fen_isleri_muduru']['ad_soyad'] ?? 'Yetkili' }}</div>
                    <div style="font-weight:bold; margin-top: 2px; margin-bottom:5px;" contenteditable="{{ $isMuni ? 'true' : 'false' }}">{{ $signatories['fen_isleri_muduru']['unvan'] ?? 'Fen İşleri Müdürü' }}</div>
                </td>
            </tr>
            <tr><td class="font-bold" style="padding: 2px;">SORUMLU</td> <td style="font-size:11px; padding: 2px;" contenteditable="true" data-sign-editable="1">{{ mb_strtoupper(trim($application->tesis_sorumlusu ?? $application->institution?->tesis_sorumlusu_adi ?? 'Yetkili Görevli'), 'UTF-8') }}</td></tr>
            <tr><td class="font-bold" style="padding: 2px;">TELEFON</td> <td style="font-size:11px; padding: 2px;" contenteditable="true" data-sign-editable="1">{{ $application->applicant_phone ?? '-' }}</td></tr>
            <tr><td class="font-bold" style="padding: 2px;">İMZA</td> <td style="height: 12px; padding: 0px;" contenteditable="true" data-sign-editable="1"></td></tr>

            <tr style="background:#e5e5e5; font-size: 11px;">
                <td colspan="4" class="text-center font-bold" style="padding: 1px;" contenteditable="{{ $isMuni ? 'true' : 'false' }}">ALTYAPI TESİSİ AÇIMI İLE İLGİLİ UYULMASI GEREKEN ŞARTLAR</td>
            </tr>
            <tr>
                <td colspan="4" style="text-align: left; font-size: 10px; padding: 2px 4px; line-height: 1.15;">
                    <span class="font-bold">1 - Altyapı:</span> Yol üst kaplaması altında kalan yol kısmı ile İçme suyu... tüm tesisleri kapsar.<br>
                    <span class="font-bold">2 - EYYÜBİYE BELEDİYESİ:</span> Altyapı tesisi ile ilgili tüm işlemler... ŞANLIURFA BYŞ BELEDİYESİ AYKOME biriminin verilecek izne göre yürütülür.
                </td>
            </tr>
        </table>

        {{-- FOOTER — TEK SATIR + DAR MARGIN: uzun açıklama silindi, A4 taşması engellenir --}}
        <div style="margin-top:10px; border-top: 1px dashed #cbd5e1; padding-top:3px; text-align: center; font-family: monospace; font-size: 9px; line-height: 1; color:#64748b;">
            BELGE DOĞRULAMA KODU: <b style="color:#d97706;">{{ $application->verification_code ?? 'GEÇERSİZ/TASLAK' }}</b> | KONTROL ADRESİ: <b>aykome.eyyubiye.bel.tr/dogrulama</b>
        </div>
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
