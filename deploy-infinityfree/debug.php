<?php
// JOTIFY Debug Helper - DELETE after use!
// Visit: http://jotify.xo.je/debug.php

// 1. Clear bootstrap cache (fixes most 500 errors)
$cacheFiles = glob(__DIR__ . '/bootstrap/cache/*.php');
$cleared = [];
foreach ($cacheFiles as $file) {
    $cleared[] = basename($file);
    unlink($file);
}

// 2. Show PHP & env info
echo '<pre style="font-family:monospace;background:#0f172a;color:#e2e8f0;padding:20px;">';
echo "=== JOTIFY Debug Report ===\n\n";

// ENV file
if (file_exists('.env')) {
    $env = file_get_contents('.env');
    // Mask passwords
    $env = preg_replace('/(PASSWORD|SECRET|KEY)=.+/i', '$1=***HIDDEN***', $env);
    echo "✅ .env found:\n$env\n\n";
} else {
    echo "❌ .env NOT FOUND!\n\n";
}

// Cleared cache
if (count($cleared) > 0) {
    echo "🗑 Cleared bootstrap/cache/:\n";
    foreach ($cleared as $f) echo "  - $f\n";
} else {
    echo "ℹ️ bootstrap/cache/ already empty\n";
}

echo "\n✅ PHP: " . PHP_VERSION . "\n";
echo "✅ Dir: " . __DIR__ . "\n";

// Test DB connection
echo "\n=== DB Connection Test ===\n";
$dotenv = file_get_contents('.env');
preg_match('/DB_HOST=(.+)/', $dotenv, $host);
preg_match('/DB_DATABASE=(.+)/', $dotenv, $db);
preg_match('/DB_USERNAME=(.+)/', $dotenv, $user);
preg_match('/DB_PASSWORD=(.+)/', $dotenv, $pass);

$h = trim($host[1] ?? '');
$d = trim($db[1] ?? '');
$u = trim($user[1] ?? '');
$p = trim($pass[1] ?? '');

try {
    $pdo = new PDO("mysql:host=$h;dbname=$d;charset=utf8", $u, $p);
    echo "✅ Database connected! Tables:\n";
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "  - {$row[0]}\n";
    }
} catch (Exception $e) {
    echo "❌ DB Error: " . $e->getMessage() . "\n";
}

// Try booting Laravel
echo "\n=== Laravel Boot Test ===\n";
try {
    require __DIR__ . '/vendor/autoload.php';
    echo "✅ vendor/autoload.php loaded\n";
    $app = require_once __DIR__ . '/bootstrap/app.php';
    echo "✅ bootstrap/app.php loaded\n";
} catch (Throwable $e) {
    echo "❌ Laravel Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    echo "   Trace:\n" . substr($e->getTraceAsString(), 0, 1000) . "\n";
}

echo "\n⚠️ DELETE this file after debugging!";
echo '</pre>';
