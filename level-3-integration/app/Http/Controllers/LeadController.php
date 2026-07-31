<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadRequest;
use App\Jobs\SendContactToDilovod;
use App\Jobs\SendLeadToSalesDrive;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeadController extends Controller
{
    public function store(LeadRequest $request): JsonResponse
    {
        $lead = Lead::create([
            'first_name' => $request->string('first_name')->trim()->value(),
            'last_name' => $request->string('last_name')->trim()->value(),
            'email' => $request->string('email')->trim()->value() ?: null,
            'phone' => $request->string('phone')->value(),
            'message' => $request->string('message')->trim()->value() ?: null,
            'ip' => $request->ip(),
        ]);

        SendLeadToSalesDrive::dispatch($lead);

        return response()->json(['ok' => true, 'id' => $lead->id], 201);
    }

    public function dilovodWebhook(Request $request): JsonResponse
    {
        $secret = config('integrations.dilovod.webhook_secret');

        if ($secret !== '' && !hash_equals($secret, (string) $request->header('X-Webhook-Secret'))) {
            return response()->json(['ok' => false], 403);
        }

        $personId = $request->input('id', $request->input('header.id'));

        Log::info('вебхук Діловод', ['person' => $personId, 'payload' => $request->all()]);

        if (!$personId) {
            return response()->json(['ok' => true, 'skipped' => 'no id']);
        }

        $lead = Lead::where('dilovod_person_id', (string) $personId)->first();

        if (!$lead) {
            return response()->json(['ok' => true, 'skipped' => 'lead not found']);
        }

        $lead->update(['dilovod_status' => 'confirmed']);

        return response()->json(['ok' => true, 'lead' => $lead->id]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $secret = config('integrations.salesdrive.webhook_secret');

        if ($secret !== '' && !hash_equals($secret, (string) $request->header('X-Webhook-Secret'))) {
            return response()->json(['ok' => false], 403);
        }

        $orderId = $request->input('data.id', $request->input('id'));
        $externalId = $request->input('data.externalId', $request->input('externalId'));

        $lead = Lead::query()
            ->when($externalId, fn($q) => $q->orWhere('id', $externalId))
            ->when($orderId, fn($q) => $q->orWhere('salesdrive_order_id', (string) $orderId))
            ->first();

        if (!$lead) {
            Log::warning('вебхук SalesDrive: заявка не найдена', $request->all());

            return response()->json(['ok' => false, 'error' => 'lead not found'], 404);
        }

        if ($lead->dilovod_status === 'sent') {
            return response()->json(['ok' => true, 'skipped' => true]);
        }

        SendContactToDilovod::dispatch($lead);

        return response()->json(['ok' => true]);
    }
}
