# scripts/dev.ps1
# Starts all development servers concurrently:
#   1. Laravel (php artisan serve)
#   2. Vite (npm run dev)
#   3. OCR FastAPI service (uvicorn)

$RootDir = Split-Path -Parent $PSScriptRoot

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "  Starting SIPERBANG Development Servers..." -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan

# --- 1. Laravel server ---
Write-Host "[1/3] Starting Laravel server on http://127.0.0.1:8000" -ForegroundColor Green
$laravelJob = Start-Job -ScriptBlock {
    param($dir)
    Set-Location $dir
    php artisan serve
} -ArgumentList $RootDir

# --- 2. Vite dev server ---
Write-Host "[2/3] Starting Vite dev server..." -ForegroundColor Green
$viteJob = Start-Job -ScriptBlock {
    param($dir)
    Set-Location $dir
    npm run dev
} -ArgumentList $RootDir

# --- 3. OCR FastAPI service ---
Write-Host "[3/3] Starting OCR service on http://127.0.0.1:8001" -ForegroundColor Green
$ocrDir = Join-Path $RootDir "ocr-service"
$ocrJob = Start-Job -ScriptBlock {
    param($dir)
    Set-Location $dir
    if (Test-Path ".\.venv\Scripts\uvicorn.exe") {
        $env:OCR_SERVICE_TOKEN = "your-secret-token-here"
        .\.venv\Scripts\uvicorn.exe app.main:app --host 127.0.0.1 --port 8001 --reload
    } else {
        Write-Host "ERROR: .venv not found in $dir. Run 'pip install -r requirements.txt' inside ocr-service first." -ForegroundColor Red
    }
} -ArgumentList $ocrDir

Write-Host ""
Write-Host "All servers started. Press Ctrl+C to stop all." -ForegroundColor Yellow
Write-Host "  Laravel  -> http://127.0.0.1:8000" -ForegroundColor White
Write-Host "  Vite     -> http://127.0.0.1:5173" -ForegroundColor White
Write-Host "  OCR API  -> http://127.0.0.1:8001" -ForegroundColor White
Write-Host ""

# Stream output from all jobs until interrupted
try {
    while ($true) {
        foreach ($job in @($laravelJob, $viteJob, $ocrJob)) {
            $output = Receive-Job -Job $job -ErrorAction SilentlyContinue
            if ($output) {
                $output | ForEach-Object { Write-Host $_ }
            }
            # Restart job if it unexpectedly stopped
            if ($job.State -eq 'Failed' -or $job.State -eq 'Completed') {
                Write-Host "WARNING: A job stopped unexpectedly (State: $($job.State))" -ForegroundColor Red
            }
        }
        Start-Sleep -Milliseconds 500
    }
} finally {
    Write-Host ""
    Write-Host "Stopping all development servers..." -ForegroundColor Yellow
    Stop-Job -Job $laravelJob, $viteJob, $ocrJob -ErrorAction SilentlyContinue
    Remove-Job -Job $laravelJob, $viteJob, $ocrJob -Force -ErrorAction SilentlyContinue
    Write-Host "All servers stopped." -ForegroundColor Cyan
}
