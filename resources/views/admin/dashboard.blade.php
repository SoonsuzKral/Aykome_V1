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
                <p class="metric-value text-indigo-700">{{ number_format($stats['paid_revenue_total'], 2, ',', '.') }} ₺</p>
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
                                            'draft' => ['label' => 'Taslak', 'class' => 'bg-slate-100 text-slate-700'],
                                            'submitted' => ['label' => 'Gönderildi', 'class' => 'bg-sky-100 text-sky-700'],
                                            'priced' => ['label' => 'Fiyatlandı', 'class' => 'bg-indigo-100 text-indigo-700'],
                                            'awaiting_payment' => ['label' => 'Ödeme bekliyor', 'class' => 'bg-amber-100 text-amber-700'],
                                            'receipt_pending' => ['label' => 'Makbuz bekliyor', 'class' => 'bg-orange-100 text-orange-700'],
                                            'licensed' => ['label' => 'Ruhsatlı', 'class' => 'bg-emerald-100 text-emerald-700'],
                                            'completed' => ['label' => 'Tamamlandı', 'class' => 'bg-teal-100 text-teal-700'],
                                            default => ['label' => str_replace('_', ' ', $statusValue), 'class' => 'bg-slate-100 text-slate-700'],
                                        };
                                    @endphp
                                    <tr class="transition hover:bg-cyan-50/50">
                                        <td class="px-3 py-2 font-medium text-slate-800">
                                            <a href="{{ route('admin.applications.show', $application) }}" class="hover:text-[#02AFC6]">{{ $application->application_no }}</a>
                                        </td>
                                        <td class="px-3 py-2 text-slate-600">{{ $application->institution?->name ?? '—' }}</td>
                                        <td class="px-3 py-2">
                                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-slate-500">{{ $application->created_at?->format('d.m.Y H:i') }}</td>
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
