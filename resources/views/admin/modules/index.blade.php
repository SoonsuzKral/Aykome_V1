@extends('layouts.admin')

@section('title', 'Modül Yönetimi')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Modül Yönetimi</h1>
            <p class="mt-1 text-sm text-slate-500">Başvuru aşamalarını ve modüllerini yönetin</p>
        </div>
        <a href="{{ route('admin.modules.create') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-[#02E0FB] to-[#02A5C6] px-4 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:shadow-xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
            Yeni Modül
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-700 border border-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-slate-700">Modüller</h2>
        </div>

        @if($modules->isEmpty())
            <div class="p-12 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                </div>
                <p class="text-slate-500">Henüz modül oluşturulmadı.</p>
                <a href="{{ route('admin.modules.create') }}" class="mt-3 inline-block text-sm text-[#02E0FB] hover:underline">İlk modülü oluştur</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="modules-table">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="w-10 px-4 py-3"></th>
                            <th class="px-4 py-3">Modül</th>
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3 text-center">Alanlar</th>
                            <th class="px-4 py-3 text-center">Şablonlar</th>
                            <th class="px-4 py-3 text-center">Durum</th>
                            <th class="px-4 py-3 text-right">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="sortable-body">
                        @foreach($modules as $module)
                            <tr class="hover:bg-slate-50 transition" data-id="{{ $module->id }}">
                                <td class="px-4 py-3">
                                    <div class="cursor-grab text-slate-400 hover:text-slate-600" title="Sürükleerek sıralayın">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h3a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if($module->icon)
                                            <span class="text-xl">{{ $module->icon }}</span>
                                        @endif
                                        <div>
                                            <div class="font-medium text-slate-900">{{ $module->name }}</div>
                                            @if($module->description)
                                                <div class="text-xs text-slate-500 truncate max-w-xs">{{ $module->description }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600">{{ $module->slug }}</code>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $module->fields_count }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $module->templates_count }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($module->is_active)
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">Pasif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.modules.edit', $module->id) }}"
                                           class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition"
                                           title="Düzenle">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                                        </a>
                                        <form action="{{ route('admin.modules.destroy', $module->id) }}" method="POST" class="inline" onsubmit="return confirm('Bu modülü silmek istediğinize emin misiniz?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-500 transition"
                                                    title="Sil">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            </button>
                                        </form>
                                    </div>
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

@push('styles')
<style>
    #sortable-body tr { transition: background-color 0.2s; }
    #sortable-body tr.sortable-ghost { opacity: 0.4; background-color: #f0f9ff; }
    #sortable-body tr.sortable-chosen { background-color: #e0f2fe; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('sortable-body');
    if (!el) return;

    new Sortable(el, {
        animation: 150,
        handle: '[data-id]',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function () {
            const ids = [...el.querySelectorAll('tr')].map(row => row.dataset.id);
            fetch('{{ route('admin.modules.reorder') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ order: ids })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    // Show brief success indicator
                    const badge = document.createElement('span');
                    badge.className = 'fixed bottom-4 right-4 rounded-lg bg-emerald-500 px-4 py-2 text-sm text-white shadow-lg';
                    badge.textContent = 'Sıralama kaydedildi';
                    document.body.appendChild(badge);
                    setTimeout(() => badge.remove(), 2000);
                }
            });
        }
    });
});
</script>
@endpush
