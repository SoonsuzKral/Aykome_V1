@extends('layouts.admin')

@section('page-heading', $application->application_no)

@section('content')
    @php
        $st = $application->status instanceof \BackedEnum ? $application->status->value : $application->status;
        $isAltKurum = $application->institution && !str_contains(strtolower($application->institution->name ?? ''), 'merkez');

        $statusMeta = match($st) {
            'draft'                  => ['label' => 'Taslak',                 'class' => 'bg-slate-100 text-slate-700'],
            'submitted'              => ['label' => 'Ön Kazı Bekliyor',       'class' => 'bg-sky-100 text-sky-700'],
            'pre_excavation_approved'=> ['label' => 'Ön Kazı Onaylı',         'class' => 'bg-cyan-100 text-cyan-700'],
            'pre_approved'           => ['label' => 'Ön Kazı Onaylı',         'class' => 'bg-cyan-100 text-cyan-700'],
            'priced'                 => ['label' => 'Fiyatlandı',             'class' => 'bg-indigo-100 text-indigo-700'],
            'awaiting_payment'       => ['label' => 'Ödeme Bekliyor',         'class' => 'bg-amber-100 text-amber-700'],
            'receipt_pending'        => ['label' => 'Makbuz Bekliyor',        'class' => 'bg-orange-100 text-orange-700'],
            'approved'               => ['label' => 'Onaylandı',              'class' => 'bg-emerald-100 text-emerald-700'],
            'licensed'               => ['label' => 'Ruhsatlı',               'class' => 'bg-green-100 text-green-700'],
            'field_work'             => ['label' => 'Saha İşi',               'class' => 'bg-blue-100 text-blue-700'],
            'completed'              => ['label' => 'Tamamlandı',             'class' => 'bg-teal-100 text-teal-700'],
            'rejected'               => ['label' => 'Reddedildi',             'class' => 'bg-rose-100 text-rose-700'],
            default                  => ['label' => ucfirst(str_replace('_',' ',$st)), 'class' => 'bg-slate-100 text-slate-700'],
        };

        $surfaceLines = $application->surfaceLines;
        $isDicle = $application->institution?->tax_number === '2950368442';
        $isInstApp = $application->institution_id && !$application->institution?->is_municipality;

        $toplamMiktar = 0;
        $ztb = 0;
        foreach ($surfaceLines as $line) {
            $q = max((float)($line->quantity ?? 0), 0);
            $up = max((float)($line->surfaceType?->price_per_m2 ?? 0), 0);
            $toplamMiktar += $q;
            $ztb += $q * $up;
        }
        $kdv = $ztb * 0.20;
        $ruhsatHarci = $isDicle ? 0 : $toplamMiktar * 9;
        $kesifBedeli = 361 + ($ztb * 0.01);
        $ztbToplam = $ztb + $kdv + $ruhsatHarci + $kesifBedeli;
        $teminat = $isInstApp ? 0 : $ztb * 0.50;
        $genelToplam = $ztbToplam + $teminat;
    @endphp

    {{-- HEADER --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold text-slate-900">{{ $application->application_no }}</h1>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                <span class="rounded-full bg-cyan-50 px-2.5 py-1 text-[11px] font-bold text-cyan-700">{{ $currentStepLabel }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">{{ $application->institution?->name }} · {{ $application->creator?->name }}</p>
        </div>
        <a href="{{ route('admin.makam.index') }}"
           class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">← Makam Masası</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">

            {{-- BAŞVURU BİLGİLERİ --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-slate-800">Başvuru Bilgileri</h2>
                <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-slate-500">Başvuru Türü</dt>
                        <dd class="mt-0.5">
                            @if($application->application_type === 'ariza')
                                <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">
                                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                    Arıza (Acil Kazı)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-700">
                                    Normal Başvuru
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div><dt class="text-xs font-medium text-slate-500">Başvuran</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $application->applicant_first_name }} {{ $application->applicant_last_name }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Telefon</dt><dd class="mt-0.5">{{ $application->applicant_phone ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Kazı Sebebi</dt><dd class="mt-0.5">{{ $application->excavation_reason ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">İşin Adı (Cinsi)</dt><dd class="mt-0.5">{{ $application->work_type ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Başlangıç</dt><dd class="mt-0.5">{{ $application->start_date?->format('d.m.Y') ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Bitiş</dt><dd class="mt-0.5">{{ $application->end_date?->format('d.m.Y') ?? '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-medium text-slate-500">Adres</dt><dd class="mt-0.5 text-slate-700">{{ $application->address_text ?? '—' }}</dd></div>
                    @if($application->description)
                    <div class="sm:col-span-2"><dt class="text-xs font-medium text-slate-500">Açıklama</dt><dd class="mt-0.5 text-slate-700">{{ $application->description }}</dd></div>
                    @endif
                    @if($application->preExcavationApprover)
                    <div><dt class="text-xs font-medium text-slate-500">Ön Kazı Onaylayan</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $application->preExcavationApprover?->name }}</dd></div>
                    <div><dt class="text-xs font-medium text-slate-500">Ön Kazı Onay Tarihi</dt><dd class="mt-0.5">{{ $application->pre_excavation_approved_at?->format('d.m.Y H:i') }}</dd></div>
                    @endif
                </dl>
            </div>

            {{-- ZEMİN SATIRLARI & HESAPLAMALAR (zemin tahrip — aynı) --}}
            @if($surfaceLines->isNotEmpty())
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="mb-3 text-sm font-semibold text-slate-800">Zemin Satırları &amp; Hesaplamalar</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-slate-300 text-left text-slate-600">
                                <th class="py-2 pr-2 font-medium">#</th>
                                <th class="p-2 font-medium min-w-[180px]">Zemin Tipi</th>
                                <th class="p-2 font-medium min-w-[100px]">Genişlik (m)</th>
                                <th class="p-2 font-medium min-w-[100px]">Uzunluk (m)</th>
                                <th class="p-2 font-medium min-w-[120px]">Miktar (m²)</th>
                                <th class="p-2 font-medium min-w-[110px]">Birim Fiyat</th>
                                <th class="p-2 font-medium min-w-[140px]">Satır Tutarı (₺)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($surfaceLines as $idx => $line)
                            @php
                                $qty = max((float)($line->quantity ?? 0), 0);
                                $unitPrice = max((float)($line->surfaceType?->price_per_m2 ?? 0), 0);
                                $rowTotal = $qty * $unitPrice;
                            @endphp
                            <tr class="border-b border-slate-200 hover:bg-slate-100/50 transition">
                                <td class="py-2 pr-2 text-slate-400 font-mono text-[10px] align-top pt-3">{{ $idx + 1 }}</td>
                                <td class="p-2 align-top pt-2 font-medium text-slate-800">{{ $line->surfaceType?->name ?? '—' }}</td>
                                <td class="p-2 align-top pt-2 text-slate-700">{{ $line->width_m ? number_format((float)$line->width_m, 2, ',', '.') : '—' }}</td>
                                <td class="p-2 align-top pt-2 text-slate-700">{{ $line->length_m ? number_format((float)$line->length_m, 2, ',', '.') : '—' }}</td>
                                <td class="p-2 align-top pt-2 font-semibold text-slate-800">{{ number_format($qty, 2, ',', '.') }}</td>
                                <td class="p-2 align-top pt-2 text-slate-600 font-mono">{{ number_format($unitPrice, 2, ',', '.') }} ₺/m²</td>
                                <td class="p-2 align-top pt-2 text-right font-mono text-xs font-semibold text-slate-800">{{ number_format($rowTotal, 2, ',', '.') }} ₺</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Toplam Miktar</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($toplamMiktar, 2, ',', '.') }} m²</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Zemin Tahrip Bedeli</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($ztb, 2, ',', '.') }} ₺</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">KDV (%20)</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($kdv, 2, ',', '.') }} ₺</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Ruhsat Harcı</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($ruhsatHarci, 2, ',', '.') }} ₺</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Keşif Bedeli</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($kesifBedeli, 2, ',', '.') }} ₺</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">ZTB Toplam</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($ztbToplam, 2, ',', '.') }} ₺</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Teminat</p>
                        <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($teminat, 2, ',', '.') }} ₺</p>
                    </div>
                    <div class="rounded-lg border border-emerald-300 bg-emerald-50 p-3 shadow-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600">Genel Toplam</p>
                        <p class="mt-1 text-xl font-bold text-emerald-700">{{ number_format($genelToplam, 2, ',', '.') }} ₺</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- KARAR EVRAKI — SADECE: Üst Yazı · Ön Kazı İzni · Ruhsat --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-1 text-sm font-semibold text-slate-800">📜 Karar Evrakı</h2>
                <p class="mb-4 text-xs text-slate-500">Başkanlık değerlendirmesi için yalnızca bu üç belge gösterilir.</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    {{-- Üst Yazı --}}
                    @if($isAltKurum)
                    <a href="{{ route('admin.applications.pdf.cover-letter', $application) }}" target="_blank"
                       class="group flex flex-col items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/70 p-5 text-center transition hover:border-cyan-300 hover:bg-cyan-50/70 hover:shadow-sm">
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700 group-hover:bg-cyan-200 transition">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4a1 1 0 001 1h4"/></svg>
                        </span>
                        <span class="text-sm font-semibold text-slate-700 group-hover:text-cyan-800">Üst Yazı</span>
                        <span class="text-[11px] text-slate-400">Alt Kurum Dilekçesi</span>
                    </a>
                    @endif

                    {{-- Ön Kazı İzin Belgesi --}}
                    <a href="{{ route('admin.applications.pdf.pre-permit', $application) }}" target="_blank"
                       class="group flex flex-col items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/70 p-5 text-center transition hover:border-cyan-300 hover:bg-cyan-50/70 hover:shadow-sm">
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700 group-hover:bg-cyan-200 transition">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </span>
                        <span class="text-sm font-semibold text-slate-700 group-hover:text-cyan-800">Ön Kazı İzni</span>
                        <span class="text-[11px] text-slate-400">İzin Belgesi</span>
                    </a>

                    {{-- Ruhsat --}}
                    <a href="{{ route('admin.applications.pdf.ruhsat', $application) }}" target="_blank"
                       class="group flex flex-col items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/70 p-5 text-center transition hover:border-emerald-300 hover:bg-emerald-50/70 hover:shadow-sm">
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 group-hover:bg-emerald-200 transition">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <span class="text-sm font-semibold text-slate-700 group-hover:text-emerald-800">Ruhsat</span>
                        <span class="text-[11px] text-slate-400">Kazı Ruhsatı Belgesi</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- RIGHT SIDEBAR --}}
        <div class="space-y-4">
            {{-- ONAY / KARAR KARTI --}}
            <div class="rounded-2xl border border-amber-200 bg-gradient-to-b from-amber-50 to-white p-5 shadow-sm">
                <h2 class="text-sm font-bold text-slate-800">✍️ Kararınız</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Şu anki adım: <b class="text-amber-700">{{ $currentStepLabel }}</b>
                </p>

                @if($canApprove)
                    <form method="POST" action="{{ route('admin.makam.onayla', $application) }}" class="mt-4"
                          onsubmit="return confirm('#{{ $application->application_no }} başvurusunu ONAYLIYOR, e-imzalayıp gönderiyorsunuz. Emin misiniz?')">
                        @csrf
                        <button type="submit"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-3 text-sm font-bold text-white shadow-md shadow-amber-900/20 transition hover:bg-amber-700 active:scale-95">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ONAYLIYORUM E-İMZAYLA &amp; GÖNDER
                        </button>
                    </form>
                    <p class="mt-2 text-[11px] text-slate-400">Tek tıkla onaylar, son adımda Ön Kazı İzni verir ve e-imza sürecini başlatır.</p>
                @else
                    <div class="mt-4 rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-600">
                        {{ $processCurrentStepIsFinal && $st === 'pre_approved' ? 'Bu başvuru onaylandı.' : 'Bu başvuru şu an onayınıza açık değil.' }}
                    </div>
                @endif
            </div>

            {{-- ONAY AKIŞI --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold text-slate-800">Onay Akışı</h2>
                <ol class="space-y-3">
                    @forelse($approvalLog as $entry)
                        <li class="flex items-start gap-2.5">
                            <span class="mt-1 h-2.5 w-2.5 flex-shrink-0 rounded-full bg-emerald-500"></span>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-800">{{ $entry['step_name'] ?? $entry['role_key'] ?? 'Adım' }}</p>
                                <p class="text-[11px] text-slate-500">{{ $entry['approved_by_name'] ?? '—' }} · {{ isset($entry['approved_at']) ? \Illuminate\Support\Carbon::parse($entry['approved_at'])->format('d.m.Y H:i') : '—' }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="text-xs text-slate-500">Henüz onay verilmedi.</li>
                    @endforelse
                </ol>
            </div>

            {{-- SÜREÇ ZAMAN ÇİZELGESİ --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold text-slate-800">Süreç Takip</h2>
                <ol class="relative border-s-2 border-slate-200 ps-4">
                    @php
                        $audits = collect($application->history->map(fn ($a) => (object)[
                            'action' => $a->action, 'user' => $a->user?->name ?? 'Sistem', 'date' => $a->created_at,
                        ]))->merge($application->timelineLogs->map(fn ($l) => (object)[
                            'action' => $l->action, 'user' => $l->user?->name ?? 'Sistem', 'date' => $l->created_at,
                        ]))->sortByDesc('date');
                    @endphp
                    @forelse($audits as $entry)
                        <li class="relative mb-3 last:mb-0">
                            <span class="absolute -start-[1.1rem] mt-1.5 h-3 w-3 rounded-full border-2 border-white bg-cyan-400"></span>
                            <p class="text-xs font-medium text-slate-800">{{ $entry->action }}</p>
                            <p class="text-[11px] text-slate-400">{{ $entry->user }} · {{ $entry->date?->format('d.m.Y H:i') }}</p>
                        </li>
                    @empty
                        <li class="text-xs text-slate-500">Henüz işlem kaydı bulunmuyor.</li>
                    @endforelse
                </ol>
            </div>
        </div>
    </div>
@endsection
