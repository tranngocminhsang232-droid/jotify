<?php
/**
 * JOTIFY — InfinityFree One-Time Setup Script
 * Upload this file to htdocs/, visit it once, then DELETE it immediately.
 * URL: https://your-domain.infinityfreeapp.com/setup.php
 */

// ── Security: change this secret before uploading ──
$secret = 'JOTIFY_SETUP_2026';
if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    http_response_code(403);
    die('<h1>403 Forbidden</h1><p>Pass ?key=JOTIFY_SETUP_2026 to run setup.</p>');
}

$log = [];

// ── 1. Create required storage directories ──
$dirs = [
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/framework/testing',
    'storage/logs',
    'storage/app/public/note-images',
    'bootstrap/cache',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            $log[] = "✅ Created: $dir";
        } else {
            $log[] = "❌ Failed to create: $dir";
        }
    } else {
        $log[] = "⏭ Already exists: $dir";
    }
}

// ── 2. Create .gitignore in storage/app/public to keep the folder ──
$gitignore = "storage/app/public";
if (!file_exists($gitignore . '/.gitignore')) {
    file_put_contents($gitignore . '/.gitignore', "*\n!.gitignore\n");
    $log[] = "✅ Created storage/app/public/.gitignore";
}

// ── 3. Check writable ──
$writeable = [
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
];
foreach ($writeable as $dir) {
    $log[] = is_writable($dir) ? "✅ Writable: $dir" : "❌ NOT writable: $dir (chmod 755)";
}

// ── 4. PHP version check ──
$log[] = "ℹ️ PHP Version: " . PHP_VERSION;
$log[] = "ℹ️ Extensions: " . implode(', ', ['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo']);

// ── 5. Check .env exists ──
$log[] = file_exists('.env') ? "✅ .env exists" : "❌ .env NOT found — please create it!";

// ── 6. Check vendor ──
$log[] = is_dir('vendor') ? "✅ vendor/ exists" : "❌ vendor/ missing — upload vendor folder!";

// ── 7. Check build assets ──
$log[] = is_dir('build') ? "✅ build/ exists (Vite assets)" : "❌ build/ missing — upload public/build/ contents!";

// ── Output ──
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>JOTIFY Setup</title>
<style>body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:2rem;} .ok{color:#4ade80;} .err{color:#f87171;} .skip{color:#94a3b8;}</style></head><body>';
echo '<h1>🎵 JOTIFY Setup Report</h1>';
echo '<p style="color:#f59e0b;">⚠️ DELETE this file immediately after setup!</p>';
echo '<hr style="border-color:#334155;"><ul>';
foreach ($log as $line) {
    $class = str_starts_with($line, '✅') ? 'ok' : (str_starts_with($line, '❌') ? 'err' : 'skip');
    echo "<li class=\"$class\">$line</li>";
}
echo '</ul>';
echo '<p>✅ Setup complete. Now go to your domain and test the app.</p>';
echo '</body></html>';
