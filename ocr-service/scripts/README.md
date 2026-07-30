# Script OCR Service

Jalankan script dari PowerShell. Script menentukan root `ocr-service` secara otomatis.

```powershell
.\scripts\run-dev.ps1
.\scripts\run-server.ps1
.\scripts\run-tests.ps1
```

- `run-dev.ps1`: menjalankan Uvicorn sekali untuk pengembangan.
- `run-server.ps1`: menjalankan Uvicorn dan memulai ulang service setelah hard timeout.
- `run-tests.ps1`: menjalankan pemeriksaan integrasi OCR memakai fixture lokal.
