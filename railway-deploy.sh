#!/bin/bash
# Railway deployment script — chạy sau khi build xong

echo "=== JOTIFY Deploy Script ==="

# 1. Chạy migrations
echo "Running migrations..."
php artisan migrate --force

# 2. Tạo storage link (dùng cho avatar, v.v.)
echo "Creating storage link..."
php artisan storage:link || true

# 3. Clear cache cũ (phòng trường hợp có cache stale)
echo "Clearing old cache..."
php artisan cache:clear

echo "=== Deploy completed! ==="
