@extends('layouts.admin')

@section('page-heading', 'Dashboard')

@section('content')
    <div class="relative space-y-6">
        <div class="pointer-events-none absolute -top-24 right-0 h-56 w-56 rounded-full bg-[#02E0FB]/25 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-16 top-40 h-56 w-56 rounded-full bg-[#FA6001]/20 blur-3xl"></div>

        <section class="relative overflow-hidden rounded-3xl border border-cyan-300/30 bg-slate-900/90 px-6 py-6 shadow-[0_28px_60px_-28px_rgba(2,224,251,0.9)] backdrop-blur-xl">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(2,224,251,0.18),transparent_52%),radial-gradient(circle_at_bottom_left,rgba(250,96,1,0.18),transparent_48%)]"></div>
            <div class="relative flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-100/80">HGB Bilişim AYKOME</p>
                    <h1 class="mt-2 text-3xl font-black text-white">Premium Operasyon Dashboard</h1>
                    <p class="mt-2 text-sm text-slate-200">Başvuru, gelir ve saha operasyonlarını tek panelde canlı izleyin.</p>
                </div>
                <a href="{{ route('admin.applications.create') }}" class="inline-flex items-center rounded-xl bg-[#FA6001] px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-orange-900/30 transition hover:-translate-y-0.5 hover:bg-[#e75700]">Yeni başvuru</a>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article class="glass-card group border-cyan-300/35">
                <p class="metric-label text-cyan-700">Toplam başvuru</p>
                <p class="metric-value text-slate-900">{{ number_format($stats['applications_total']) }}</p>
                <p class="metric-sub">Genel sistem havuzu</p>
            </article>
            <article class="glass-card group border-orange-300/35">
                <p class="metric-label text-[#FA6001]">Onay bekleyen</p>
                <p class="metric-value text-[#FA6001]">{{ number_format($stats['applications_pending']) }}</p>
                <p class="metric-sub">Hızlı aksiyon gerektiren</p>
            </article>
            <article class="glass-card group border-cyan-300/35">
                <p class="metric-label text-cyan-700">Bu ay başvuru</p>
                <p class="metric-value text-cyan-700">{{ number_format($stats['applications_this_month']) }}</p>
                <p class="metric-sub">Aylık büyüme ritmi</p>
            </article>
            <article class="glass-card group border-indigo-300/35">
                <p class="metric-label text-indigo-700">Kazanılan gelir</p>
                <p class="metric-value text-indigo-700" style="font-size:1.35rem">{{ number_format($stats['paid_revenue_total'], 2, ',', '.') }} ₺</p>
                <p class="metric-sub">Tahsil edilen toplam</p>
            </article>
            <article class="glass-card group border-rose-300/35">
                <p class="metric-label text-rose-700">Ödeme bekleyen</p>
                <p class="metric-value text-rose-700">{{ number_format($stats['awaiting_payment_total']) }}</p>
                <p class="metric-sub">Makbuz süreci açık kayıt</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,1fr)]">
            <div class="space-y-6">
                <article class="relative overflow-hidden rounded-3xl border border-cyan-300/30 bg-slate-900/90 p-5 shadow-[0_25px_55px_-30px_rgba(2,224,251,0.85)] backdrop-blur-xl">
                    <div class="pointer-events-none absolute -top-14 right-6 h-36 w-36 rounded-full bg-[#02E0FB]/20 blur-3xl"></div>
                    <div class="pointer-events-none absolute -bottom-16 left-6 h-36 w-36 rounded-full bg-[#FA6001]/20 blur-3xl"></div>
                    <div class="relative mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold text-cyan-100">Son 6 ay başvuru &amp; gelir</h2>
                        <span class="rounded-full border border-cyan-300/30 bg-cyan-400/10 px-2 py-0.5 text-xs text-cyan-100">Aylık trend</span>
                    </div>
                    <div class="relative h-[320px]">
                        <canvas id="dashboard-chart"></canvas>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200/80 bg-white/80 p-5 shadow-[0_18px_42px_-24px_rgba(15,23,42,0.45)] backdrop-blur-xl">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold text-slate-800">Son başvurular</h2>
                        <a href="{{ route('admin.applications.index') }}" class="text-xs font-medium text-cyan-700 hover:underline">Tümünü gör</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50/90">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-slate-600">Başvuru</th>
                                    <th class="px-3 py-2 text-left font-medium text-slate-600">Kurum</th>
                                    <th class="px-3 py-2 text-left font-medium text-slate-600">Durum</th>
                                    <th class="px-3 py-2 text-left font-medium text-slate-600">Tarih</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($recentApplications as $application)
                                    @php
                                        $statusValue = $application->status instanceof \BackedEnum ? $application->status->value : (string) $application->status;
                                        $statusMeta = match ($statusValue) {
                                            'draft' => ['label' => 'Taslak', 'class' => 'bg-slate-100 text-slate-600 ring-slate-300', 'icon' => '<path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />'],
                                            'submitted' => ['label' => 'Gönderildi', 'class' => 'bg-sky-100 text-sky-700 ring-sky-300', 'icon' => '<path d="M6 12L3.269 3.125A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.875L5.999 12zm0 0h7.5" />'],
                                            'pre_excavation_approved' => ['label' => 'Ön Kazı Onaylı', 'class' => 'bg-cyan-100 text-cyan-700 ring-cyan-300', 'icon' => '<path d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />'],
                                            'priced' => ['label' => 'Fiyatlandı', 'class' => 'bg-indigo-100 text-indigo-700 ring-indigo-300', 'icon' => '<path d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />'],
                                            'awaiting_payment' => ['label' => 'Ödeme Bekliyor', 'class' => 'bg-amber-100 text-amber-700 ring-amber-300', 'icon' => '<path d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />'],
                                            'receipt_pending' => ['label' => 'Makbuz Bekliyor', 'class' => 'bg-orange-100 text-orange-700 ring-orange-300', 'icon' => '<path d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />'],
                                            'approved' => ['label' => 'Onaylandı', 'class' => 'bg-emerald-100 text-emerald-700 ring-emerald-300', 'icon' => '<path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'],
                                            'rejected' => ['label' => 'Reddedildi', 'class' => 'bg-red-100 text-red-700 ring-red-300', 'icon' => '<path d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'],
                                            'licensed' => ['label' => 'Ruhsatlı', 'class' => 'bg-emerald-100 text-emerald-700 ring-emerald-300', 'icon' => '<path d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />'],
                                            'field_work' => ['label' => 'Saha İşi', 'class' => 'bg-purple-100 text-purple-700 ring-purple-300', 'icon' => '<path d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />'],
                                            'completed' => ['label' => 'Tamamlandı', 'class' => 'bg-teal-100 text-teal-700 ring-teal-300', 'icon' => '<path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'],
                                            'archived' => ['label' => 'Arşiv', 'class' => 'bg-slate-100 text-slate-600 ring-slate-300', 'icon' => '<path d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 015.25 3.75h9.75A2.25 2.25 0 0117.25 6v3.776" />'],
                                            default => ['label' => str_replace('_', ' ', $statusValue), 'class' => 'bg-slate-100 text-slate-600 ring-slate-300', 'icon' => '<path d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'],
                                        };
                                    @endphp
                                    <tr class="relative transition hover:bg-gradient-to-r hover:from-cyan-50/70 hover:to-transparent">
                                        <td class="px-3 py-2.5 font-medium text-slate-800">
                                            <a href="{{ route('admin.applications.show', $application) }}" class="hover:text-[#02AFC6] transition-colors">{{ $application->application_no }}</a>
                                        </td>
                                        <td class="px-3 py-2.5 text-slate-600">{{ $application->institution?->name ?? '—' }}</td>
                                        <td class="px-3 py-2.5">
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $statusMeta['class'] }}">
                                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    {!! $statusMeta['icon'] !!}
                                                </svg>
                                                {{ $statusMeta['label'] }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2.5 text-slate-500 whitespace-nowrap">{{ $application->created_at?->format('d.m.Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-5 text-center text-slate-500">Henüz başvuru kaydı yok.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>

            <article class="rounded-3xl border border-slate-200/80 bg-white/80 p-5 shadow-[0_18px_42px_-24px_rgba(15,23,42,0.45)] backdrop-blur-xl">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-slate-800">Canlı Aktiviteler</h2>
                    <span class="rounded-full border border-cyan-200 bg-cyan-100/70 px-2 py-0.5 text-xs font-medium text-cyan-700">Timeline</span>
                </div>

                <ol class="relative border-s-2 border-cyan-200/80 ps-1">
                    @forelse($recentActivities as $activity)
                        <li class="ms-6 pb-6 last:pb-0">
                            <span class="absolute -start-[0.52rem] mt-1.5 inline-flex h-3.5 w-3.5 rounded-full border-2 border-white bg-[#FA6001] shadow-[0_0_0_6px_rgba(250,96,1,0.12)]"></span>
                            <div class="rounded-2xl border border-slate-100/90 bg-gradient-to-r from-white via-slate-50 to-cyan-50/60 px-3 py-2 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                <p class="text-sm font-semibold text-slate-800">{{ $activity->user?->name ?? 'Sistem' }}</p>
                                <p class="mt-0.5 text-sm text-slate-600">{{ $activity->message ?: str_replace('.', ' ', $activity->action) }}</p>
                                <div class="mt-2 flex items-center justify-between gap-2 text-xs text-slate-500">
                                    <span class="font-medium">{{ $activity->application?->application_no ?? 'Başvuru yok' }}</span>
                                    <span>{{ $activity->created_at?->diffForHumans() }}</span>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="ms-6">
                            <span class="absolute -start-[0.52rem] mt-1.5 h-3.5 w-3.5 rounded-full border-2 border-white bg-slate-400"></span>
                            <p class="text-sm text-slate-500">Henüz aktivite kaydı oluşmadı.</p>
                        </li>
                    @endforelse
                </ol>
            </article>
        </section>
    </div>
@endsection

@push('scripts')
    <style>
        .glass-card {
            border-width: 1px;
            border-radius: 1rem;
            background: linear-gradient(135deg, rgba(255,255,255,0.82), rgba(248,250,252,0.72));
            backdrop-filter: blur(14px);
            padding: 1.25rem;
            box-shadow: 0 18px 36px -24px rgba(15, 23, 42, 0.45);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .glass-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 24px 42px -20px rgba(2, 224, 251, 0.35);
        }

        .metric-label {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .18em;
        }

        .metric-value {
            margin-top: .75rem;
            font-size: 2rem;
            font-weight: 900;
            line-height: 1;
        }

        .metric-sub {
            margin-top: .5rem;
            font-size: .75rem;
            color: #475569;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (() => {
            const canvas = document.getElementById('dashboard-chart');
            if (!canvas || typeof Chart === 'undefined') {
                return;
            }

            const labels = @json($chart['labels']);
            const applications = @json($chart['applications']);
            const revenues = @json($chart['revenues']);

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Başvuru adedi',
                            data: applications,
                            backgroundColor: 'rgba(2, 224, 251, 0.30)',
                            borderColor: 'rgb(2, 224, 251)',
                            borderWidth: 1.5,
                            yAxisID: 'yApplications',
                            borderRadius: 10,
                            maxBarThickness: 40,
                        },
                        {
                            type: 'line',
                            label: 'Gelir (₺)',
                            data: revenues,
                            borderColor: 'rgb(250, 96, 1)',
                            backgroundColor: 'rgba(250, 96, 1, 0.22)',
                            yAxisID: 'yRevenue',
                            tension: 0.32,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 5,
                            pointBackgroundColor: 'rgba(250, 96, 1, 1)',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                boxHeight: 12,
                                usePointStyle: true,
                                color: '#CFFAFE',
                            },
                        },
                    },
                    scales: {
                        yApplications: {
                            type: 'linear',
                            beginAtZero: true,
                            position: 'left',
                            ticks: {
                                color: '#BAE6FD',
                                precision: 0,
                            },
                            grid: {
                                color: 'rgba(186, 230, 253, 0.2)',
                            },
                        },
                        yRevenue: {
                            type: 'linear',
                            beginAtZero: true,
                            position: 'right',
                            ticks: {
                                color: '#FDBA74',
                                callback: (value) => `${Number(value).toLocaleString('tr-TR')} ₺`,
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        },
                        x: {
                            ticks: {
                                color: '#E2E8F0',
                            },
                            grid: {
                                display: false,
                            },
                        },
                    },
                },
            });
        })();
    </script>
@endpush
