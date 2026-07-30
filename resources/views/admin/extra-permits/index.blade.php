@extends('layouts.app')

@section('title', 'Ek Ruhsatlar - ' . $application->application_no)

@section('content')
<div class="py-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <a href="{{ route('admin.applications.show', $application) }}" class="mb-2 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Başvuruya Dön
                </a>
                <h1 class="text-2xl font-bold text-slate-900">Ek Ruhsatlar</h1>
                <p class="mt-1 text-sm text-slate-500">Başvuru: {{ $application->application_no }}</p>
            </div>
            <a href="{{ route('admin.extra-permits.create', $application) }}"
               class="rounded-lg bg-cyan-700 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-800">
                + Yeni Ek Ruhsat
            </a>
        </div>

        @if($extraPermits->isEmpty())
            <div class="rounded-lg border-2 border-dashed border-slate-200 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-slate-700">Henüz ek ruhsat bulunmuyor</h3>
                <p class="mt-2 text-sm text-slate-500">Bu başvuru için ek kazı ruhsatı oluşturabilirsiniz.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-slate-500">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-slate-500">Ek Metraj (m)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-slate-500">Toplam Tutar</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-slate-500">Ruhsat No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-slate-500">Durum</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase text-slate-500">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($extraPermits as $ep)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">{{ $ep->id }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">{{ number_format($ep->ek_metraj_m, 2) }} m</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">{{ number_format($ep->total_price, 2) }} ₺</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">{{ $ep->ruhsat_no ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($ep->status === 'completed')
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Tamamlandı</span>
                                @else
                                    <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">Beklemede</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <a href="{{ route('admin.extra-permits.show', [$application, $ep]) }}" class="text-cyan-700 hover:text-cyan-900">Detay</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
