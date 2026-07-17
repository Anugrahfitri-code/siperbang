$ErrorActionPreference = "Stop"

$RootDir = Split-Path -Parent $PSScriptRoot

Set-Location $RootDir


$HealthUrl = (
    "http://127.0.0.1:8001/health"
)

$MaximumWaitSeconds = 300
$WaitedSeconds = 0
$OcrReady = $false


Write-Host (
    "Menunggu model PaddleOCR siap..."
) -ForegroundColor Yellow


while ($WaitedSeconds -lt $MaximumWaitSeconds) {
    try {
        $response = Invoke-RestMethod `
            -Uri $HealthUrl `
            -Method Get `
            -TimeoutSec 3

        if ($response.status -eq "healthy" -and $response.engine_loaded -eq $true) {
            $OcrReady = $true

            break
        }
    }
    catch {
        # Status 503 normal selama model dimuat.
    }

    Start-Sleep -Seconds 2

    $WaitedSeconds += 2

    if (($WaitedSeconds % 10) -eq 0) {
        Write-Host (
            "Model OCR masih dipersiapkan... " +
            $WaitedSeconds +
            " detik"
        ) -ForegroundColor DarkYellow
    }
}


if (-not $OcrReady) {
    throw (
        "PaddleOCR tidak siap setelah " +
        $MaximumWaitSeconds +
        " detik. Periksa terminal OCR."
    )
}


Write-Host (
    "PaddleOCR sehat. Menjalankan queue OCR..."
) -ForegroundColor Green


# "database" adalah nama connection.
# "ocr,default" adalah nama queue.
& php artisan queue:work database `
    --queue=ocr,default `
    --tries=1 `
    --timeout=115 `
    --sleep=1 `
    --verbose


exit $LASTEXITCODE
