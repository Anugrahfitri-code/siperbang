$ErrorActionPreference = "Stop"

$serviceRoot = Split-Path -Parent $PSScriptRoot
$python = Join-Path $serviceRoot ".venv\Scripts\python.exe"
$envFile = Join-Path $serviceRoot ".env"

Set-Location $serviceRoot

if (-not (Test-Path $python)) {
    Write-Error "Virtual environment OCR tidak ditemukan."
    exit 1
}

if (-not (Test-Path $envFile)) {
    Write-Error "File ocr-service\.env tidak ditemukan."
    exit 1
}

if (-not $env:PADDLE_PDX_MODEL_SOURCE) {
    $env:PADDLE_PDX_MODEL_SOURCE = "BOS"
}

while ($true) {
    Write-Host "Menjalankan OCR service pada http://127.0.0.1:8001" -ForegroundColor Cyan

    & $python `
        -m uvicorn `
        app.main:app `
        --app-dir $serviceRoot `
        --host 127.0.0.1 `
        --port 8001 `
        --workers 1 `
        --log-level info

    $exitCode = $LASTEXITCODE

    if ($exitCode -eq 75) {
        Write-Warning "OCR dihentikan karena hard timeout. Service dimulai ulang dalam 2 detik."
        Start-Sleep -Seconds 2
        continue
    }

    exit $exitCode
}
