# Inventaris Lab — Backend Laravel + Frontend Vue + Docker Compose

Tema bebas (bukan tema tugas sebelumnya): **pencatatan alat praktikum laboratorium**.
Satu resource dengan CRUD lengkap: **equipments** (alat) — nama, kode unik,
kategori, jumlah, kondisi, lokasi, deskripsi — tersimpan di database MySQL.

## Arsitektur

```
Browser (http://localhost:8080)
   │  memanggil /api/... ke origin yang sama
   ▼
Frontend Vue (Nginx, service "frontend")
   │  proxy_pass http://backend:80  (nama service Docker, BUKAN IP laptop)
   ▼
Backend Laravel (FrankenPHP, service "backend", port host 8000)
   │  Sanctum (Bearer token) → Controller → Eloquent
   ▼
Database MySQL 8.0 (service "db", volume bernama uas_lab_db_data)
```

Alur data singkat: formulir Vue → `axios` dengan header
`Authorization: Bearer <token>` → Nginx frontend meneruskan `/api/*` ke
service `backend` → route `api.php` (middleware `auth:sanctum`, kecuali
login) → `EquipmentController` → validasi `FormRequest` (gagal = 422 JSON)
→ model `Equipment` → tabel `equipment` di MySQL. Balasan selalu JSON:
`{success, message, data}`; tambah = **201**, validasi gagal = **422**,
tanpa token = **401**.

## Menjalankan (satu perintah)

Prasyarat: Docker + Docker Compose v2 terpasang.

```bash
cp .env.example .env
# Isi DB_PASSWORD dan DB_ROOT_PASSWORD di .env dengan nilai Anda sendiri.
# APP_KEY boleh kosong: entrypoint backend membuatnya dan menyimpannya
# di volume backend_storage.

docker compose up -d --build
```

Buka:

- Frontend Vue: **http://localhost:8080**
- API langsung: **http://localhost:8000/api/equipments** (butuh token)
- Cek service: `docker compose ps`

Login demo (dari seeder, hanya untuk demo lokal):

- Email: `admin@lab.test`
- Password: `password123`

> Tidak ada endpoint register publik, sesuai ketentuan soal: seluruh
> endpoint selain login hanya bisa diakses dengan token.

## Menguji dengan Postman

Impor `postman/Inventaris-Lab.postman_collection.json` ke Postman.
Urutan: jalankan **Auth → Login** dulu — skrip Tests-nya menyimpan token
ke variabel collection `token`, dan semua request lain memakai Bearer token
itu. Koleksi juga berisi kasus negatif: tanpa token (401) dan validasi
gagal (422). Variabel `base_url` default `http://localhost:8000`.

Contoh curl (setelah login, ganti TOKEN):

```bash
# Login
curl -s -X POST http://localhost:8000/api/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"admin@lab.test","password":"password123"}'

# Tampil semua (butuh token)
curl -s http://localhost:8000/api/equipments -H "Authorization: Bearer TOKEN"

# Tambah (201)
curl -s -X POST http://localhost:8000/api/equipments \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -H "Authorization: Bearer TOKEN" \
  -d '{"name":"Laptop Praktikum","code":"LAB-101","category":"Komputer","quantity":10,"condition":"baik","location":"Lab A"}'

# Tanpa token -> 401
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8000/api/equipments
```

## API

| Metode | Endpoint | Auth | Keterangan |
|---|---|---|---|
| POST | `/api/login` | — | Mengembalikan `data.token` (Sanctum) |
| POST | `/api/logout` | Bearer | Menghapus token yang sedang dipakai |
| GET | `/api/user` | Bearer | Profil user login |
| GET | `/api/equipments` | Bearer | Tampil semua |
| POST | `/api/equipments` | Bearer | Tambah → 201 |
| GET | `/api/equipments/{id}` | Bearer | Tampil satu (404 bila tidak ada) |
| PUT | `/api/equipments/{id}` | Bearer | Ubah |
| DELETE | `/api/equipments/{id}` | Bearer | Hapus |

Validasi tambah/ubah: `name` wajib, `code` wajib & unik, `category` salah
satu dari Komputer/Jaringan/Audio Visual/Perkakas/Lainnya, `quantity`
integer ≥ 0, `condition` salah satu dari baik/rusak_ringan/rusak_berat/
perawatan, `location` wajib, `description` opsional.

## Persistensi data

Database disimpan di **named volume** `uas_lab_db_data`. Buktinya:

```bash
docker compose down
docker compose up -d
# Data yang dibuat sebelum down tetap ada — volume tidak ikut terhapus
# (hanya `docker compose down -v` yang menghapus volume, jangan dipakai
# bila data ingin dipertahankan).
```

## Nilai rahasia

- Kredensial database dibaca dari berkas `.env` di akar repository, yang
  **tidak di-commit** (lihat `.gitignore`). Contohnya ada di `.env.example`.
- **Tidak ada** password/key/token yang ditulis di dalam `Dockerfile`
  mana pun — Dockerfile hanya membaca environment yang dikirim compose.
- Masing-masing folder `backend/` dan `frontend/` punya `.dockerignore`
  sendiri, sehingga `node_modules`, `vendor`, `.env`, dan `.git` tidak
  masuk ke konteks build.

## Nilai tambahan: FrankenPHP

Backend **tidak** memakai `php artisan serve` maupun PHP-FPM + Nginx.
Server aplikasinya adalah **FrankenPHP** (`dunglas/frankenphp:1.13.3-php8.4`,
versi dipin, bukan `latest`) lewat `docker/Caddyfile`. Bukti bahwa API
benar-benar dilayani FrankenPHP:

```bash
docker compose logs backend   # baris start-up FrankenPHP/Caddy
curl -i http://localhost:8000/api/equipments
# Header balasan memuat: X-Served-By: FrankenPHP
```

## Struktur repository

```
docker-compose.yml            # 3 service: db, backend, frontend
.env.example                  # contoh kredensial (bukan nilai asli)
backend/                      # Laravel + Sanctum
  Dockerfile                  # FrankenPHP 1.13.3-php8.4
  docker/Caddyfile            # konfigurasi FrankenPHP/Caddy
  docker/entrypoint.sh        # tunggu DB, migrate, seed, jalankan FrankenPHP
  app/Http/Controllers/Api/   # AuthController, EquipmentController
  app/Http/Requests/          # validasi Store/Update Equipment
  app/Models/Equipment.php
  database/migrations/        # tabel equipment
  database/seeders/           # admin demo + 3 alat contoh
  routes/api.php
frontend/                     # Vue 3 + Vite
  Dockerfile                  # build Node 22.20.0-alpine, serve Nginx 1.27.4-alpine
  nginx.conf                  # proxy /api -> http://backend:80
  src/App.vue                 # login + daftar + form tambah/ubah/hapus
postman/                      # Postman Collection (siap impor)
laporan/                      # laporan PDF
```

## Pengembangan lokal tanpa Docker (opsional)

```bash
# Backend (butuh PHP 8.3+ dan Composer, MySQL lokal, .env backend diisi)
cd backend && composer install && php artisan migrate --seed && php artisan serve --port=8000
# Frontend
cd frontend && npm install && npm run dev   # http://localhost:5173, /api di-proxy ke :8000
```
