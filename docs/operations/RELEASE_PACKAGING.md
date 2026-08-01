# Pembuatan Paket Rilis Aman

Arsip source tidak boleh berisi `.env`, database runtime, dokumen kuitansi, log, cache, session, virtual environment, token, atau hasil debug.

## Membuat ZIP source

```bash
php scripts/package-release.php /tmp/siperbang-source.zip
```

Secara default, `public/build` tidak disertakan. Bangun frontend setelah ekstraksi:

```bash
npm ci
npm run typecheck
npm run lint
npm run build
npm run verify:build
```

Untuk pipeline CI yang sudah menghasilkan build dari commit yang sama:

```bash
php scripts/package-release.php /tmp/siperbang-release.zip --include-build
```

Script menolak berjalan bila `public/hot` ada. Opsi `--include-build` juga menolak bila `public/build/manifest.json` atau `build-meta.json` tidak tersedia, fingerprint source tidak tertanam di JavaScript, atau hash artefak build tidak cocok. Dengan demikian bundle lama tidak dapat ikut terkemas secara tidak sengaja.

## Data yang dikeluarkan

- `.env` dan variasinya, kecuali `.env.example`;
- `ocr-service/.env`;
- `database/database.sqlite`;
- `storage/app/private`, file branding runtime, logs, cache, session, dan compiled views;
- `vendor`, `node_modules`, virtualenv, pytest cache, Python bytecode;
- `public/hot`, `public/storage`, serta build bila opsi include tidak dipilih;
- folder arsip/prototipe dan output debug;
- `.git`, IDE metadata, ZIP lain, dan `opencode.json`.

## Sebelum membagikan arsip

1. Jalankan secret scan.
2. Uji integritas ZIP.
3. Pastikan `.env` tidak ada di daftar entry.
4. Pastikan tidak ada dokumen di `storage/app/private`.
5. Rotasi credential apabila arsip lama yang mengandung secret pernah keluar dari lingkungan tepercaya.
