<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Telegram
{
    public function __construct(
        private readonly string $token,
        private readonly string $chatId,
    ) {
    }

    public function send(string $text): bool
    {
        if ($this->token === '' || $this->chatId === '') {
            Log::warning('telegram не настроен, сообщение не отправлено', ['text' => $text]);

            return false;
        }

        $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->token}/sendMessage", [
            'chat_id' => $this->chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);

        if (!$response->successful()) {
            Log::error('telegram не принял сообщение', ['status' => $response->status(), 'body' => $response->body()]);
        }

        return $response->successful();
    }
}
