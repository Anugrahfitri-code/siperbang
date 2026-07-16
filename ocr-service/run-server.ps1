$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot

$python = Join-Path `
    $PSScriptRoot `
    ".venv\Scripts\python.exe"

if (-not (Test-Path $python)) {
    Write-Error (
        "Virtual environment OCR tidak ditemukan.`n" +
        "Jalankan:`n" +
        "cd ocr-service`n" +
        "py -3.12 -m venv .venv`n" +
        ".\.venv\Scripts\python.exe -m pip install -r requirements.txt"
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

Write-Host (
    "Menjalankan FastAPI pada " +
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

exit $LASTEXITCODE
