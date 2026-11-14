<?php

namespace App\Core\Notifications\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Expose the VAPID public key for the browser Push API.
 * The private key never leaves the server.
 */
class VapidPublicKeyController
{
    public function __invoke(): JsonResponse
    {
        $publicKey = config('webpush.vapid.public_key');

        if (! is_string($publicKey) || $publicKey === '') {
            return response()->json([
                'message' => 'Web Push is not configured on this server yet.',
            ], 503);
        }

        return response()->json([
            'publicKey' => $publicKey,
        ]);
    }
}
