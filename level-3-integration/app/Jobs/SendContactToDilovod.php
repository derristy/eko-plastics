<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\Dilovod;
use App\Services\Telegram;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendContactToDilovod implements ShouldQueue
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

    public function handle(Dilovod $dilovod): void
    {
        $personId = $dilovod->createClient($this->lead);

        $this->lead->update([
            'dilovod_status' => 'sent',
            'dilovod_person_id' => $personId,
            'last_error' => null,
        ]);
    }

    public function failed(Throwable $e): void
    {
        $this->lead->update([
            'dilovod_status' => 'failed',
            'last_error' => $e->getMessage(),
        ]);

        app(Telegram::class)->send(
            "❌ Контакт по заявке #{$this->lead->id} не ушёл в Діловод\n"
            . htmlspecialchars($e->getMessage())
        );
    }
}
