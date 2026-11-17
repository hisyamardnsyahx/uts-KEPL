#!/usr/bin/env bash
# Tugas 7 — skrip uji di laptop (jalankan dari akar repo ini).
# Menghasilkan bukti yang diminta laporan P11: /targets semua UP,
# dashboard Grafana, dan alarm FIRING setelah satu layanan dimatikan.
#   chmod +x tugas7-uji.sh
#   ./tugas7-uji.sh
# Prasyarat: .env sudah diisi termasuk GRAFANA_PASS (bukan admin),
# Docker jalan. TIDAK ada `down -v` di sini.
set -u
cd "$(dirname "$0")"

echo "== 1. Nyalakan semua layanan (build ulang backend) =="
docker compose up -d --build
echo "Tunggu 60 detik sampai semua layanan dan scrape pertama jalan..."
sleep 60
echo
echo "== 2. docker compose ps (semua layanan harus jalan) =="
docker compose ps
echo
echo "== 3. /metrics aplikasi (harus ada http_requests_total) =="
curl -s http://localhost:8000/metrics | head -20
echo
echo "== 4. Buat lalu lintas dulu: login + tampilkan daftar alat 5x =="
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"admin@lab.test","password":"password123"}' \
  | python3 -c "import sys, json; print(json.load(sys.stdin)['data']['token'])")
for i in 1 2 3 4 5; do
  curl -s -o /dev/null http://localhost:8000/api/equipments -H "Authorization: Bearer $TOKEN"
done
echo "Lalu lintas dibuat. Tunggu 30 detik untuk scrape berikutnya..."
sleep 30
curl -s http://localhost:8000/metrics | grep http_requests_total
echo
echo "== 5. /targets Prometheus (buka di browser, screenshot: semua UP) =="
echo "http://localhost:9090/targets"
curl -s 'http://localhost:9090/api/v1/targets?state=active' | python3 -c "import sys, json; d=json.load(sys.stdin); [print(t['labels'].get('job'), t['health'], t['scrapeUrl']) for t in d['data']['activeTargets']]"
echo
echo "== 6. Dashboard Grafana (buka di browser, screenshot lengkap) =="
echo "http://localhost:3000 — login admin + password GRAFANA_PASS dari .env"
echo "Dashboard: Inventaris Lab - Apakah aplikasiku sehat?"
echo
echo "== 7. Alarm sebelum dimatikan (harusnya Inactive/Pending, bukan Firing) =="
curl -s 'http://localhost:9090/api/v1/alerts' | python3 -c "import sys, json; d=json.load(sys.stdin); [print(a['labels'].get('alertname'), a['state']) for a in d['data']['alerts']]"
echo
echo "== 8. Matikan backend dengan sengaja =="
docker compose stop backend
echo "Tunggu 3 menit (for: 2m pada LayananMati) lalu cek lagi..."
sleep 180
echo
echo "== 9. Alarm saat FIRING (screenshot halaman Alerts Prometheus + Grafana) =="
curl -s 'http://localhost:9090/api/v1/alerts' | python3 -c "import sys, json; d=json.load(sys.stdin); [print(a['labels'].get('alertname'), a['state'], a.get('activeAt')) for a in d['data']['alerts']]"
docker compose ps
echo
echo "== 10. Nyalakan lagi backend, tunggu pulih =="
docker compose start backend
sleep 45
docker compose ps
curl -s http://localhost:8000/health
echo
echo "Selesai. Simpan screenshot: /targets, dashboard lengkap, dan Alerts saat FIRING."
