<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class Dilovod
{
    private const VERSION = '0.25';

    public function __construct(
        private readonly string $url,
        private readonly string $key,
        private readonly string $personType,
        private readonly string $group,
    ) {
    }

    public function createClient(Lead $lead): string
    {
        $name = $lead->fullName();

        // телефон и email в этом справочнике хранятся в details,
        // одноимённые поля через API не заполняются
        $details = array_filter([
            'phone' => $lead->phone,
            'email' => $lead->email,
        ]);

        $header = [
            'id' => 'catalogs.persons',
            'name' => ['uk' => $name, 'ru' => $name],
            'personType' => $this->personType,
            'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
            'remark' => $lead->message,
        ];

        // группа-категория «Клієнти» по требованию ТЗ
        if ($this->group !== '') {
            $header['parent'] = $this->group;
        }

        $response = $this->call('saveObject', ['header' => $header]);

        $id = $response['id'] ?? $response['header']['id'] ?? null;

        if (!$id) {
            throw new RuntimeException('Діловод не вернул id контрагента: ' . json_encode($response, JSON_UNESCAPED_UNICODE));
        }

        return (string) $id;
    }

    public function ping(): bool
    {
        try {
            $this->call('request', ['from' => 'catalogs.persons', 'fields' => ['id' => 'id'], 'limit' => 1]);

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    private function call(string $action, array $params): array
    {
        $packet = [
            'version' => self::VERSION,
            'key' => $this->key,
            'action' => $action,
            'params' => $params,
        ];

        $response = Http::timeout(20)
            ->asForm()
            ->post(rtrim($this->url, '/'), [
                'packet' => json_encode($packet, JSON_UNESCAPED_UNICODE),
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Діловод ответил ' . $response->status() . ': ' . $response->body());
        }

        $data = $response->json();

        if (isset($data['error'])) {
            throw new RuntimeException('Діловод вернул ошибку: ' . json_encode($data['error'], JSON_UNESCAPED_UNICODE));
        }

        return is_array($data) ? $data : [];
    }
}
