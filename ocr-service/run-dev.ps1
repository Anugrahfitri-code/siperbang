Set-Location $PSScriptRoot

if (-not (Test-Path ".\.venv\Scripts\python.exe")) {
    Write-Error "Virtual environment tidak ditemukan. Buat .venv dan instal requirements."
    exit 1
}

& ".\.venv\Scripts\python.exe" `
    -m uvicorn `
    app.main:app `
    --host 127.0.0.1 `
    --port 8001 `
    --workers 1 `
    --log-level info
