@extends('layouts.app')

@section('title', "Ek Ruhsat #{$extraPermit->id} - {$application->application_no}")

@section('content')
<div class="py-6">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('admin.applications.show', $application) }}" class="mb-2 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Başvuruya Dön
            </a>
            <h1 class="text-2xl font-bold text-slate-900">Ek Ruhsat #{{ $extraPermit->id }}</h1>
            <p class="mt-1 text-sm text-slate-500">Başvuru: {{ $application->application_no }}</p>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Ek Metraj</span>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($extraPermit->ek_metraj_m, 2) }} m</p>
                </div>
                <div>
                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Toplam Tutar</span>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($extraPermit->total_price, 2) }} ₺</p>
                </div>
                <div>
                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Ruhsat No</span>
                    <p class="mt-1 text-sm text-slate-700">{{ $extraPermit->ruhsat_no ?? '—' }}</p>
                </div>
                <div>
                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Durum</span>
                    <p class="mt-1">
                        @if($extraPermit->status === 'completed')
                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Tamamlandı</span>
                        @else
                            <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">Beklemede</span>
                        @endif
                    </p>
                </div>
            </div>

            @if(!empty($extraPermit->surface_lines))
            <div class="border-t border-slate-200 pt-6">
                <h2 class="mb-4 text-sm font-semibold text-slate-900">Zemin Satırları</h2>
                <div class="overflow-hidden rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Zemin Tipi</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Genişlik</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Uzunluk</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Miktar</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-slate-500">Tutar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($extraPermit->surface_lines as $line)
                            <tr>
                                <td class="px-4 py-2 text-sm text-slate-700">{{ $line['surface_type_name'] ?? '—' }}</td>
                                <td class="px-4 py-2 text-sm text-slate-700">{{ number_format($line['width_m'] ?? 0, 2) }} m</td>
                                <td class="px-4 py-2 text-sm text-slate-700">{{ number_format($line['length_m'] ?? 0, 2) }} m</td>
                                <td class="px-4 py-2 text-sm text-slate-700">{{ number_format($line['quantity'] ?? 0, 2) }} m²</td>
                                <td class="px-4 py-2 text-right text-sm text-slate-700">{{ number_format($line['amount'] ?? 0, 2) }} ₺</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
