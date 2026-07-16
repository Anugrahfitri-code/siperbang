$ErrorActionPreference = "Stop"

Write-Host "--- Step 6: Syntax Check ---"
.\.venv\Scripts\Activate.ps1
python -m compileall app
python -c "from app.main import app; print(app.title)"

Write-Host "`n--- Step 7: Starting FastAPI ---"
$env:OCR_SERVICE_TOKEN="your-secret-token-here"
$fastapiProcess = Start-Process -NoNewWindow -PassThru -FilePath "uvicorn" -ArgumentList "app.main:app", "--host", "127.0.0.1", "--port", "8001"
Start-Sleep -Seconds 15

try {
    Write-Host "`n--- Step 8: Health Check ---"
    curl.exe http://127.0.0.1:8001/health

    Write-Host "`n`n--- Step 9: Test New Agung ---"
    curl.exe -X POST -H "X-Service-Token: $env:OCR_SERVICE_TOKEN" -F "document=@D:\Project\siperbang\ocr-test\260212 New Agung 80.000.pdf;type=application/pdf" http://127.0.0.1:8001/internal/v1/receipt-ocr --output "debug-output\fastapi-new-agung.json"
    Get-Content "debug-output\fastapi-new-agung.json" -Encoding utf8

    Write-Host "`n`n--- Step 10: Test Nirmana ---"
    curl.exe -X POST -H "X-Service-Token: $env:OCR_SERVICE_TOKEN" -F "document=@D:\Project\siperbang\ocr-test\260617 Nirmana Aqsha 3.309.798.pdf;type=application/pdf" http://127.0.0.1:8001/internal/v1/receipt-ocr --output "debug-output\fastapi-nirmana.json"
    Get-Content "debug-output\fastapi-nirmana.json" -Encoding utf8 | Select-Object -First 20

    Write-Host "`n`n--- Step 11: Fake File Rejection ---"
    Set-Content "debug-output\fake.pdf" "ini bukan file pdf"
    curl.exe -i -X POST -H "X-Service-Token: $env:OCR_SERVICE_TOKEN" -F "document=@debug-output\fake.pdf;type=application/pdf" http://127.0.0.1:8001/internal/v1/receipt-ocr

    Write-Host "`n`n--- Step 12: Invalid Token Test ---"
    curl.exe -i -X POST -H "X-Service-Token: token-salah" -F "document=@D:\Project\siperbang\ocr-test\260212 New Agung 80.000.pdf;type=application/pdf" http://127.0.0.1:8001/internal/v1/receipt-ocr

} finally {
    Write-Host "`nStopping FastAPI..."
    Stop-Process -Id $fastapiProcess.Id -Force
}
