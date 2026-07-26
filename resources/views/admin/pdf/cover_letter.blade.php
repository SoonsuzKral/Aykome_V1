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

        .print-bar { position: fixed; top: 0; right:0; background:#1f2937; padding:15px; width:100%; text-align:right;}
        .print-btn { background:#3b82f6; color:#fff; border:none; padding:10px 25px; border-radius:5px; font-weight:bold; cursor:pointer;}

        @page { size: A4 portrait; margin: 0 !important; }
        @media print {
            body { background:#fff; margin:0 !important; font-size: 14.5px !important; display: block;}
            .print-bar { display:none !important; }
            .a4-container { width: 100% !important; padding: 12mm 20mm 0 20mm !important; height:100% !important; box-shadow:none; margin:0; page-break-after: avoid; }
        }
    </style>
</head>
<body>

    <div class="print-bar no-print"><button onclick="window.print()" class="print-btn">🖨️ YAZDIR / KAYDET</button></div>
    <div class="a4-container">

        <table>
            <tr>
                <td style="width:25%;">
                    @if($application->institution)
                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSY1aGeBk3bGnpaUikWjQ-JRMM9kPhZbX8KevL3u5IahtS6t3Zdd-IS4Ic&s=10" alt="Logo" style="max-height:180px;">
                    @else
                        <div>BAŞVURU BELGESİ</div>
                    @endif
                </td>
                <td style="width:50%; text-align:center; padding-top:10px;">
                    <span class="font-bold" style="font-size: 16px;">{{ mb_strtoupper($application->institution?->name ?? 'DİCLE ELEKTRİK DAĞITIM A.Ş.', 'UTF-8') }}</span><br>
                    <span style="font-size: 15px;">Şanlıurfa Tesis Yöneticiliği</span>
                </td>
                <td style="width:25%;"></td>
            </tr>
        </table>

        <table style="margin-top: 50px; margin-bottom: 50px;">
            <tr>
                <td style="width:80%; padding:0; line-height: 1.5;">
                    Sayı &nbsp;&nbsp;&nbsp;: E-50005665001100-100-{{ str_pad($application->id, 6, '0', STR_PAD_LEFT) }}<br>
                    Konu : {{ mb_strtoupper($application->excavation_reason ?? 'ALTYAPI TESİS', 'UTF-8') }} PROJESİ KAZI ÖN <br> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;İZNİ TALEP REVİZE
                </td>
                <td style="width:20%; text-align:right; padding:0; padding-top: 5px;">
                    {{ $application->created_at ? $application->created_at->format('d.m.Y') : date('d.m.Y') }}
                </td>
            </tr>
        </table>

        <div class="text-center font-bold" style="font-size: 15px; margin-bottom: 40px; letter-spacing: 0.5px;">
            EYYÜBİYE BELEDİYE BAŞKANLIĞI<br>AYKOME ŞUBE MÜDÜRLÜĞÜ
        </div>

        <div>
            <b>İlgi &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</b> Belediyeniz yatırım programlarına istinaden iletilen {{ $application->updated_at ? $application->updated_at->format('d.m.Y') : date('d.m.Y') }} tarihli talep ve onaylı formunuz.<br><br>
            <p style="text-indent: 40px; margin-bottom: 10px;">İlgi sayılı form / talebiniz üzerine; kurumumuza bildirdiğiniz lokasyon adreslerinin sokak ve numarataj ilişkileri planımıza yansıtılmıştır.</p>

            <p style="text-indent: 40px; text-align: justify; line-height:1.5;">
                Şirketimiz {{ date('Y') }} yılı altyapı-üstyapı yatırım ve kurulum bakım-onarım çalışma programında <b>{{ $application->project_code ?? str_pad($application->id, 5, '0', STR_PAD_LEFT) }}</b> Pyp referans numarası ile işlem gören; ŞANLIURFA İLİ EYYÜBİYE İLÇESİ adreslerindeki faaliyet alanımız ve projeye dahil tesis çalışmalarımız kapsamında planlamalar değerlendirilmiş olup; yüklenicimizde ({{ $application->institution?->name ?? 'Bağlı kurumumuz' }}) kalan faaliyetlerin ve ihale sürçlerinin idamesi adına Şanlıurfa EYYÜBİYE Belediyesi'nin sınırlarında (sorumluluğunda) bulunan kazı işlem ruhsat ve izinlerinin tarafımıza tahsis edilmesi kurumumuzca / ilgilimizce talep edilmektedir.
            </p>
            <p style="text-indent: 40px; margin-bottom:10px;">Belirtilen tesislerin yapım süreciyle alakalı ilgili cadde ve sokaklar alttaki tabloda gösterilmektedir. Gerekli ön ruhsat onayları hakkında,<br>Gereğini arz ederim.</p>
        </div>

        <div style="margin-top:25px;">
            @if(is_array($application->address_components) && count($application->address_components) > 0)
                @foreach($application->address_components as $adres)
                    <div class="mahalle-title">{{ mb_strtoupper($adres['mahalle'] ?? 'Belirsiz Mahalle', 'UTF-8') }}</div>
                    <table class="list-table">
                        @php
                            $sokaklar = isset($adres['streets']) && is_array($adres['streets']) ? $adres['streets'] : [];
                            $satirlar = array_chunk($sokaklar, 2);
                        @endphp
                        @forelse($satirlar as $satir)
                            <tr>
                                <td>{{ mb_strtoupper($satir[0] ?? '', 'UTF-8') }}</td>
                                <td>{{ mb_strtoupper($satir[1] ?? '', 'UTF-8') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" style="font-weight:normal;">Seçili adres ve parsel bölge alanları</td></tr>
                        @endforelse
                    </table>
                @endforeach
            @else
                <div class="mahalle-title">KAYITLI ADRES BLOĞU</div>
                <table class="list-table">
                    <tr><td colspan="2" style="font-weight:normal; font-size:12.5px;">{{ $application->address_text ?? 'Adres verisi boş.' }}</td></tr>
                </table>
            @endif
        </div>

        <table style="width:100%; margin-top: 50px;">
            <tr>
                <td style="width:60%; line-height:1.4;">
                    @php $appName = trim(($application->applicant_first_name ?? '') . ' ' . ($application->applicant_last_name ?? '')); @endphp
                    Tesis Kontrol: <b>{{ mb_strtoupper($appName ?: '', 'UTF-8') }}</b><br>
                    Tel / GSM : <b>{{ $application->applicant_phone ?? '' }}</b><br>
                    Toplam Kazı : <b>{{ number_format((float)($application->total_area_m2 ?? 0), 2, ',', '.') }} m² / m. </b>
                </td>
                <td style="width:40%; text-align:center;">
                    <b>FUAT DEĞER</b><br>
                    <span style="font-size:14.5px;">Şanlıurfa İl Müdür Yardımcısı</span>
                </td>
            </tr>
        </table>

    </div>
</body>
</html>
