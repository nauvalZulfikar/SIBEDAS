<?php
/**
 * GitHub Webhook Receiver
 * Place this file at: /var/www/SIBEDAS/webhook.php
 * Access via: https://sibedaspbg.cloud/webhook.php (served by nginx directly)
 *
 * Setup: Add GitHub webhook pointing to https://sibedaspbg.cloud/webhook
 * with secret matching WEBHOOK_SECRET below.
 */

define('WEBHOOK_SECRET', getenv('WEBHOOK_SECRET') ?: 'sibedas-webhook-secret');
define('APP_DIR', '/var/www/SIBEDAS/sibedas_dev');
define('CONTAINER', 'sibedas_app');
define('LOG_FILE', '/var/log/sibedas-deploy.log');

function logMsg(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents(LOG_FILE, $line, FILE_APPEND);
    echo $line;
}

function run(string $cmd): string {
    $output = shell_exec($cmd . ' 2>&1');
    logMsg("CMD: $cmd");
    logMsg("OUT: " . trim($output));
    return trim($output);
}

// Verify signature
$payload = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);

if (!hash_equals($expected, $sig)) {
    http_response_code(401);
    logMsg('Unauthorized webhook request');
    exit('Unauthorized');
}

$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
$data = json_decode($payload, true);

if ($event !== 'push' || ($data['ref'] ?? '') !== 'refs/heads/master') {
    http_response_code(200);
    echo 'ignored';
    exit;
}

logMsg('=== Deploy triggered by push to master ===');
logMsg('Commit: ' . ($data['head_commit']['message'] ?? 'unknown'));

// Pull latest code
run('cd ' . APP_DIR . ' && git pull origin master 2>&1');

// Get list of changed files from git
$changedFiles = run('cd ' . APP_DIR . ' && git diff --name-only HEAD~1 HEAD 2>&1');
logMsg('Changed files: ' . $changedFiles);

// Docker cp each changed PHP/config file into container
$lines = explode("\n", $changedFiles);
foreach ($lines as $file) {
    $file = trim($file);
    if (empty($file)) continue;

    $localPath = APP_DIR . '/' . $file;
    if (!file_exists($localPath)) {
        logMsg("Skipping (deleted): $file");
        continue;
    }

    // Map local path to container path
    $containerPath = '/var/www/' . $file;
    run("docker cp '$localPath' " . CONTAINER . ":$containerPath");
}

// Clear cache
run('docker exec ' . CONTAINER . ' php artisan optimize:clear');

logMsg('=== Deploy complete ===');
http_response_code(200);
echo 'deployed';
