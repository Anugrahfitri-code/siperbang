$ErrorActionPreference = "Stop"

$RootDir = Split-Path -Parent $PSScriptRoot

Set-Location $RootDir


function Get-EnvValue {
    param(
        [Parameter(Mandatory = $true)]
        [string] $Path,

        [Parameter(Mandatory = $true)]
        [string] $Name
    )

    if (-not (Test-Path -LiteralPath $Path)) {
        return $null
    }

    $escapedName = [regex]::Escape($Name)

    $line = Get-Content -LiteralPath $Path |
        Where-Object {
            $_ -match ("^\s*" + $escapedName + "\s*=")
        } |
        Select-Object -Last 1

    if (-not $line) {
        return $null
    }

    $parts = $line -split "=", 2

    if ($parts.Count -lt 2) {
        return $null
    }

    $value = $parts[1].Trim()

    if (
        ($value.Length -ge 2) -and
        (
            ($value.StartsWith('"') -and $value.EndsWith('"')) -or
            ($value.StartsWith("'") -and $value.EndsWith("'"))
        )
    ) {
        $value = $value.Substring(
            1,
            $value.Length - 2
        )
    }

    return $value
}


$RootEnvPath = Join-Path $RootDir ".env"

$OcrServiceUrl = Get-EnvValue `
    -Path $RootEnvPath `
    -Name "OCR_SERVICE_URL"

if (
    [string]::IsNullOrWhiteSpace(
        $OcrServiceUrl
    )
) {
    throw (
        "OCR_SERVICE_URL tidak ditemukan " +
        "di file .env utama."
    )
}

$OcrServiceUrl = $OcrServiceUrl.TrimEnd("/")

try {
    $OcrUri = [Uri] $OcrServiceUrl
}
catch {
    throw (
        "OCR_SERVICE_URL bukan URL yang valid: " +
        $OcrServiceUrl
    )
}

if (
    $OcrUri.Scheme -notin @(
        "http",
        "https"
    ) -or
    [string]::IsNullOrWhiteSpace(
        $OcrUri.Host
    )
) {
    throw (
        "OCR_SERVICE_URL harus menggunakan " +
        "HTTP atau HTTPS."
    )
}


$HealthUrl = $OcrServiceUrl + "/health"

$MaximumWaitSeconds = 600
$WaitedSeconds = 0
$OcrReady = $false


$PhpCommand = (
    Get-Command `
        php `
        -ErrorAction Stop
).Source


Write-Host (
    "Menunggu service OCR siap di " +
    $HealthUrl +
    " ..."
) -ForegroundColor Yellow


while (
    $WaitedSeconds -lt
    $MaximumWaitSeconds
) {
    try {
        $response = Invoke-RestMethod `
            -Uri $HealthUrl `
            -Method Get `
            -TimeoutSec 5

        if (
            $response.status -eq "healthy" -and
            $response.engine_loaded -eq $true
        ) {
            $OcrReady = $true

            break
        }
    }
    catch {
        # HTTP 503 normal selama model dimuat.
        # Connection error juga dicoba kembali
        # sampai batas waktu habis.
    }

    Start-Sleep -Seconds 2

    $WaitedSeconds += 2

    if (
        ($WaitedSeconds % 10) -eq 0
    ) {
        Write-Host (
            "Service OCR masih dipersiapkan... " +
            $WaitedSeconds +
            " detik"
        ) -ForegroundColor DarkYellow
    }
}


if (-not $OcrReady) {
    throw (
        "Service OCR tidak siap setelah " +
        $MaximumWaitSeconds +
        " detik. Periksa URL, jaringan, " +
        "Docker, dan log OCR."
    )
}


Write-Host (
    "Service OCR sehat. " +
    "Menjalankan queue OCR..."
) -ForegroundColor Green


# Urutan timeout:
#
# FastAPI hard timeout : 95 detik
# Laravel HTTP client  : 110 detik
# Laravel job/worker   : 130 detik
# Database retry_after : 180 detik
& $PhpCommand artisan queue:work database `
    --queue=ocr,default `
    --tries=3 `
    --timeout=130 `
    --sleep=2 `
    --memory=512 `
    --verbose


exit $LASTEXITCODE
