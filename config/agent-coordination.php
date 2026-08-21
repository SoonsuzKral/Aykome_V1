<?php

return [
    /*
    |-----------------------------------------------------------------
    | Agent Coordination API Key
    |-----------------------------------------------------------------
    | Claude Code ve Minimax LLM'leri arası mesajlaşma için paylaşılan
    | anahtar. .env'de AGENT_COORDINATION_API_KEY olarak tanımlanır.
    |
    | Kullanım: HTTP header X-Coordination-Key: <değer>
    */
    'api_key' => env('AGENT_COORDINATION_API_KEY', ''),
];
