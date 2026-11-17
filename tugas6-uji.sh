#!/usr/bin/env bash
# Tugas 6 — skrip uji di laptop (jalankan dari akar repo ini).
# Tujuannya: menghasilkan bukti yang diminta laporan P10 —
# potongan log terstruktur, `docker compose ps` saat sehat dan saat
# sakit, dan isi /health pada kedua keadaan — tanpa harus menghafal
# urutannya. Jalankan sekali, salin output terminalnya, dan ambil
# screenshot `docker compose ps` di langkah sehat dan sakit.
#
#   chmod +x tugas6-uji.sh
#   ./tugas6-uji.sh
#
# Prasyarat: .env sudah diisi (cp .env.example .env), Docker jalan.
# Skrip ini TIDAK menghapus volume (tidak ada `down -v` di sini).

set -u
cd "$(dirname "$0")"

echo "== 1. Nyalakan semua layanan (build ulang backend) =="
docker compose up -d --build
echo
echo "== 2. docker compose ps — keadaan SEHAT (tunggu sampai backend healthy) =="
sleep 5
docker compose ps
echo
echo "== 3. /health — keadaan SEHAT (harusnya 200) =="
curl -s -w '\nHTTP %{http_code}\n' http://localhost:8000/health
echo
echo "== 4. Login, lalu simpan satu alat (log info equipment.created) =="
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"admin@lab.test","password":"password123"}' \
  | python3 -c "import sys, json; print(json.load(sys.stdin)['data']['token'])")
echo "Token didapat (tidak ditampilkan utuh di sini)."
curl -s -X POST http://localhost:8000/api/equipments \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"name":"Laptop Uji Tugas 6","code":"LAB-T6-01","category":"Komputer","quantity":1,"condition":"baik","location":"Lab A"}'
echo
echo
echo "== 5. Kirim data yang sengaja tidak valid (log warning validation_failed, 422) =="
curl -s -X POST http://localhost:8000/api/equipments \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"name":"","code":"LAB-T6-02","category":"BukanKategori","quantity":-1,"condition":"baik","location":"Lab A"}'
echo
echo
echo "== 6. Log terstruktur dari backend (stdout, JSON satu baris) =="
docker compose logs --tail=30 backend
echo
echo "== 7. Matikan db dengan sengaja =="
docker compose stop db
sleep 12
echo
echo "== 8. docker compose ps — keadaan SAKIT (backend harusnya unhealthy) =="
docker compose ps
echo
echo "== 9. /health — keadaan SAKIT (harusnya 503, menyebut database) =="
curl -s -w '\nHTTP %{http_code}\n' http://localhost:8000/health || true
echo
echo "== 10. Log saat sakit (harus ada health.database_error / equipment.database_error) =="
docker compose logs --tail=30 backend
echo
echo "== 11. Nyalakan lagi db, tunggu pulih =="
docker compose start db
sleep 15
docker compose ps
curl -s -w '\nHTTP %{http_code}\n' http://localhost:8000/health
echo
echo "Selesai. Simpan output di atas + screenshot ps langkah 2 dan 8 untuk laporan P10."
