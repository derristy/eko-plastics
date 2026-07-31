<?php

namespace App\Console\Commands;

use App\Services\Dilovod;
use App\Services\SalesDrive;
use App\Services\Telegram;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckIntegrations extends Command
{
    protected $signature = 'integrations:check';

    protected $description = 'Проверяет доступность SalesDrive и Діловод, шлёт алерт в Telegram';

    public function handle(SalesDrive $salesDrive, Dilovod $dilovod, Telegram $telegram): int
    {
        $services = [
            'SalesDrive' => fn() => $salesDrive->ping(),
            'Діловод' => fn() => $dilovod->ping(),
        ];

        foreach ($services as $name => $ping) {
            try {
                $alive = $ping();
                $error = null;
            } catch (Throwable $e) {
                $alive = false;
                $error = $e->getMessage();
            }

            $this->line($name . ': ' . ($alive ? 'ok' : 'недоступен'));
            $this->report($name, $alive, $error, $telegram);
        }

        return self::SUCCESS;
    }

    private function report(string $name, bool $alive, ?string $error, Telegram $telegram): void
    {
        $key = 'integration_down:' . md5($name);
        $wasDown = Cache::get($key, false);

        if (!$alive && !$wasDown) {
            Cache::put($key, true, now()->addDay());
            Log::error('интеграция недоступна', ['service' => $name, 'error' => $error]);
            $telegram->send("🔴 <b>{$name}</b> не отвечает\n" . htmlspecialchars((string) $error));

            return;
        }

        if ($alive && $wasDown) {
            Cache::forget($key);
            $telegram->send("🟢 <b>{$name}</b> снова отвечает");
        }
    }
}
