# ROADMAP.md — Rencana Pengembangan SIPERBANG

Dokumen ini dibuat berdasarkan hasil audit enterprise (Juli 2026).
Diperbarui setiap kali ada keputusan arsitektur besar.

---

## Status Saat Ini (Baseline Juli 2026)

- Monolith Laravel 13 + React 19 SPA
- OCR service Python terpisah (FastAPI + PaddleOCR)
- Database: SQLite (dev), belum ada konfigurasi production resmi
- Test coverage: ~5% (hanya 2 feature test + 1 unit test placeholder)
- Tidak ada CI/CD pipeline
- Tidak ada monitoring/alerting

---

## Tahun 1 (2026–2027): Stabilisasi & Fondasi

Fokus: memperkuat yang sudah ada sebelum menambah fitur baru.

### Q3 2026 (Prioritas Tinggi)
- [ ] **Rate limiting** pada endpoint login dan upload (cegah brute force)
- [ ] **Konsolidasi dua model stok** — `StockItem` vs `Barang` harus jadi satu
- [ ] **Ganti ILIKE ke LIKE** agar kompatibel SQLite/MySQL di development
- [ ] **Pindah ke UUID** untuk nama file upload (ganti `time()` prefix)
- [ ] **Paksa ganti password** saat user baru pertama kali login
- [ ] **Hapus/arsipkan dead code** setelah konfirmasi: `PerbaikiDataController`, `VerifikasiKodePersediaanController`, `StokPengadaanController`, `BarangController`
- [ ] **Setup CI/CD** minimal: GitHub Actions untuk run test otomatis setiap push

### Q4 2026
- [ ] **Tambah test coverage** ke minimal 40% — fokus ke service layer (ExcelImport, Finalization, OCR Job)
- [ ] **Konsolidasikan audit log** — hapus `history_logs`, pakai `audit_logs` saja, cover semua aksi sensitif
- [ ] **Tambah Form Request class** untuk semua endpoint yang masih inline validation
- [ ] **Konfigurasi production** — pilih database engine (MySQL vs PostgreSQL), buat dokumentasi infra
- [ ] **Setup monitoring dasar** — error tracking (Sentry atau Laravel Telescope)

---

## Tahun 3 (2027–2029): Pertumbuhan & Skalabilitas

Fokus: siapkan sistem untuk lebih banyak user dan fitur baru.

### Arsitektur
- [ ] **Migrasi database ke PostgreSQL** — full-text search native, JSONB untuk OCR results lebih efisien
- [ ] **Redis untuk cache dan queue** — gantikan database queue driver yang lambat di volume tinggi
- [ ] **API versioning** — pisahkan `/api/v1/` agar perubahan backward compatible
- [ ] **Pisahkan OCR service** ke server tersendiri — resource PaddleOCR berat, jangan satu server dengan Laravel

### Fitur
- [ ] **Notifikasi real-time** — WebSocket (Laravel Reverb) atau polling untuk status BON dan OCR
- [ ] **Dashboard analytics** — grafik tren stok, BON per periode, tingkat pemenuhan
- [ ] **Mobile-responsive improvements** — saat ini UI desktop-first
- [ ] **Bulk operations** — verifikasi/tolak banyak item BON sekaligus
- [ ] **Export Excel yang sesungguhnya** — saat ini export hanya CSV, ubah ke `.xlsx` menggunakan PhpSpreadsheet

### Keamanan
- [ ] **2FA (Two-Factor Authentication)** untuk role Superadmin dan Petugas Persediaan
- [ ] **Session management** — tampilkan sesi aktif, kemampuan revoke sesi dari device lain
- [ ] **Secret manager** — pindah dari `.env` ke Vault atau AWS Secrets Manager untuk production

---

## Tahun 5 (2029–2031): Maturity & Enterprise Grade

### Observability
- [ ] **Structured logging** dengan format JSON — kirim ke ELK Stack atau Datadog
- [ ] **Distributed tracing** — trace request dari browser → Laravel → OCR service
- [ ] **SLA monitoring** — alert jika response time > threshold, queue backlog > N

### Skalabilitas
- [ ] **Horizontal scaling** — pastikan semua state ada di database/Redis, bukan di file lokal
- [ ] **CDN untuk assets** — static files (JS, CSS, gambar) via CDN
- [ ] **Database read replica** — pisahkan read/write untuk query berat (laporan, export)
- [ ] **Partisi tabel besar** — `stok_histories`, `audit_logs`, `receipt_documents` bisa tumbuh besar

### Developer Experience
- [ ] **OpenAPI/Swagger spec** — generate dari kode, bukan tulis manual
- [ ] **Postman collection** — untuk testing API manual
- [ ] **Staging environment otomatis** — setiap PR buat preview environment

---

## Tahun 10 (2031–2036): Longevity

### Tech Stack Refresh
- Laravel akan tetap relevan selama 10 tahun (track record kuat). Prioritaskan upgrade versi major secara berkala.
- React 19+ — ikuti upgrade major, jangan skip lebih dari 2 major version.
- PaddleOCR: evaluasi ulang setiap 2 tahun — jika ada model OCR yang lebih baik/efisien, pertimbangkan migrasi.
- PHP 8.x → 9.x saat rilis — biasanya upgrade relatif smooth.

### Strategi Migrasi Dependency
- Gunakan `composer outdated` dan `npm outdated` secara berkala (minimal tiap 6 bulan)
- Pin versi exact di `composer.json` dan `package.json` untuk reproducible builds
- Ikuti security advisory PHP, Laravel, dan PaddleOCR

### Pertimbangan Arsitektur Jangka Panjang
- Jika skala user > 10.000 aktif bersamaan: pertimbangkan pisah OCR menjadi microservice dengan antrian message broker (RabbitMQ/Kafka)
- Jika fitur tumbuh > 20 modul: pertimbangkan Laravel Package-based modular monolith (tiap modul jadi Laravel package)
- Jika butuh multi-tenancy: tambah `organization_id` ke semua tabel utama — ini harus direncanakan sebelum data besar

---

## Keputusan yang Belum Diambil (Open Questions)

| Pertanyaan | Deadline Keputusan |
|---|---|
| Database production: MySQL atau PostgreSQL? | Q3 2026 |
| OCR service: satu server dengan Laravel atau terpisah? | Q4 2026 |
| Hosting: on-premise atau cloud? | Q3 2026 |
| Perlu multi-organisasi/multi-tenant di masa depan? | Q1 2027 |
