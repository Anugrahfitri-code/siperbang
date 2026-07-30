$ErrorActionPreference = "Stop"

$serviceRoot = Split-Path -Parent $PSScriptRoot
$python = Join-Path $serviceRoot ".venv\Scripts\python.exe"

Set-Location $serviceRoot

if (-not (Test-Path $python)) {
    Write-Error "Virtual environment tidak ditemukan. Buat .venv dan instal requirements."
    exit 1
}

& $python `
    -m uvicorn `
    app.main:app `
    --app-dir $serviceRoot `
    --host 127.0.0.1 `
    --port 8001 `
    --workers 1 `
    --log-level info

exit $LASTEXITCODE
