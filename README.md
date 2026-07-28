# ⚡ Posive POS - Laravel REST API Backend

Backend REST API untuk **Posive POS (Modern F&B Point of Sale Application)** yang dibangun menggunakan **Laravel 11**, **PostgreSQL (Neon Cloud)**, **Laravel Sanctum**, dan **Pest PHP**.

---

## 🚀 Fitur Backend API

- **Authentication System**: Login & logout dengan Sanctum Bearer Token.
- **Product Management API**: CRUD produk, toggle `is_active`, upload gambar Base64 / URL, kustomisasi variasi kustom.
- **Atomic Checkout & Inventory System**:
  - Pessimistic locking (`Product::lockForUpdate()`) untuk mencegah race condition stok.
  - Perhitungan subtotal, PB1 10%, dan service charge 5% secara otomatis server-side.
  - Snapshot harga produk (`unit_price`) pada `order_items`.
- **Table Management API**: Status meja real-time, order terikat meja, dan transfer meja.
- **Shift Management API**: Rekonsiliasi kasir (*Open Shift / Close Shift*).
- **Automated Testing Suite**: 14 Feature Tests (50 assertions) dengan Pest PHP.

---

## 🛠️ Instalasi Backend

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
php artisan serve --port=8000
```

## 🧪 Testing

```bash
php artisan test
```
