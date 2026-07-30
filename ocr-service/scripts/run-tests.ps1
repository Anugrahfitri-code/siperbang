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

$newAgung = Join-Path $fixtureDirectory "receipt-new-agung.pdf"
$nirmana = Join-Path $fixtureDirectory "receipt-nirmana-aqsha.pdf"

foreach ($fixture in @($newAgung, $nirmana)) {
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

    Write-Host "`n`n--- Test New Agung ---"
    $newAgungOutput = Join-Path $outputDirectory "fastapi-new-agung.json"
    curl.exe -X POST `
        -H "X-Service-Token: $env:OCR_SERVICE_TOKEN" `
        -F "document=@$newAgung;type=application/pdf" `
        http://127.0.0.1:8001/internal/v1/receipt-ocr `
        --output $newAgungOutput
    Get-Content $newAgungOutput -Encoding utf8

    Write-Host "`n`n--- Test Nirmana ---"
    $nirmanaOutput = Join-Path $outputDirectory "fastapi-nirmana.json"
    curl.exe -X POST `
        -H "X-Service-Token: $env:OCR_SERVICE_TOKEN" `
        -F "document=@$nirmana;type=application/pdf" `
        http://127.0.0.1:8001/internal/v1/receipt-ocr `
        --output $nirmanaOutput
    Get-Content $nirmanaOutput -Encoding utf8 | Select-Object -First 20

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
        -F "document=@$newAgung;type=application/pdf" `
        http://127.0.0.1:8001/internal/v1/receipt-ocr
} finally {
    Write-Host "`nStopping FastAPI..."
    Stop-Process -Id $fastapiProcess.Id -Force
}
