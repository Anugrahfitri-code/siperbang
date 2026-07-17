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

    $escapedName = [regex]::Escape(
        $Name
    )

    $line = Get-Content `
        -LiteralPath $Path |
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

$RootToken = Get-EnvValue `
    -Path $RootEnvPath `
    -Name "OCR_SERVICE_TOKEN"

$OcrToken = Get-EnvValue `
    -Path $OcrEnvPath `
    -Name "OCR_SERVICE_TOKEN"


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

if (
    [string]::IsNullOrWhiteSpace(
        $OcrToken
    )
) {
    throw (
        "OCR_SERVICE_TOKEN tidak ditemukan " +
        "di file ocr-service\.env."
    )
}

if ($RootToken -ne $OcrToken) {
    throw (
        "OCR_SERVICE_TOKEN pada .env utama " +
        "dan ocr-service\.env tidak sama."
    )
}


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


$OcrScript = Join-Path `
    $RootDir `
    "ocr-service\run-server.ps1"

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


    # OCR harus menggunakan run-server.ps1.
    # Jangan menggunakan uvicorn --reload.
    Start-TrackedProcess `
        -Name "OCR Service" `
        -FilePath $PowerShellCommand `
        -Arguments @(
            "-NoProfile",
            "-ExecutionPolicy",
            "Bypass",
            "-File",
            "`"$OcrScript`""
        ) `
        -WorkingDirectory (
            Join-Path $RootDir "ocr-service"
        )


    # Script queue akan menunggu health OCR
    # sampai model benar-benar siap.
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


    Write-Host ""
    Write-Host (
        "Laravel : http://127.0.0.1:8000"
    ) -ForegroundColor White

    Write-Host (
        "OCR API : http://127.0.0.1:8001"
    ) -ForegroundColor White

    Write-Host (
        "Health  : http://127.0.0.1:8001/health"
    ) -ForegroundColor White

    Write-Host ""
    Write-Host (
        "Tunggu pesan bahwa PaddleOCR dan queue " +
        "sudah siap sebelum mengunggah dokumen."
    ) -ForegroundColor Yellow

    Write-Host (
        "Tekan Ctrl+C untuk menghentikan semuanya."
    ) -ForegroundColor Yellow


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
        "Menghentikan seluruh service SIPERBANG..."
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
        "Seluruh service telah dihentikan."
    ) -ForegroundColor Cyan
}
