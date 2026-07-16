$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$healthUrl = "http://127.0.0.1:8001/health"

$maximumWaitSeconds = 600
$waitedSeconds = 0
$ocrReady = $false

Write-Host "Menunggu PaddleOCR siap..." -ForegroundColor Yellow

while ($waitedSeconds -lt $maximumWaitSeconds) {
    try {
        $response = Invoke-RestMethod -Uri $healthUrl -Method Get -TimeoutSec 3

        if ($response.status -eq "healthy" -and $response.engine_loaded -eq $true) {
            $ocrReady = $true
            break
        }
    }
    catch {
        # Status 503 diperbolehkan saat model masih dimuat.
    }

    Start-Sleep -Seconds 2
    $waitedSeconds += 2

    if (($waitedSeconds % 10) -eq 0) {
        Write-Host "Model OCR masih dipersiapkan... $waitedSeconds detik" -ForegroundColor DarkYellow
    }
}

if (-not $ocrReady) {
    throw "PaddleOCR tidak siap setelah $maximumWaitSeconds detik. Periksa terminal OCR."
}

Write-Host "PaddleOCR sudah sehat. Menjalankan queue worker..." -ForegroundColor Green

& php artisan queue:work `
    --queue=ocr,default `
    --tries=1 `
    --timeout=115 `
    --sleep=1 `
    -v

exit $LASTEXITCODE
