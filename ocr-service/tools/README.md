# Tools OCR Service

Folder ini berisi alat diagnostik manual. File di sini tidak dijalankan saat runtime.

Contoh dari root proyek:

```bash
python ocr-service/tools/diagnostics/check_real_ocr.py ocr-service/tests/fixtures/receipt-new-agung.pdf
python ocr-service/tools/diagnostics/check_parser_result.py ocr-service/debug-output/raw-ocr.json
```
