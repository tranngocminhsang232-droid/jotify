<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    Illuminate\Support\Facades\Mail::raw('Test email từ JOTIFY - Laravel Mail đang hoạt động!', function($message) {
        $message->to('kietle07122006@gmail.com')
                ->subject('JOTIFY Mail Test ✅');
    });
    echo "✅ Mail gửi thành công!\n";
} catch (\Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}
