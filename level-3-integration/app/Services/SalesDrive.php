<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SalesDrive
{
    public function __construct(
        private readonly string $url,
        private readonly string $apiKey,
    ) {
    }

    public function createOrder(Lead $lead): string
    {
        $response = $this->request()->post('/handler/', [
            'getResultData' => '1',
            'fName' => $lead->first_name,
            'lName' => $lead->last_name,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'comment' => $lead->message,
            'externalId' => (string) $lead->id,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('SalesDrive ответил ' . $response->status() . ': ' . $response->body());
        }

        $data = $response->json();

        if (empty($data['success'])) {
            throw new RuntimeException('SalesDrive отклонил заявку: ' . $response->body());
        }

        $orderId = $data['data']['orderId'] ?? null;

        if (!$orderId) {
            throw new RuntimeException('SalesDrive не вернул orderId: ' . $response->body());
        }

        return (string) $orderId;
    }

    public function ping(): bool
    {
        // update без идентификатора отвечает осмысленной ошибкой,
        // если ключ принят, и 401 если API недоступно
        $response = $this->request()->timeout(10)->post('/api/order/update/', []);

        return $response->status() < 500 && $response->status() !== 401;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->url, '/'))
            ->timeout(20)
            ->acceptJson()
            ->withHeaders(array_filter([
                'X-Api-Key' => $this->apiKey ?: null,
            ]));
    }
}
