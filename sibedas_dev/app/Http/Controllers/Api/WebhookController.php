<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function github(Request $request)
    {
        // Verify GitHub signature
        $secret = config('app.webhook_secret');
        $signature = $request->header('X-Hub-Signature-256');
        $payload = $request->getContent();
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        Log::info('Webhook: received', ['event' => $request->header('X-GitHub-Event')]);

        if (!hash_equals($expected, $signature ?? '')) {
            Log::warning('Webhook: invalid signature');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $event = $request->header('X-GitHub-Event');
        $data = $request->json()->all();

        if ($event !== 'push' || ($data['ref'] ?? '') !== 'refs/heads/master') {
            return response()->json(['status' => 'ignored']);
        }

        Log::info('Webhook: deploy triggered', [
            'commit' => $data['head_commit']['message'] ?? 'unknown',
        ]);

        // Run deploy script (repo dir is bind-mounted into container)
        $repoDir = '/var/www/SIBEDAS/sibedas_dev/sibedas_dev';
        $appDir = '/var/www';

        // Git pull
        shell_exec("cd $repoDir && git config --global --add safe.directory $repoDir && git pull origin master 2>&1");

        // Get changed files
        $changed = shell_exec("cd $repoDir && git diff --name-only HEAD~1 HEAD 2>&1");
        Log::info('Webhook: changed files', ['files' => $changed]);

        $files = array_filter(explode("\n", trim($changed ?? '')));
        foreach ($files as $file) {
            $file = preg_replace('#^sibedas_dev/#', '', trim($file));
            $localPath = "$repoDir/$file";
            if (!file_exists($localPath)) continue;
            $containerPath = "$appDir/$file";
            $out = shell_exec("docker cp '$localPath' sibedas_app:$containerPath 2>&1");
            Log::info("Webhook: copied $file", ['out' => $out]);
        }

        shell_exec("docker exec sibedas_app php artisan optimize:clear 2>&1");

        Log::info('Webhook: deploy complete');
        return response()->json(['status' => 'deployed']);
    }
}
