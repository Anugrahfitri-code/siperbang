import os
from fastapi.testclient import TestClient
from app.main import app as fastapi_app
from app.config import settings
import app.ocr_engine

client = TestClient(fastapi_app)

import pytest
from unittest.mock import patch, PropertyMock

@pytest.fixture(autouse=True)
def mock_env_and_ready():
    settings.service_token = "test-token"
    with patch('app.main._get_model_state', return_value=("ready", None)), \
         patch('app.ocr_engine.OcrEngine.is_loaded', new_callable=PropertyMock, return_value=True):
        yield

def test_health():
    response = client.get("/health")
    assert response.status_code == 200
    assert response.json()["status"] == "healthy"

def test_missing_token():
    response = client.post("/internal/v1/receipt-ocr", files={"document": ("test.jpg", b"fake", "image/jpeg")})
    assert response.status_code == 401

def test_invalid_token():
    response = client.post("/internal/v1/receipt-ocr", 
                           headers={"X-Service-Token": "invalid-token"},
                           files={"document": ("test.jpg", b"fake", "image/jpeg")})
    assert response.status_code == 401

def test_unsupported_ext():
    response = client.post("/internal/v1/receipt-ocr", 
                           headers={"X-Service-Token": settings.service_token},
                           files={"document": ("test.txt", b"fake", "text/plain")})
    assert response.status_code == 415

from unittest.mock import patch

def test_successful_ocr():
    with patch('app.main.ocr_engine.process') as mock_process:
        mock_process.return_value = [[
            [[[1,1], [2,1], [2,2], [1,2]], ("TOKO CONTOH", 0.99)],
            [[[1,3], [2,3], [2,4], [1,4]], ("INV/001", 0.95)],
            [[[1,5], [2,5], [2,6], [1,6]], ("2026-07-13", 0.95)],
            [[[1,7], [2,7], [2,8], [1,8]], ("SUBTOTAL Rp 100.000", 0.95)],
            [[[1,9], [2,9], [2,10], [1,10]], ("PPN 11%", 0.95)],
            [[[1,11], [2,11], [2,12], [1,12]], ("11.000", 0.95)],
            [[[1,13], [2,13], [2,14], [1,14]], ("TOTAL Rp 111.000", 0.95)]
        ]]
        
        response = client.post("/internal/v1/receipt-ocr", 
                               headers={"X-Service-Token": settings.service_token},
                               files={"document": ("test.jpg", b"\xFF\xD8\xFF\xE0" + b"fake", "image/jpeg")})
        assert response.status_code == 200
        data = response.json()
        assert data["success"] == True
        doc = data["document"]
        assert doc["store_name"]["value"] == "TOKO CONTOH"

def test_executable_payload_disguised_as_image():
    response = client.post("/internal/v1/receipt-ocr",
                           headers={"X-Service-Token": settings.service_token},
                           files={"document": ("test.jpg", b"<?php system('id'); ?>", "image/jpeg")})
    assert response.status_code == 415
    assert "File content does not match" in response.json()["detail"]

import tempfile
real_mkstemp_func = tempfile.mkstemp

@patch("app.main.tempfile.mkstemp")
def test_php_extension_with_image_content(mock_mkstemp):
    # Store args called
    called_suffix = []

    def side_effect(*args, **kwargs):
        called_suffix.append(kwargs.get("suffix"))
        return real_mkstemp_func(*args, **kwargs)

    mock_mkstemp.side_effect = side_effect

    with patch('app.main.ocr_engine.process') as mock_process:
        mock_process.return_value = [[
            [[[1,1], [2,1], [2,2], [1,2]], ("TOKO CONTOH", 0.99)],
        ]]

        response = client.post("/internal/v1/receipt-ocr",
                               headers={"X-Service-Token": settings.service_token},
                               files={"document": ("test.php", b"\xFF\xD8\xFF\xE0" + b"fake", "image/jpeg")})

    # The API should ignore .php and just check bytes, deriving .jpg
    assert response.status_code == 200
    assert called_suffix[0] == ".jpg"

def test_temp_cleanup_after_failure():
    import os
    with patch('app.main.detect_mime_type', return_value="image/jpeg"), \
         patch('app.main.tempfile.mkstemp') as mock_mkstemp:

        # We need a real file to be created so we can check if it was removed
        fd, path = real_mkstemp_func(suffix=".jpg")
        mock_mkstemp.return_value = (fd, path)

        from app.ocr_engine import OcrEngineError

        # Force a failure after temp file creation (e.g. OCR engine fails)
        with patch('app.main.ocr_engine.process', side_effect=OcrEngineError("OCR Failed")):
            response = client.post("/internal/v1/receipt-ocr",
                                   headers={"X-Service-Token": settings.service_token},
                                   files={"document": ("test.jpg", b"\xFF\xD8\xFF\xE0" + b"fake", "image/jpeg")})

            assert response.status_code == 500

            # Check if file was removed
            assert not os.path.exists(path)

def test_temp_cleanup_after_success():
    import os
    with patch('app.main.detect_mime_type', return_value="image/jpeg"), \
         patch('app.main.tempfile.mkstemp') as mock_mkstemp:

        # We need a real file to be created so we can check if it was removed
        fd, path = real_mkstemp_func(suffix=".jpg")
        mock_mkstemp.return_value = (fd, path)

        with patch('app.main.ocr_engine.process') as mock_process:
            mock_process.return_value = [[
                [[[1,1], [2,1], [2,2], [1,2]], ("TOKO CONTOH", 0.99)],
            ]]

            response = client.post("/internal/v1/receipt-ocr",
                                   headers={"X-Service-Token": settings.service_token},
                                   files={"document": ("test.jpg", b"\xFF\xD8\xFF\xE0" + b"fake", "image/jpeg")})

            assert response.status_code == 200

            # Check if file was removed
            assert not os.path.exists(path)

@patch("app.main.tempfile.mkstemp")
def test_pdf_bytes_with_jpg_filename(mock_mkstemp):
    # PDF magic bytes: %PDF-1.4 (25 50 44 46 2D 31 2E 34)
    called_suffix = []

    def side_effect(*args, **kwargs):
        called_suffix.append(kwargs.get("suffix"))
        return real_mkstemp_func(*args, **kwargs)

    mock_mkstemp.side_effect = side_effect

    with patch('app.main.ocr_engine.process') as mock_process:
        mock_process.return_value = [[
            [[[1,1], [2,1], [2,2], [1,2]], ("TOKO CONTOH", 0.99)],
        ]]
        with patch('app.main.pdfium.PdfDocument') as mock_pdfium:
            mock_pdf_doc = mock_pdfium.return_value
            mock_pdf_doc.__len__.return_value = 1

            response = client.post("/internal/v1/receipt-ocr",
                                    headers={"X-Service-Token": settings.service_token},
                                    files={"document": ("test.jpg", b"%PDF-1.4\nfake", "image/jpeg")})

    assert response.status_code == 200
    assert called_suffix[0] == ".pdf"
