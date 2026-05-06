# JOTIFY — Note Management System

Ứng dụng web quản lý ghi chú cá nhân, được xây dựng trên **Laravel 13**, **Tailwind CSS 4**, **Alpine.js**, và **Vite**.

---

## Mục lục

1. [Yêu cầu hệ thống](#yêu-cầu-hệ-thống)
2. [Cài đặt XAMPP](#cài-đặt-xampp)
3. [Cài đặt Composer & Node.js](#cài-đặt-composer--nodejs)
4. [Hướng dẫn cài đặt dự án](#hướng-dẫn-cài-đặt-dự-án)
5. [Cấu hình môi trường (.env)](#cấu-hình-môi-trường-env)
6. [Tạo database & import dữ liệu](#tạo-database--import-dữ-liệu)
7. [Chạy dự án](#chạy-dự-án)
8. [Các lỗi phổ biến & Cách khắc phục](#các-lỗi-phổ-biến--cách-khắc-phục)
9. [Thông tin bổ sung](#thông-tin-bổ-sung)

---

## Yêu cầu hệ thống

| Phần mềm      | Phiên bản tối thiểu | Ghi chú                                   |
| -------------- | -------------------- | ------------------------------------------ |
| **XAMPP**      | 8.2+                 | Bao gồm PHP 8.3+ và MySQL/MariaDB         |
| **PHP**        | 8.3+                 | Đã có sẵn trong XAMPP                      |
| **Composer**   | 2.x                  | PHP package manager                        |
| **Node.js**    | 18+                  | Để build frontend assets                   |
| **npm**        | 9+                   | Đi kèm Node.js                            |

---

## Cài đặt XAMPP

1. Tải XAMPP phiên bản **8.2 trở lên** (PHP 8.3+) tại: [https://www.apachefriends.org](https://www.apachefriends.org)
2. Cài đặt XAMPP theo hướng dẫn mặc định (khuyến nghị cài vào `C:\xampp82`).
3. Mở **XAMPP Control Panel** → Bật **Apache** và **MySQL**.

> **Lưu ý:** Đảm bảo PHP 8.3+ đã được kích hoạt. Kiểm tra bằng lệnh:
> ```bash
> php -v
> ```
> Nếu terminal không nhận lệnh `php`, hãy thêm đường dẫn PHP của XAMPP vào biến môi trường `PATH` (ví dụ: `C:\xampp82\php`).

---

## Cài đặt Composer & Node.js

### Composer
1. Tải Composer tại: [https://getcomposer.org/download/](https://getcomposer.org/download/)
2. Chạy installer → chọn đúng đường dẫn PHP của XAMPP (ví dụ: `C:\xampp82\php\php.exe`).
3. Kiểm tra sau khi cài:
   ```bash
   composer -V
   ```

### Node.js & npm
1. Tải Node.js (LTS) tại: [https://nodejs.org](https://nodejs.org)
2. Cài đặt theo hướng dẫn mặc định.
3. Kiểm tra sau khi cài:
   ```bash
   node -v
   npm -v
   ```

---

## Hướng dẫn cài đặt dự án

### Bước 1: Đặt source code vào thư mục htdocs

Copy / giải nén toàn bộ source code vào thư mục `htdocs` của XAMPP:

```
C:\xampp82\htdocs\CKWeb2\
```

### Bước 2: Cài đặt PHP dependencies

Mở terminal (Command Prompt / PowerShell / Git Bash) tại thư mục dự án:

```bash
cd C:\xampp82\htdocs\CKWeb2
composer install
```

> Nếu gặp lỗi thiếu extension PHP (ví dụ: `ext-zip`, `ext-gd`,...), xem mục [Lỗi thiếu PHP Extension](#4-lỗi-thiếu-php-extension).

### Bước 3: Cài đặt Frontend dependencies

```bash
npm install
```

### Bước 4: Build frontend assets (lần đầu)

```bash
npm run build
```

---

## Cấu hình môi trường (.env)

### Tạo file .env

```bash
copy .env.example .env
```

### Chỉnh sửa file .env

Mở file `.env` bằng text editor (VS Code, Notepad++,...) và cập nhật các giá trị sau:

```dotenv
APP_NAME="JOTIFY"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/CKWeb2/public

# --- Database (MySQL qua XAMPP) ---
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ckweb2
DB_USERNAME=root
DB_PASSWORD=

# --- Session ---
SESSION_DRIVER=file

# --- Pusher (Real-time) ---
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID="2148903"
PUSHER_APP_KEY="f65eba87f2343868c032"
PUSHER_APP_SECRET="00988ac75cb0f88da7ca"
PUSHER_APP_CLUSTER="ap1"

VITE_PUSHER_APP_KEY="f65eba87f2343868c032"
VITE_PUSHER_APP_CLUSTER="ap1"

# --- Mail (tuỳ chỉnh nếu cần) ---
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

> **Lưu ý về Mail:** Nếu muốn tính năng gửi email hoạt động, bạn cần tạo **App Password** trong Google Account (bật 2FA trước). Nếu không cần, đổi `MAIL_MAILER=log` để email chỉ ghi vào log.

### Generate Application Key

```bash
php artisan key:generate
```

---

## Tạo database & import dữ liệu

### Cách 1: Tạo database mới (trống)

1. Mở trình duyệt → truy cập **phpMyAdmin**: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Nhấn **"New"** (hoặc "Mới") ở sidebar trái.
3. Đặt tên database: `ckweb2` → Collation: `utf8mb4_unicode_ci` → Nhấn **Create**.
4. Quay lại terminal, chạy migration:
   ```bash
   php artisan migrate
   ```
   *(Lệnh này sẽ tạo tất cả các bảng cần thiết)*

### Cách 2: Import từ file SQL (có sẵn dữ liệu mẫu)

1. Mở **phpMyAdmin** → tạo database `ckweb2` như Cách 1.
2. Chọn database `ckweb2` → vào tab **"Import"**.
3. Nhấn **"Choose File"** → chọn file `database/CKWeb2_dump.sql` trong thư mục dự án.
4. Nhấn **"Go"** / **"Thực hiện"** để import.

> **Lưu ý:** Nếu dùng Cách 2, **không cần** chạy `php artisan migrate` nữa vì file SQL đã chứa đầy đủ cấu trúc bảng.

---

## Chạy dự án

### Chạy qua XAMPP (Apache) — Khuyến nghị

Vì source code đã nằm trong `htdocs`, bạn chỉ cần:

1. Đảm bảo **Apache** và **MySQL** đang chạy trong XAMPP Control Panel.
2. Tạo **storage link** (chỉ cần chạy 1 lần):
   ```bash
   php artisan storage:link
   ```
3. Chạy Vite dev server cho frontend hot-reload:
   ```bash
   npm run dev
   ```
4. Truy cập ứng dụng tại: **http://localhost/CKWeb2/public**

### Chạy bằng PHP Built-in Server (thay thế)

Nếu không muốn dùng Apache, bạn có thể dùng server có sẵn của Laravel:

```bash
# Terminal 1: Frontend
npm run dev

# Terminal 2: Backend
php artisan serve
```

Truy cập tại: **http://localhost:8000**

### Chạy Queue Worker (tùy chọn — cần cho gửi email)

Nếu bạn muốn tính năng gửi email (chia sẻ ghi chú, quên mật khẩu,...) hoạt động:

```bash
php artisan queue:listen
```

---

## Các lỗi phổ biến & Cách khắc phục

### 1. Lỗi Storage Link — Ảnh / file upload không hiển thị

**Triệu chứng:** Ảnh avatar, file đính kèm không load được, hiện icon broken image.

**Nguyên nhân:** Chưa tạo symbolic link từ `public/storage` → `storage/app/public`.

**Cách sửa:**

```bash
php artisan storage:link
```

**Nếu lệnh trên báo lỗi** (link đã tồn tại hoặc lỗi permission):

```bash
# Xoá link cũ (nếu có)
# Windows CMD:
rmdir public\storage

# Windows PowerShell:
Remove-Item -Path public\storage -Force

# Sau đó tạo lại:
php artisan storage:link
```

> **Lưu ý trên Windows:** Nếu dùng CMD/PowerShell, hãy **chạy với quyền Administrator** để tạo symlink. Click phải vào terminal → "Run as Administrator".

---

### 2. Lỗi "No application encryption key has been specified."

**Nguyên nhân:** Chưa generate app key.

**Cách sửa:**
```bash
php artisan key:generate
```

---

### 3. Lỗi "SQLSTATE[HY000] [1049] Unknown database 'ckweb2'"

**Nguyên nhân:** Chưa tạo database trong MySQL.

**Cách sửa:**
1. Mở phpMyAdmin: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Tạo database mới tên `ckweb2` (collation: `utf8mb4_unicode_ci`).
3. Chạy migration hoặc import SQL (xem mục [Tạo database](#tạo-database--import-dữ-liệu)).

---

### 4. Lỗi thiếu PHP Extension

**Triệu chứng:** `composer install` báo lỗi thiếu extension như `ext-zip`, `ext-fileinfo`, `ext-gd`,...

**Cách sửa:**
1. Mở file `php.ini` của XAMPP:
   ```
   C:\xampp82\php\php.ini
   ```
2. Tìm dòng chứa extension bị thiếu (ví dụ: `;extension=zip`) và **xoá dấu `;`** ở đầu dòng:
   ```ini
   ;extension=zip      →  extension=zip
   ;extension=fileinfo →  extension=fileinfo
   ;extension=gd       →  extension=gd
   ```
3. Restart Apache trong XAMPP Control Panel.
4. Chạy lại `composer install`.

---

### 5. Lỗi "Vite manifest not found" hoặc giao diện không có CSS

**Triệu chứng:** Trang web load được nhưng không có styling, hoặc báo lỗi Vite manifest.

**Cách sửa:**

- **Khi đang phát triển (dev):** Đảm bảo đang chạy `npm run dev` trong một terminal riêng.
- **Khi chỉ muốn xem (không dev):** Build assets trước:
  ```bash
  npm run build
  ```

---

### 6. Lỗi "Port already in use" (khi dùng `php artisan serve`)

**Cách sửa:**
```bash
php artisan serve --port=8001
```
Sau đó truy cập: `http://localhost:8001`

---

### 7. Lỗi "SQLSTATE[42S01] Table already exists" khi chạy migrate

**Nguyên nhân:** Đã import file SQL trước rồi mới chạy migrate.

**Cách sửa:** Nếu đã import từ file `CKWeb2_dump.sql`, **không cần** chạy `php artisan migrate`. Nếu muốn reset lại:
```bash
php artisan migrate:fresh
```
> ⚠️ **Cảnh báo:** Lệnh trên sẽ **xoá toàn bộ dữ liệu** và tạo lại bảng từ đầu.

---

### 8. Lỗi Permission trên thư mục `storage/` và `bootstrap/cache/`

**Triệu chứng:** Lỗi 500, hoặc báo "Permission denied" khi ghi log, session, cache.

**Cách sửa (Windows):**
Thông thường trên Windows không gặp lỗi permission. Nếu có, hãy đảm bảo thư mục sau tồn tại và có quyền ghi:

```
storage/
├── app/
│   ├── public/
│   └── private/
├── framework/
│   ├── cache/
│   ├── sessions/
│   └── views/
└── logs/
```

Tạo các thư mục nếu thiếu:
```bash
mkdir storage\app\public
mkdir storage\app\private
mkdir storage\framework\cache
mkdir storage\framework\sessions
mkdir storage\framework\views
mkdir storage\logs
```

---

### 9. Lỗi "Class not found" hoặc autoload không hoạt động

**Cách sửa:**
```bash
composer dump-autoload
```

---

### 10. Lỗi cache cũ sau khi thay đổi config / route

**Cách sửa — Xoá toàn bộ cache:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

Hoặc gộp lại:
```bash
php artisan optimize:clear
```

---

## Thông tin bổ sung

### Công nghệ sử dụng

| Thành phần     | Công nghệ                     |
| -------------- | ------------------------------ |
| Backend        | Laravel 13, PHP 8.3+           |
| Frontend       | Tailwind CSS 4, Alpine.js      |
| Build tool     | Vite 8                         |
| Database       | MySQL / MariaDB (qua XAMPP)    |
| Real-time      | Pusher + Laravel Echo          |
| Offline        | Service Worker, IndexedDB      |
| Email          | SMTP (Gmail)                   |

### Cấu trúc thư mục chính

```
CKWeb2/
├── app/                    # Logic backend (Controllers, Models, Jobs,...)
├── bootstrap/              # Bootstrap framework
├── config/                 # Cấu hình ứng dụng
├── database/
│   ├── migrations/         # File migration tạo bảng
│   ├── seeders/            # Dữ liệu mẫu
│   └── CKWeb2_dump.sql     # File SQL backup (import phpMyAdmin)
├── public/                 # Entry point (index.php, assets đã build)
├── resources/
│   ├── css/                # Source CSS (Tailwind)
│   ├── js/                 # Source JavaScript
│   └── views/              # Blade templates
├── routes/                 # Định nghĩa routes
├── storage/                # File upload, cache, logs, sessions
├── .env.example            # Template cấu hình môi trường
├── composer.json           # PHP dependencies
├── package.json            # JS dependencies
└── vite.config.js          # Cấu hình Vite build
```

### Tài khoản mặc định (nếu import từ SQL dump)

Nếu bạn import file `CKWeb2_dump.sql`, dữ liệu có sẵn trong database. Bạn có thể đăng ký tài khoản mới hoặc sử dụng tài khoản có trong database (nếu có).

---

*Nếu gặp lỗi khác, hãy kiểm tra file log tại `storage/logs/laravel.log` để biết chi tiết.*
