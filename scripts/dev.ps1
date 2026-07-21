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
            $_ -match (
                "^\s*" +
                $escapedName +
                "\s*="
            )
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


$RootEnvPath = Join-Path `
    $RootDir `
    ".env"

$OcrEnvPath = Join-Path `
    $RootDir `
    "ocr-service\.env"

$ComposePath = Join-Path `
    $RootDir `
    "docker-compose.yml"


if (
    -not (
        Test-Path `
            -LiteralPath $RootEnvPath
    )
) {
    throw (
        "File .env utama tidak ditemukan."
    )
}


$OcrServiceUrl = Get-EnvValue `
    -Path $RootEnvPath `
    -Name "OCR_SERVICE_URL"

$RootToken = Get-EnvValue `
    -Path $RootEnvPath `
    -Name "OCR_SERVICE_TOKEN"


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

if (
    [string]::IsNullOrWhiteSpace(
        $RootToken
    )
) {
    throw (
        "OCR_SERVICE_TOKEN tidak ditemukan " +
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


$LocalOcrHosts = @(
    "127.0.0.1",
    "localhost",
    "::1"
)

$UsesLocalOcr = ($OcrUri.Host.ToLowerInvariant() -in $LocalOcrHosts)


$PhpCommand = (
    Get-Command `
        php `
        -ErrorAction Stop
).Source

$NpmCommand = (
    Get-Command `
        npm.cmd `
        -ErrorAction Stop
).Source

$PowerShellCommand = (
    Get-Command `
        powershell.exe `
        -ErrorAction Stop
).Source


if ($UsesLocalOcr) {
    if (
        -not (
            Test-Path `
                -LiteralPath $ComposePath
        )
    ) {
        throw (
            "docker-compose.yml tidak ditemukan " +
            "pada root proyek."
        )
    }


    $OcrToken = Get-EnvValue `
        -Path $OcrEnvPath `
        -Name "OCR_SERVICE_TOKEN"


    if (
        [string]::IsNullOrWhiteSpace(
            $OcrToken
        )
    ) {
        throw (
            "OCR_SERVICE_TOKEN tidak ditemukan " +
            "di ocr-service\.env."
        )
    }


    if ($RootToken -ne $OcrToken) {
        throw (
            "OCR_SERVICE_TOKEN pada .env utama " +
            "dan ocr-service\.env tidak sama."
        )
    }


    $DockerCommand = (
        Get-Command `
            docker `
            -ErrorAction Stop
    ).Source


    Write-Host (
        "Memastikan Docker Compose tersedia..."
    ) -ForegroundColor Yellow


    & $DockerCommand compose version

    if ($LASTEXITCODE -ne 0) {
        throw (
            "Docker Compose tidak tersedia atau " +
            "Docker Desktop belum aktif."
        )
    }


    Write-Host (
        "Menjalankan OCR Docker..."
    ) -ForegroundColor Yellow


    & $DockerCommand compose up `
        -d `
        --build `
        ocr

    if ($LASTEXITCODE -ne 0) {
        throw (
            "Container OCR gagal dijalankan."
        )
    }
}


Write-Host (
    "Membersihkan cache konfigurasi Laravel..."
) -ForegroundColor Yellow


& $PhpCommand artisan optimize:clear

if ($LASTEXITCODE -ne 0) {
    throw (
        "Laravel optimize:clear gagal."
    )
}


$Services = @()


function Start-TrackedProcess {
    param(
        [Parameter(Mandatory = $true)]
        [string] $Name,

        [Parameter(Mandatory = $true)]
        [string] $FilePath,

        [Parameter(Mandatory = $true)]
        [string[]] $Arguments,

        [Parameter(Mandatory = $true)]
        [string] $WorkingDirectory
    )

    $process = Start-Process `
        -FilePath $FilePath `
        -ArgumentList $Arguments `
        -WorkingDirectory $WorkingDirectory `
        -NoNewWindow `
        -PassThru

    $script:Services += [PSCustomObject]@{
        Name = $Name
        Process = $process
    }

    Write-Host (
        "[STARTED] " +
        $Name +
        " PID=" +
        $process.Id
    ) -ForegroundColor Green
}


$QueueScript = Join-Path `
    $RootDir `
    "scripts\run-queue.ps1"


try {
    Write-Host ""

    Write-Host (
        "=============================================="
    ) -ForegroundColor Cyan

    Write-Host (
        " Menjalankan SIPERBANG Development"
    ) -ForegroundColor Cyan

    Write-Host (
        "=============================================="
    ) -ForegroundColor Cyan


    # Queue membaca OCR_SERVICE_URL dari .env.
    # Queue akan menunggu endpoint /health
    # sampai model OCR benar-benar siap.
    Start-TrackedProcess `
        -Name "Laravel Queue" `
        -FilePath $PowerShellCommand `
        -Arguments @(
            "-NoProfile",
            "-ExecutionPolicy",
            "Bypass",
            "-File",
            "`"$QueueScript`""
        ) `
        -WorkingDirectory $RootDir


    Start-TrackedProcess `
        -Name "Laravel Server" `
        -FilePath $PhpCommand `
        -Arguments @(
            "artisan",
            "serve",
            "--host=127.0.0.1",
            "--port=8000"
        ) `
        -WorkingDirectory $RootDir


    Start-TrackedProcess `
        -Name "Vite" `
        -FilePath $NpmCommand `
        -Arguments @(
            "run",
            "dev"
        ) `
        -WorkingDirectory $RootDir


    $OcrMode = if ($UsesLocalOcr) {
        "Docker lokal"
    }
    else {
        "Server OCR jaringan"
    }


    Write-Host ""

    Write-Host (
        "Laravel : http://127.0.0.1:8000"
    ) -ForegroundColor White

    Write-Host (
        "OCR API : " +
        $OcrServiceUrl
    ) -ForegroundColor White

    Write-Host (
        "Health  : " +
        $OcrServiceUrl +
        "/health"
    ) -ForegroundColor White

    Write-Host (
        "Mode OCR: " +
        $OcrMode
    ) -ForegroundColor White


    Write-Host ""

    Write-Host (
        "Tunggu pesan bahwa OCR dan queue " +
        "sudah siap sebelum mengunggah dokumen."
    ) -ForegroundColor Yellow

    Write-Host (
        "Tekan Ctrl+C untuk menghentikan " +
        "Laravel, queue, dan Vite."
    ) -ForegroundColor Yellow


    if ($UsesLocalOcr) {
        Write-Host (
            "Container OCR tetap berjalan. " +
            "Hentikan dengan: docker compose stop ocr"
        ) -ForegroundColor DarkYellow
    }


    while ($true) {
        foreach ($service in $Services) {
            $service.Process.Refresh()

            if ($service.Process.HasExited) {
                throw (
                    $service.Name +
                    " berhenti dengan exit code " +
                    $service.Process.ExitCode +
                    "."
                )
            }
        }

        Start-Sleep -Seconds 1
    }
}
finally {
    Write-Host ""

    Write-Host (
        "Menghentikan Laravel, queue, dan Vite..."
    ) -ForegroundColor Yellow


    foreach ($service in $Services) {
        try {
            $service.Process.Refresh()

            if (
                -not $service.Process.HasExited
            ) {
                & taskkill.exe `
                    /PID $service.Process.Id `
                    /T `
                    /F `
                    2>$null |
                    Out-Null
            }
        }
        catch {
            # Proses mungkin sudah berhenti.
        }
    }


    Write-Host (
        "Service development telah dihentikan."
    ) -ForegroundColor Cyan
}
