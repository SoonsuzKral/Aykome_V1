@extends('layouts.admin')

@section('page-heading', 'Raporlar')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Özet raporlar</h1>
        <p class="text-sm text-slate-600">Başvuru dağılımları (kurum ve durum).</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800">Kuruma göre başvuru</h2>
            <table class="mt-4 w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-slate-500">
                        <th class="pb-2 font-medium">Kurum</th>
                        <th class="pb-2 font-medium text-right">Adet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($byInstitution as $inst)
                        <tr>
                            <td class="py-2">{{ $inst->name }}</td>
                            <td class="py-2 text-right tabular-nums">{{ $inst->applications_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="py-4 text-slate-500">Veri yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800">Duruma göre</h2>
            <table class="mt-4 w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-slate-500">
                        <th class="pb-2 font-medium">Durum</th>
                        <th class="pb-2 font-medium text-right">Adet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($byStatus as $status => $count)
                        <tr>
                            <td class="py-2 capitalize">{{ $status }}</td>
                            <td class="py-2 text-right tabular-nums">{{ $count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="py-4 text-slate-500">Veri yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
