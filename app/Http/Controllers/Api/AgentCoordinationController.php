<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * İki LLM arası koordinasyon kanalı (Claude ↔ Minimax).
 *
 * Dosya tabanlıdır — DB migration gerektirmez. storage/app/coordination/
 * altındaki messages.json'ı okur/yazar.
 *
 * Protokol:
 *   GET    /api/coordination                 — tüm mesajları listeler
 *   GET    /api/coordination?since=<id>      — <id> numaralı mesajdan sonrakiler
 *   POST   /api/coordination                 — mesaj gönderir (agent, message, task)
 *   DELETE /api/coordination                 — tüm mesajları temizler
 *
 * Auth: X-Coordination-Key header = AGENT_COORDINATION_API_KEY (.env)
 */
class AgentCoordinationController extends Controller
{
    protected function file(): string
    {
        return 'coordination/messages.json';
    }

    protected function checkKey(Request $request): ?string
    {
        $key = $request->header('X-Coordination-Key');
        $expected = config('agent-coordination.api_key');
        if (!$expected || !$key || !hash_equals($expected, $key)) {
            return null;
        }
        return $key;
    }

    /** GET /api/coordination — tüm mesajları veya ?since=<id> sonrasını listeler. */
    public function index(Request $request)
    {
        if (!$this->checkKey($request)) {
            return response()->json(['error' => 'Geçersiz API anahtarı.'], 401);
        }

        $messages = $this->readMessages();

        if ($since = $request->query('since')) {
            $messages = array_filter($messages, fn ($m) => ($m['id'] ?? 0) > (int) $since);
        }

        return response()->json([
            'messages' => array_values($messages),
            'count' => count($messages),
        ]);
    }

    /** POST /api/coordination — yeni mesaj gönder. */
    public function store(Request $request)
    {
        if (!$this->checkKey($request)) {
            return response()->json(['error' => 'Geçersiz API anahtarı.'], 401);
        }

        $request->validate([
            'agent'   => 'required|string|max:50',
            'message' => 'required|string|max:2000',
            'task'    => 'nullable|string|max:100',
        ]);

        $messages = $this->readMessages();
        $maxId = empty($messages) ? 0 : max(array_column($messages, 'id'));

        $messages[] = [
            'id'       => $maxId + 1,
            'agent'    => $request->input('agent'),
            'task'     => $request->input('task'),
            'message'  => $request->input('message'),
            'timestamp'=> now()->toISOString(),
        ];

        $this->writeMessages($messages);

        return response()->json(['status' => 'ok', 'id' => $maxId + 1], 201);
    }

    /** DELETE /api/coordination — mesajları temizle (yönetici). */
    public function destroy(Request $request)
    {
        if (!$this->checkKey($request)) {
            return response()->json(['error' => 'Geçersiz API anahtarı.'], 401);
        }

        $this->writeMessages([]);

        return response()->json(['status' => 'ok', 'message' => 'Tüm mesajlar temizlendi.']);
    }

    protected function readMessages(): array
    {
        if (!Storage::disk('local')->exists($this->file())) {
            return [];
        }
        $raw = Storage::disk('local')->get($this->file());
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    protected function writeMessages(array $messages): void
    {
        Storage::disk('local')->put($this->file(), json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
