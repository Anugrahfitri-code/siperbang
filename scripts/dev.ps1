$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

function Assert-PortFree {
    param(
        [Parameter(Mandatory = $true)]
        [int] $Port,
        [Parameter(Mandatory = $true)]
        [string] $ServiceName
    )

    $listeners = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue

    if (-not $listeners) { return }

    $processIds = $listeners | Select-Object -ExpandProperty OwningProcess -Unique
    throw "$ServiceName tidak dapat dijalankan karena port $Port sedang digunakan oleh PID: $($processIds -join ', '). Tutup terminal atau proses lama, kemudian jalankan composer dev kembali."
}

Assert-PortFree -Port 8000 -ServiceName "Laravel"
Assert-PortFree -Port 8001 -ServiceName "FastAPI OCR"
Assert-PortFree -Port 5173 -ServiceName "Vite"

Write-Host "Membersihkan cache konfigurasi Laravel..." -ForegroundColor Cyan
& php artisan optimize:clear

if ($LASTEXITCODE -ne 0) {
    throw "Gagal membersihkan cache Laravel."
}

Write-Host "Menghentikan queue worker lama..." -ForegroundColor Cyan
& php artisan queue:restart

if ($LASTEXITCODE -ne 0) {
    throw "Gagal mengirim perintah restart queue."
}

$ocrScript = Join-Path $root "ocr-service\run-server.ps1"
$queueScript = Join-Path $root "scripts\run-queue.ps1"

if (-not (Test-Path $ocrScript)) {
    throw "File OCR server tidak ditemukan: $ocrScript"
}
if (-not (Test-Path $queueScript)) {
    throw "File queue runner tidak ditemukan: $queueScript"
}

$ocrCommand = "powershell -NoProfile -ExecutionPolicy Bypass -File `"$ocrScript`""
$serverCommand = "php artisan serve --host=127.0.0.1 --port=8000"
$queueCommand = "powershell -NoProfile -ExecutionPolicy Bypass -File `"$queueScript`""
$viteCommand = "npm run dev"

Write-Host "Menjalankan OCR, Laravel, queue, dan Vite dalam satu terminal..." -ForegroundColor Green

& npx concurrently -c "#d8b4fe,#93c5fd,#fdba74,#86efac" --names=ocr,server,queue,vite --kill-others $ocrCommand $serverCommand $queueCommand $viteCommand

exit $LASTEXITCODE
