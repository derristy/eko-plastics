<?php

namespace Tests\Feature;

use App\Jobs\SendContactToDilovod;
use App\Jobs\SendLeadToSalesDrive;
use App\Models\Lead;
use App\Services\SalesDrive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Іван',
            'last_name' => 'Петренко',
            'email' => 'ivan@example.com',
            'phone' => '+380671234567',
            'message' => 'Цікавить тепла підлога',
            'form_started_at' => (now()->timestamp - 30) * 1000,
        ], $overrides);
    }

    public function test_принимает_корректную_заявку(): void
    {
        Queue::fake();

        $this->postJson('/api/lead', $this->payload())
            ->assertCreated()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('leads', ['phone' => '+380671234567', 'first_name' => 'Іван']);
        Queue::assertPushed(SendLeadToSalesDrive::class);
    }

    public function test_имя_только_буквы(): void
    {
        Queue::fake();

        $this->postJson('/api/lead', $this->payload(['first_name' => 'Іван123']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('first_name');

        Queue::assertNothingPushed();
    }

    public function test_телефон_только_украинских_операторов(): void
    {
        Queue::fake();

        foreach (['+380121234567', '+79161234567', '+38067123456', '0671234567'] as $phone) {
            $this->postJson('/api/lead', $this->payload(['phone' => $phone]))
                ->assertStatus(422)
                ->assertJsonValidationErrors('phone');
        }

        $this->postJson('/api/lead', $this->payload(['phone' => '+380 (63) 123-45-67']))
            ->assertCreated();
    }

    public function test_ловушка_отсекает_бота(): void
    {
        Queue::fake();

        $this->postJson('/api/lead', $this->payload(['company' => 'spam ltd']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('company');

        Queue::assertNothingPushed();
    }

    public function test_слишком_быстрая_отправка_отклоняется(): void
    {
        Queue::fake();

        $this->postJson('/api/lead', $this->payload(['form_started_at' => now()->timestamp * 1000]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('form_started_at');

        Queue::assertNothingPushed();
    }

    public function test_после_salesdrive_ставится_задача_в_діловод(): void
    {
        Queue::fake();

        Http::preventStrayRequests();
        Http::fake([
            '*' => Http::response(['success' => true, 'data' => ['orderId' => 777]], 200),
        ]);

        $lead = Lead::create([
            'first_name' => 'Іван',
            'phone' => '+380671234567',
        ]);

        (new SendLeadToSalesDrive($lead))->handle(app(SalesDrive::class));

        $this->assertSame('sent', $lead->fresh()->salesdrive_status);
        $this->assertSame('777', $lead->fresh()->salesdrive_order_id);
        Queue::assertPushed(SendContactToDilovod::class);
    }
}
