$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot

$python = Join-Path `
    $PSScriptRoot `
    ".venv\Scripts\python.exe"

if (-not (Test-Path $python)) {
    Write-Error (
        "Virtual environment OCR tidak ditemukan."
    )

    exit 1
}

$envFile = Join-Path `
    $PSScriptRoot `
    ".env"

if (-not (Test-Path $envFile)) {
    Write-Error (
        "File ocr-service\.env tidak ditemukan."
    )

    exit 1
}

if (-not $env:PADDLE_PDX_MODEL_SOURCE) {
    $env:PADDLE_PDX_MODEL_SOURCE = "BOS"
}

while ($true) {
    Write-Host (
        "Menjalankan OCR service pada " +
        "http://127.0.0.1:8001"
    ) -ForegroundColor Cyan

    & $python `
        -m uvicorn `
        app.main:app `
        --app-dir $PSScriptRoot `
        --host 127.0.0.1 `
        --port 8001 `
        --workers 1 `
        --log-level info

    $exitCode = $LASTEXITCODE

    if ($exitCode -eq 75) {
        Write-Warning (
            "OCR dihentikan karena hard timeout. " +
            "Service dimulai ulang dalam 2 detik."
        )

        Start-Sleep -Seconds 2
        continue
    }

    exit $exitCode
}
