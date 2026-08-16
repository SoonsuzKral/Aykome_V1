@php
    // 16.08 ÇOKLU İMZA GÖSTERGESİ: süreçteki TÜM adımlar + durumları tek bakışta.
    // "Kaç kişi imzalayacak, sırası ne, kim zaten onayladı/imzaladı" sorusuna cevap.
    $approvalLogByStepId = collect($application->approval_log ?? [])->keyBy('step_id');
    $actionIcon = fn (string $type) => match ($type) {
        'e_imza' => '🔏',
        'paraf' => '📝',
        default => '✅',
    };
    $actionLabel = fn (string $type) => match ($type) {
        'e_imza' => 'E-İmza',
        'paraf' => 'Paraf',
        default => 'Onay',
    };
@endphp
@if(($processSteps ?? collect())->isNotEmpty())
<div class="mb-3 rounded-xl border border-slate-200 bg-white p-3">
    <p class="text-xs font-semibold text-slate-700 mb-2">
        🧭 Süreç — {{ $processSteps->count() }} kişi imza/onay silsilesinde
    </p>
    <ol class="space-y-1.5">
        @foreach($processSteps as $item)
            @php
                $step = $item['step'];
                $durum = $item['durum'];
                $logEntry = $approvalLogByStepId->get($step->id);
                $actionType = $step->action_type ?? 'onay';
            @endphp
            <li class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs
                {{ $durum === 'tamamlandi' ? 'bg-emerald-50 text-emerald-800' : ($durum === 'aktif' ? 'bg-blue-50 text-blue-800 ring-1 ring-blue-300' : 'bg-slate-50 text-slate-500') }}">
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold
                    {{ $durum === 'tamamlandi' ? 'bg-emerald-500 text-white' : ($durum === 'aktif' ? 'bg-blue-500 text-white' : 'bg-slate-300 text-slate-600') }}">
                    {{ $durum === 'tamamlandi' ? '✓' : $loop->iteration }}
                </span>
                <span class="font-medium">{{ $step->name }}</span>
                <span class="rounded bg-white/70 px-1.5 py-0.5 text-[10px] font-semibold">{{ $actionIcon($actionType) }} {{ $actionLabel($actionType) }}</span>
                <span class="ml-auto text-[11px]">
                    @if($durum === 'tamamlandi' && $logEntry)
                        {{ $logEntry['approved_by_name'] ?? $logEntry['user_name'] ?? '' }} —
                        {{ \Illuminate\Support\Carbon::parse($logEntry['approved_at'] ?? $logEntry['paraf_at'] ?? null)?->format('d.m.Y H:i') }}
                    @elseif($durum === 'aktif')
                        <span class="font-semibold">Şu an bekleniyor</span>
                    @else
                        Bekliyor
                    @endif
                </span>
            </li>
        @endforeach
    </ol>
</div>
@endif
