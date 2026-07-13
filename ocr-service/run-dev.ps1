# Run this script to start the development server
$env:OCR_SERVICE_TOKEN="your-secret-token-here"
.venv\Scripts\uvicorn.exe app.main:app --host 127.0.0.1 --port 8001 --reload
