$ErrorActionPreference = "Stop"

$serviceRoot = Split-Path -Parent $PSScriptRoot
$fixtureDirectory = Join-Path $serviceRoot "tests\fixtures"
$outputDirectory = Join-Path $serviceRoot "debug-output"
$python = Join-Path $serviceRoot ".venv\Scripts\python.exe"

Set-Location $serviceRoot

if (-not (Test-Path $python)) {
    Write-Error "Virtual environment OCR tidak ditemukan."
    exit 1
}

$syntheticReceipt = Join-Path $fixtureDirectory "synthetic-smoke-receipt.png"

foreach ($fixture in @($syntheticReceipt)) {
    if (-not (Test-Path $fixture)) {
        Write-Error "Fixture OCR tidak ditemukan: $fixture"
        exit 1
    }
}

New-Item -ItemType Directory -Force -Path $outputDirectory | Out-Null

if (-not $env:OCR_SERVICE_TOKEN) {
    $env:OCR_SERVICE_TOKEN = "your-secret-token-here"
}

Write-Host "--- Syntax Check ---"
& $python -m compileall app
& $python -c "from app.main import app; print(app.title)"

Write-Host "`n--- Starting FastAPI ---"
$fastapiProcess = Start-Process `
    -NoNewWindow `
    -PassThru `
    -FilePath $python `
    -ArgumentList "-m", "uvicorn", "app.main:app", "--app-dir", $serviceRoot, "--host", "127.0.0.1", "--port", "8001"

Start-Sleep -Seconds 15

try {
    Write-Host "`n--- Health Check ---"
    curl.exe http://127.0.0.1:8001/health

    Write-Host "`n`n--- Test Synthetic Receipt ---"
    $syntheticOutput = Join-Path $outputDirectory "fastapi-synthetic.json"
    curl.exe -X POST `
        -H "X-Service-Token: $env:OCR_SERVICE_TOKEN" `
        -F "document=@$syntheticReceipt;type=image/png" `
        http://127.0.0.1:8001/internal/v1/receipt-ocr `
        --output $syntheticOutput
    Get-Content $syntheticOutput -Encoding utf8 | Select-Object -First 20

    Write-Host "`n`n--- Fake File Rejection ---"
    $fakeFile = Join-Path $outputDirectory "fake.pdf"
    Set-Content $fakeFile "ini bukan file pdf"
    curl.exe -i -X POST `
        -H "X-Service-Token: $env:OCR_SERVICE_TOKEN" `
        -F "document=@$fakeFile;type=application/pdf" `
        http://127.0.0.1:8001/internal/v1/receipt-ocr

    Write-Host "`n`n--- Invalid Token Test ---"
    curl.exe -i -X POST `
        -H "X-Service-Token: token-salah" `
        -F "document=@$syntheticReceipt;type=image/png" `
        http://127.0.0.1:8001/internal/v1/receipt-ocr
} finally {
    Write-Host "`nStopping FastAPI..."
    Stop-Process -Id $fastapiProcess.Id -Force
}
