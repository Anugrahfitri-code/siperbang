from __future__ import annotations

import logging
import math
import os
import tempfile
from contextlib import asynccontextmanager
from functools import partial
from pathlib import Path
from threading import (
    Event,
    Lock,
    Thread,
)
from time import perf_counter
from typing import Any

import pypdfium2 as pdfium
from fastapi import (
    Depends,
    FastAPI,
    File,
    HTTPException,
    UploadFile,
    status,
)
from fastapi.responses import JSONResponse
from starlette.concurrency import run_in_threadpool
from fastapi.responses import JSONResponse

from app.config import settings
from app.ocr_engine import (
    NoTextDetectedError,
    OcrEngineError,
    OcrEngineUnavailableError,
    ocr_engine,
)
from app.receipt_parser import parse_receipt
from app.schemas import (
    HealthResponse,
    LineData,
    OcrResponse,
    OcrWarning,
    PageData,
)
from app.security import verify_service_token


logger = logging.getLogger(__name__)


_model_state_lock = Lock()
_model_state = "loading"
_model_error: str | None = None


def _set_model_state(
    state: str,
    error: str | None = None,
) -> None:
    global _model_state
    global _model_error

    with _model_state_lock:
        _model_state = state
        _model_error = error


def _get_model_state() -> tuple[str, str | None]:
    with _model_state_lock:
        return _model_state, _model_error


def _hard_timeout_watchdog(
    cancel_event: Event,
    timeout_seconds: int,
    filename: str,
) -> None:
    """
    Mengakhiri proses OCR jika native inference
    tidak selesai dalam batas waktu.

    run-server.ps1 akan menyalakan service kembali.
    """
    timeout_seconds = max(
        30,
        int(timeout_seconds),
    )

    if cancel_event.wait(
        timeout_seconds
    ):
        return

    logger.critical(
        (
            "OCR melewati hard timeout "
            "%d detik. Service dihentikan. "
            "file=%s"
        ),
        timeout_seconds,
        filename,
    )

    os._exit(75)


def _load_model_in_background() -> None:
    """
    Memuat PaddleOCR di thread terpisah.

    Dengan cara ini, Uvicorn dapat membuka port 8001 lebih dahulu.
    Endpoint health akan memberi status 503 selama model masih dimuat.
    """
    _set_model_state(
        state="loading",
        error=None,
    )

    try:
        logger.info(
            "Memuat model PaddleOCR di background."
        )

        ocr_engine.ensure_loaded()
        ocr_engine.warm_up()

        _set_model_state(
            state="ready",
            error=None,
        )

        logger.info(
            "Model PaddleOCR siap menerima dokumen."
        )
    except Exception as exception:
        logger.exception(
            "Model PaddleOCR gagal dimuat."
        )

        _set_model_state(
            state="failed",
            error=str(exception),
        )


@asynccontextmanager
async def lifespan(
    _: FastAPI,
):
    loader_thread = Thread(
        target=_load_model_in_background,
        name="paddleocr-model-loader",
        daemon=True,
    )

    loader_thread.start()

    # Jangan menunggu model selesai di sini.
    # Yield membuat Uvicorn segera membuka port 8001.
    yield

    logger.info(
        "Layanan PaddleOCR dihentikan."
    )


app = FastAPI(
    title="Siperbang OCR Service",
    version="1.0.0",
    lifespan=lifespan,
)


ALLOWED_EXTENSIONS = {
    ".jpg",
    ".jpeg",
    ".png",
    ".pdf",
    ".tif",
    ".tiff",
}


EXTENSION_MIME_TYPES = {
    ".jpg": {"image/jpeg"},
    ".jpeg": {"image/jpeg"},
    ".png": {"image/png"},
    ".pdf": {"application/pdf"},
    ".tif": {"image/tiff"},
    ".tiff": {"image/tiff"},
}


def detect_mime_type(header: bytes) -> str | None:
    """
    Mendeteksi tipe file dari signature byte.

    Fungsi ini tidak mempercayai ekstensi dan Content-Type
    yang dikirim pengguna.
    """

    if header.startswith(b"%PDF-"):
        return "application/pdf"

    if header.startswith(b"\xff\xd8\xff"):
        return "image/jpeg"

    if header.startswith(
        b"\x89PNG\r\n\x1a\n"
    ):
        return "image/png"

    if header.startswith(b"II*\x00"):
        return "image/tiff"

    if header.startswith(b"MM\x00*"):
        return "image/tiff"

    return None


def normalize_box(
    raw_box: Any,
) -> list[list[float]]:
    if not isinstance(
        raw_box,
        (list, tuple),
    ):
        return []

    result: list[list[float]] = []

    for raw_point in raw_box:
        if (
            not isinstance(
                raw_point,
                (list, tuple),
            )
            or len(raw_point) < 2
        ):
            continue

        try:
            x = float(raw_point[0])
            y = float(raw_point[1])
        except (TypeError, ValueError):
            continue

        result.append([x, y])

    return result


def infer_page_dimensions(
    lines: list[LineData],
) -> tuple[int, int]:
    """
    Mengambil perkiraan ukuran halaman dari koordinat OCR.

    Pada tahap berikutnya ukuran akan diambil langsung dari
    hasil render gambar atau PDF.
    """

    max_x = 0.0
    max_y = 0.0

    for line in lines:
        for point in line.box:
            if len(point) < 2:
                continue

            max_x = max(max_x, float(point[0]))
            max_y = max(max_y, float(point[1]))

    return (
        math.ceil(max_x),
        math.ceil(max_y),
    )


def convert_page(
    page_number: int,
    raw_page: list[list[Any]],
) -> tuple[
    PageData,
    list[dict[str, Any]],
]:
    page_lines: list[LineData] = []
    parser_lines: list[dict[str, Any]] = []

    for raw_line in raw_page:
        if (
            not isinstance(
                raw_line,
                (list, tuple),
            )
            or len(raw_line) < 2
        ):
            continue

        raw_box = raw_line[0]
        recognition = raw_line[1]

        if (
            not isinstance(
                recognition,
                (list, tuple),
            )
            or len(recognition) < 2
        ):
            continue

        text = str(
            recognition[0]
        ).strip()

        if not text:
            continue

        try:
            confidence = float(
                recognition[1]
            )
        except (TypeError, ValueError):
            confidence = 0.0

        box = normalize_box(
            raw_box
        )

        line = LineData(
            text=text,
            confidence=confidence,
            box=box,
        )

        page_lines.append(line)

        parser_lines.append({
            "page": page_number,
            "text": text,
            "confidence": confidence,
            "box": box,
        })

    width, height = infer_page_dimensions(
        page_lines
    )

    return (
        PageData(
            page=page_number,
            width=width,
            height=height,
            lines=page_lines,
        ),
        parser_lines,
    )


@app.get(
    "/health",
    response_model=HealthResponse,
)
def health_check() -> Any:
    model_state, model_error = (
        _get_model_state()
    )

    if (
        model_state != "ready"
        or not ocr_engine.is_loaded
    ):
        if model_state == "failed":
            error_message = (
                model_error
                or "PaddleOCR gagal dimuat."
            )
        else:
            error_message = (
                "PaddleOCR sedang dimuat."
            )

        payload = HealthResponse(
            status="unhealthy",
            service="siperbang-ocr",
            engine=ocr_engine.engine_name,
            engine_version=(
                ocr_engine.engine_version
            ),
            paddle_version=(
                ocr_engine.paddle_version
            ),
            model=ocr_engine.model_name,
            device=ocr_engine.device,
            engine_loaded=False,
            error=error_message,
        )

        return JSONResponse(
            status_code=(
                status.HTTP_503_SERVICE_UNAVAILABLE
            ),
            content=payload.model_dump(),
        )

    return HealthResponse(
        status="healthy",
        service="siperbang-ocr",
        engine=ocr_engine.engine_name,
        engine_version=(
            ocr_engine.engine_version
        ),
        paddle_version=(
            ocr_engine.paddle_version
        ),
        model=ocr_engine.model_name,
        device=ocr_engine.device,
        engine_loaded=True,
        error=None,
    )


@app.post(
    "/internal/v1/receipt-ocr",
    response_model=OcrResponse,
)
async def process_receipt(
    document: UploadFile = File(...),
    _: str = Depends(
        verify_service_token
    ),
) -> OcrResponse:
    model_state, model_error = (
        _get_model_state()
    )

    if (
        model_state != "ready"
        or not ocr_engine.is_loaded
    ):
        if model_state == "failed":
            detail = (
                "Mesin OCR gagal dimuat. "
                "Periksa terminal FastAPI."
            )
        else:
            detail = (
                "Mesin OCR sedang dipersiapkan. "
                "Coba lagi beberapa detik."
            )

        if model_error:
            logger.error(
                "OCR engine unavailable: %s",
                model_error,
            )

        raise HTTPException(
            status_code=(
                status.HTTP_503_SERVICE_UNAVAILABLE
            ),
            detail=detail,
        )

    filename = (
        document.filename
        or "document"
    )

    request_started_at = perf_counter()

    extension = Path(
        filename
    ).suffix.lower()

    if extension not in ALLOWED_EXTENSIONS:
        raise HTTPException(
            status_code=(
                status.HTTP_415_UNSUPPORTED_MEDIA_TYPE
            ),
            detail=(
                "File extension is not supported."
            ),
        )

    file_descriptor, temporary_path = (
        tempfile.mkstemp(
            suffix=extension
        )
    )

    try:
        file_size = 0
        file_header = b""

        with os.fdopen(
            file_descriptor,
            "wb",
        ) as temporary_file:
            while True:
                chunk = await document.read(
                    1024 * 1024
                )

                if not chunk:
                    break

                file_size += len(chunk)

                if (
                    file_size
                    > settings.max_upload_size
                ):
                    raise HTTPException(
                        status_code=(
                            status.HTTP_413_REQUEST_ENTITY_TOO_LARGE
                        ),
                        detail=(
                            "Uploaded file exceeds "
                            "the maximum allowed size."
                        ),
                    )

                if len(file_header) < 16:
                    remaining = (
                        16 - len(file_header)
                    )

                    file_header += chunk[
                        :remaining
                    ]

                temporary_file.write(
                    chunk
                )

        if file_size == 0:
            raise HTTPException(
                status_code=(
                    status.HTTP_422_UNPROCESSABLE_ENTITY
                ),
                detail="Uploaded file is empty.",
            )

        detected_mime = detect_mime_type(
            file_header
        )

        expected_mimes = (
            EXTENSION_MIME_TYPES[
                extension
            ]
        )

        if (
            detected_mime is None
            or detected_mime
            not in expected_mimes
        ):
            raise HTTPException(
                status_code=(
                    status.HTTP_415_UNSUPPORTED_MEDIA_TYPE
                ),
                detail=(
                    "File content does not match "
                    "the supported document format."
                ),
            )

        if detected_mime == "application/pdf":
            try:
                pdf_document = pdfium.PdfDocument(
                    temporary_path
                )

                page_count = len(
                    pdf_document
                )

                pdf_document.close()
            except Exception as exc:
                raise HTTPException(
                    status_code=422,
                    detail="File PDF tidak dapat dibaca.",
                ) from exc

            if page_count > settings.max_pdf_pages:
                raise HTTPException(
                    status_code=422,
                    detail=(
                        "Untuk pemrosesan cepat, PDF hanya "
                        f"boleh memiliki maksimal "
                        f"{settings.max_pdf_pages} halaman. "
                        "Pisahkan PDF menjadi beberapa file."
                    ),
                )

        ocr_started_at = perf_counter()

        try:
            watchdog_cancel = Event()

            watchdog_thread = Thread(
                target=_hard_timeout_watchdog,
                args=(
                    watchdog_cancel,
                    settings.hard_timeout_seconds,
                    filename,
                ),
                name="paddleocr-hard-timeout",
                daemon=True,
            )

            watchdog_thread.start()

            try:
                raw_pages = await run_in_threadpool(
                    partial(
                        ocr_engine.process,
                        temporary_path,
                        max_pages=(
                            settings.max_pdf_pages
                        ),
                    )
                )
            finally:
                watchdog_cancel.set()
        except NoTextDetectedError as exc:
            raise HTTPException(
                status_code=(
                    status.HTTP_422_UNPROCESSABLE_ENTITY
                ),
                detail=(
                    "No readable text was detected "
                    "in the document."
                ),
            ) from exc
        except OcrEngineUnavailableError as exc:
            logger.exception(
                "PaddleOCR engine is unavailable."
            )

            raise HTTPException(
                status_code=(
                    status.HTTP_503_SERVICE_UNAVAILABLE
                ),
                detail=(
                    "OCR engine is currently unavailable."
                ),
            ) from exc
        except OcrEngineError as exc:
            logger.exception(
                "PaddleOCR inference failed."
            )

            raise HTTPException(
                status_code=(
                    status.HTTP_500_INTERNAL_SERVER_ERROR
                ),
                detail=(
                    "OCR processing failed."
                ),
            ) from exc

        ocr_duration = (
            perf_counter()
            - ocr_started_at
        )

        pages: list[PageData] = []
        all_parser_lines: list[
            dict[str, Any]
        ] = []
        raw_page_texts: list[str] = []
        all_confidences: list[float] = []

        for page_number, raw_page in enumerate(
            raw_pages,
            start=1,
        ):
            page, parser_lines = convert_page(
                page_number=page_number,
                raw_page=raw_page,
            )

            if not page.lines:
                continue

            pages.append(page)

            all_parser_lines.extend(
                parser_lines
            )

            raw_page_texts.append(
                "\n".join(
                    line.text
                    for line in page.lines
                )
            )

            all_confidences.extend(
                line.confidence
                for line in page.lines
            )

        if not pages or not all_parser_lines:
            raise HTTPException(
                status_code=(
                    status.HTTP_422_UNPROCESSABLE_ENTITY
                ),
                detail=(
                    "No readable text was detected "
                    "in the document."
                ),
            )

        total_duration = (
            perf_counter()
            - request_started_at
        )

        logger.info(
            (
                "OCR selesai. file=%s "
                "size_bytes=%d "
                "pages=%d "
                "ocr_seconds=%.3f "
                "total_seconds=%.3f"
            ),
            filename,
            file_size,
            len(pages),
            ocr_duration,
            total_duration,
        )

        try:
            parsed_document = parse_receipt(
                all_parser_lines
            )
        except Exception as exc:
            logger.exception(
                "Receipt parser failed."
            )

            raise HTTPException(
                status_code=(
                    status.HTTP_500_INTERNAL_SERVER_ERROR
                ),
                detail=(
                    "OCR text was detected, but "
                    "receipt parsing failed."
                ),
            ) from exc

        overall_confidence = (
            sum(all_confidences)
            / len(all_confidences)
            if all_confidences
            else None
        )

        warnings: list[OcrWarning] = []

        if (
            overall_confidence is not None
            and overall_confidence < 0.75
        ):
            warnings.append(
                OcrWarning(
                    code="low_average_confidence",
                    field=None,
                    message=(
                        "Rata-rata confidence OCR rendah. "
                        "Periksa kembali seluruh hasil."
                    ),
                    severity="warning",
                )
            )

        important_fields = {
            "store_name":
                parsed_document.store_name,

            "invoice_no":
                parsed_document.invoice_no,

            "date":
                parsed_document.date,

            "total":
                parsed_document.total,
        }

        for field_name, field_value in (
            important_fields.items()
        ):
            if field_value.value is None:
                warnings.append(
                    OcrWarning(
                        code="field_not_detected",
                        field=field_name,
                        message=(
                            f"Field {field_name} "
                            "tidak berhasil ditemukan."
                        ),
                        severity="warning",
                    )
                )
                continue

            if (
                field_value.confidence is not None
                and field_value.confidence < 0.70
            ):
                warnings.append(
                    OcrWarning(
                        code="low_field_confidence",
                        field=field_name,
                        message=(
                            f"Confidence field "
                            f"{field_name} rendah."
                        ),
                        severity="warning",
                    )
                )

        if not parsed_document.items:
            warnings.append(
                OcrWarning(
                    code="items_not_detected",
                    field="items",
                    message=(
                        "Daftar item tidak berhasil "
                        "diekstrak secara otomatis."
                    ),
                    severity="warning",
                )
            )

        if (
            parsed_document.tax_rate.source
            == "derived"
        ):
            warnings.append(
                OcrWarning(
                    code="derived_tax_rate",
                    field="tax_rate",
                    message=(
                        "Tarif pajak merupakan hasil "
                        "perhitungan, bukan teks yang "
                        "terbaca langsung dari dokumen."
                    ),
                    severity="warning",
                )
            )

        if (
            parsed_document.tax_amount.source
            == "derived"
        ):
            warnings.append(
                OcrWarning(
                    code="derived_tax_amount",
                    field="tax_amount",
                    message=(
                        "Nominal pajak merupakan hasil "
                        "perhitungan."
                    ),
                    severity="warning",
                )
            )

        for item_index, item in enumerate(
            parsed_document.items
        ):
            fields = (
                "name",
                "qty",
                "price",
                "subtotal",
            )

            for field_name in fields:
                field_data = item.get(
                    field_name,
                    {},
                )

                confidence = field_data.get(
                    "confidence"
                )

                if (
                    confidence is not None
                    and float(confidence) < 0.70
                ):
                    warnings.append(
                        OcrWarning(
                            code=(
                                "low_item_confidence"
                            ),
                            field=(
                                f"items.{item_index}."
                                f"{field_name}"
                            ),
                            message=(
                                f"Confidence {field_name} "
                                f"pada item "
                                f"{item_index + 1} rendah."
                            ),
                            severity="warning",
                        )
                    )

        return OcrResponse(
            success=True,
            engine=ocr_engine.engine_name,
            engine_version=(
                ocr_engine.engine_version
            ),
            paddle_version=(
                ocr_engine.paddle_version
            ),
            overall_confidence=(
                overall_confidence
            ),
            raw_text="\n\n".join(
                raw_page_texts
            ),
            pages=pages,
            document=parsed_document,
            items=parsed_document.items,
            warnings=warnings,
        )

    finally:
        await document.close()

        if os.path.exists(
            temporary_path
        ):
            try:
                os.remove(
                    temporary_path
                )
            except OSError:
                logger.exception(
                    "Failed to delete temporary OCR file."
                )
