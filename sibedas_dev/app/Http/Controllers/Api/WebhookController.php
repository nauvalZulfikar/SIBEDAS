<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function github(Request $request)
    {
        $secret = config('app.webhook_secret');
        $signature = $request->header('X-Hub-Signature-256');
        $payload = $request->getContent();
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expected, $signature ?? '')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $event = $request->header('X-GitHub-Event');
        $data = $request->json()->all();

        if ($event !== 'push' || ($data['ref'] ?? '') !== 'refs/heads/master') {
            return response()->json(['status' => 'ignored']);
        }

        // Write a deploy flag file on the shared storage volume
        // A cron job on the host watches this file and runs git pull + optimize:clear
        file_put_contents('/var/www/storage/deploy.flag', date('Y-m-d H:i:s'));

        return response()->json(['status' => 'queued']);
    }
}
