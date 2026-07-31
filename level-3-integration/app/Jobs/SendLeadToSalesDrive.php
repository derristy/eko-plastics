<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\SalesDrive;
use App\Services\Telegram;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendLeadToSalesDrive implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public Lead $lead)
    {
    }

    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(SalesDrive $salesDrive): void
    {
        $orderId = $salesDrive->createOrder($this->lead);

        $this->lead->update([
            'salesdrive_status' => 'sent',
            'salesdrive_order_id' => $orderId,
            'last_error' => null,
        ]);

        // передаём дальше в Діловод по событию успешного создания заявки
        SendContactToDilovod::dispatch($this->lead);
    }

    public function failed(Throwable $e): void
    {
        $this->lead->update([
            'salesdrive_status' => 'failed',
            'last_error' => $e->getMessage(),
        ]);

        app(Telegram::class)->send(
            "❌ Заявка #{$this->lead->id} не ушла в SalesDrive\n"
            . htmlspecialchars($e->getMessage())
        );
    }
}
