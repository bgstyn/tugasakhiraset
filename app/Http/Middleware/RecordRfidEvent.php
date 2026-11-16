<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RecordRfidEvent
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->header('Idempotency-Key');
        if ($id === null) {
            return $next($request);
        }
        if (! Str::isUuid($id)) {
            return response()->json(['success' => false, 'message' => 'Idempotency-Key harus berupa UUID.'], 400);
        }

        $id = strtolower($id);
        $payload = $request->except('api_token');
        ksort($payload);
        $fingerprint = hash('sha256', json_encode([$request->route()->getName(), $payload], JSON_THROW_ON_ERROR));
        $request->attributes->set('rfid_event_id', $id);

        return DB::transaction(function () use ($request, $next, $id, $fingerprint) {
            $receipts = DB::table('rfid_event_receipts');
            $claimed = $receipts->insertOrIgnore([
                'id' => $id,
                'fingerprint' => $fingerprint,
                'created_at' => now(),
            ]);

            if (! $claimed) {
                $receipt = $receipts->where('id', $id)->lockForUpdate()->first();
                if ($receipt->fingerprint !== $fingerprint) {
                    return response()->json(['success' => false, 'message' => 'Idempotency-Key sudah digunakan untuk data lain.'], 409);
                }

                return response($receipt->body, $receipt->status, ['Content-Type' => 'application/json']);
            }

            $response = $next($request);
            if ($response->getStatusCode() >= 500) {
                throw new HttpResponseException($response);
            }
            $receipts->where('id', $id)->update([
                'status' => $response->getStatusCode(),
                'body' => $response->getContent(),
            ]);

            return $response;
        }, 3);
    }
}
