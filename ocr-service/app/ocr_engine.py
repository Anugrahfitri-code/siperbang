from __future__ import annotations

import logging
import os

from importlib.metadata import (
    PackageNotFoundError,
    version,
)

from pathlib import Path
from threading import Lock
from time import perf_counter
from typing import Any

from paddleocr import PaddleOCR

from app.config import settings
from app.document_loader import (
    DocumentLoadError,
    load_document_pages,
)


logger = logging.getLogger(__name__)


class OcrEngineError(RuntimeError):
    """Kesalahan umum pada mesin OCR."""


class OcrEngineUnavailableError(OcrEngineError):
    """Model PaddleOCR tidak dapat dimuat."""


class NoTextDetectedError(OcrEngineError):
    """Dokumen berhasil diproses, tetapi tidak ada teks yang terbaca."""


class OcrEngine:
    """
    Mesin OCR lokal berbasis PaddleOCR.

    Isi dokumen hanya dibaca dari piksel gambar atau halaman PDF.
    Nama file tidak digunakan untuk menghasilkan data OCR.

    Format hasil sementara dibuat kompatibel dengan main.py saat ini:

    [
        [
            [
                [[x1, y1], [x2, y2], [x3, y3], [x4, y4]],
                ("teks", confidence)
            ]
        ]
    ]
    """

    def __init__(self) -> None:
        self._engine: PaddleOCR | None = None
        self._load_error: Exception | None = None
        self._load_lock = Lock()
        self._inference_lock = Lock()

    @property
    def engine_name(self) -> str:
        return "paddleocr"

    @property
    def engine_version(self) -> str:
        try:
            return version("paddleocr")
        except PackageNotFoundError:
            return "unknown"

    @property
    def paddle_version(self) -> str:
        try:
            return version("paddlepaddle")
        except PackageNotFoundError:
            return "unknown"

    @property
    def is_loaded(self) -> bool:
        return self._engine is not None and self._load_error is None

    @property
    def model_name(self) -> str:
        return "PP-OCRv6_small"

    @property
    def device(self) -> str:
        return "cpu"

    @property
    def load_error(self) -> str | None:
        if self._load_error is None:
            return None

        return str(self._load_error)

    def ensure_loaded(self) -> None:
        """
        Memastikan model benar-benar dapat dimuat.

        Digunakan oleh endpoint health check.
        """
        self._get_engine()

    def _effective_pdf_dpi(
        self,
    ) -> int:
        """
        Mencegah render PDF terlalu besar pada CPU.

        Nilai .env yang melebihi 220 tetap akan dibatasi
        agar layanan tidak kehabisan waktu atau RAM.
        """
        return max(
            144,
            min(
                int(settings.pdf_dpi),
                220,
            ),
        )

    def _effective_max_side(
        self,
    ) -> int:
        """
        Membatasi gambar OCR maksimal 2000 piksel.

        Setelah kuitansi berhasil dicrop, ukuran ini sudah cukup
        untuk struk cetak tanpa membebani model secara berlebihan.
        """
        return max(
            1280,
            min(
                int(settings.max_image_side),
                2000,
            ),
        )

    def _get_engine(self) -> PaddleOCR:
        """
        Memuat model satu kali.

        Request selanjutnya menggunakan objek model yang sama agar model tidak
        dimuat ulang pada setiap pemindaian.
        """
        if self._engine is not None:
            return self._engine

        if self._load_error is not None:
            raise OcrEngineUnavailableError(
                "PaddleOCR gagal dimuat pada percobaan sebelumnya."
            ) from self._load_error

        with self._load_lock:
            if self._engine is not None:
                return self._engine

            try:
                logger.info(
                    "Memuat PaddleOCR %s dengan PaddlePaddle %s pada CPU.",
                    self.engine_version,
                    self.paddle_version,
                )

                self._engine = PaddleOCR(
                    lang="id",
                    ocr_version="PP-OCRv6",
                    text_detection_model_name=(
                        "PP-OCRv6_small_det"
                    ),

                    text_recognition_model_name=(
                        "PP-OCRv6_small_rec"
                    ),

                    device="cpu",

                    use_doc_orientation_classify=True,
                    use_doc_unwarping=False,
                    use_textline_orientation=True,

                    text_det_limit_side_len=(
                        self._effective_max_side()
                    ),

                    text_det_limit_type="max",

                    text_det_thresh=0.30,
                    text_det_box_thresh=0.52,
                    text_det_unclip_ratio=1.8,

                    text_rec_score_thresh=0.25,

                    text_recognition_batch_size=max(
                        1,
                        min(
                            4,
                            int(
                                settings
                                .recognition_batch_size
                            ),
                        ),
                    ),

                    enable_mkldnn=(
                        settings.enable_mkldnn
                    ),

                    mkldnn_cache_capacity=10,

                    cpu_threads=max(
                        1,
                        min(
                            int(settings.cpu_threads),
                            os.cpu_count() or 4,
                            6,
                        ),
                    ),
                )

                logger.info("PaddleOCR berhasil dimuat.")

                return self._engine

            except Exception as exc:
                self._load_error = exc

                logger.exception(
                    "PaddleOCR gagal dimuat."
                )

                raise OcrEngineUnavailableError(
                    "Model PaddleOCR tidak dapat dimuat. "
                    "Periksa instalasi Python dan log FastAPI."
                ) from exc

    def _predict_page(
        self,
        engine: PaddleOCR,
        page_image: Any,
        *,
        use_orientation: bool,
    ) -> list[list[Any]]:
        """
        Menjalankan satu OCR pass.

        Pass pertama tidak menggunakan orientation classifier.
        Orientation hanya dijalankan sebagai fallback jika pass
        pertama sama sekali tidak menemukan teks.
        """
        predictions = engine.predict(
            page_image,

            use_doc_orientation_classify=(
                use_orientation
            ),

            use_doc_unwarping=False,

            use_textline_orientation=(
                use_orientation
            ),

            text_det_limit_side_len=(
                self._effective_max_side()
            ),

            text_det_limit_type="max",

            text_det_thresh=0.30,
            text_det_box_thresh=0.52,
            text_det_unclip_ratio=1.8,

            text_rec_score_thresh=0.25,
        )

        page_lines: list[list[Any]] = []

        for prediction in predictions:
            result_data = (
                self._result_to_dict(
                    prediction
                )
            )

            page_lines.extend(
                self._extract_lines(
                    result_data
                )
            )

        return page_lines

    def process(
        self,
        file_path: str,
        original_filename: str = "",
        max_pages: int = 1,
    ) -> list[list[list[Any]]]:
        del original_filename

        path = Path(file_path)

        if not path.is_file():
            raise OcrEngineError(
                "File OCR tidak ditemukan."
            )

        allowed_extensions = {
            ".jpg",
            ".jpeg",
            ".png",
            ".pdf",
            ".tif",
            ".tiff",
        }

        if (
            path.suffix.lower()
            not in allowed_extensions
        ):
            raise OcrEngineError(
                "Format file tidak didukung oleh mesin OCR."
            )

        engine = self._get_engine()

        try:
            render_started_at = perf_counter()

            document_pages = (
                load_document_pages(
                    file_path=str(path),
                    max_pages=max_pages,
                    pdf_dpi=(
                        self._effective_pdf_dpi()
                    ),
                    max_side=(
                        self._effective_max_side()
                    ),
                )
            )

            logger.info(
                (
                    "Render dan preprocessing selesai "
                    "dalam %.3f detik. "
                    "dpi=%d max_side=%d pages=%d"
                ),
                perf_counter() - render_started_at,
                self._effective_pdf_dpi(),
                self._effective_max_side(),
                len(document_pages),
            )
        except DocumentLoadError as exc:
            raise OcrEngineError(
                str(exc)
            ) from exc

        pages: list[list[list[Any]]] = []

        lock_acquired = False

        try:
            # Jangan menunggu tanpa batas apabila ada request OCR
            # sebelumnya yang masih berjalan.
            lock_acquired = (
                self._inference_lock.acquire(
                    timeout=5,
                )
            )

            if not lock_acquired:
                raise OcrEngineError(
                    "Mesin OCR sedang memproses dokumen lain. "
                    "Coba kembali beberapa detik."
                )

            for page_number, page_image in enumerate(
                document_pages,
                start=1,
            ):
                page_started_at = perf_counter()

                # Pass cepat: tanpa model orientation.
                page_lines = self._predict_page(
                    engine,
                    page_image,
                    use_orientation=False,
                )

                first_pass_seconds = (
                    perf_counter()
                    - page_started_at
                )

                # Fallback orientation hanya jika tidak ada teks.
                # Jangan melakukan retry jika pass pertama saja sudah
                # menggunakan waktu terlalu lama.
                if (
                    not page_lines
                    and first_pass_seconds < 45
                ):
                    logger.info(
                        (
                            "Halaman %d tidak menghasilkan teks. "
                            "Menjalankan orientation fallback."
                        ),
                        page_number,
                    )

                    page_lines = self._predict_page(
                        engine,
                        page_image,
                        use_orientation=True,
                    )

                logger.info(
                    (
                        "Inferensi halaman %d selesai "
                        "dalam %.3f detik, lines=%d"
                    ),
                    page_number,
                    perf_counter() - page_started_at,
                    len(page_lines),
                )

                pages.append(
                    page_lines
                )

        except OcrEngineError:
            raise

        except Exception as exc:
            logger.exception(
                "Inferensi PaddleOCR gagal."
            )

            raise OcrEngineError(
                "PaddleOCR gagal memproses dokumen."
            ) from exc

        finally:
            if lock_acquired:
                self._inference_lock.release()

        if not pages or not any(pages):
            raise NoTextDetectedError(
                "PaddleOCR tidak menemukan teks "
                "yang dapat dibaca pada dokumen."
            )

        return pages

    def warm_up(self) -> None:
        """
        Memuat model sebelum pengguna mengunggah dokumen.

        Jika warm-up gagal (misalnya karena dummy image terlalu kecil),
        server tetap dapat melayani request dokumen nyata.
        """
        import numpy as np

        engine = self._get_engine()

        dummy_image = np.full(
            (128, 512, 3),
            255,
            dtype=np.uint8,
        )

        try:
            engine.predict(
                dummy_image,

                use_doc_orientation_classify=False,
                use_doc_unwarping=False,
                use_textline_orientation=False,

                text_det_limit_side_len=512,
                text_det_limit_type="max",

                text_rec_score_thresh=0.45,
            )
            logger.info("Warm-up PaddleOCR berhasil.")
        except Exception:
            logger.warning(
                "Warm-up PaddleOCR gagal pada gambar dummy. "
                "Server tetap berjalan — model akan dipakai saat dokumen nyata dikirim."
            )

    @staticmethod
    def _result_to_dict(
        prediction: Any,
    ) -> dict[str, Any]:
        """
        Mengubah Result PaddleOCR 3.x menjadi dictionary biasa.
        """

        payload = getattr(
            prediction,
            "json",
            None,
        )

        # Sebagian implementasi dapat menyediakan json sebagai method.
        if callable(payload):
            payload = payload()

        if not isinstance(payload, dict):
            raise OcrEngineError(
                "Format hasil PaddleOCR tidak dikenali."
            )

        result_data = payload.get(
            "res",
            payload,
        )

        if not isinstance(result_data, dict):
            raise OcrEngineError(
                "Data hasil PaddleOCR tidak valid."
            )

        return result_data

    def _extract_lines(
        self,
        result_data: dict[str, Any],
    ) -> list[list[Any]]:
        """
        Mengambil teks, confidence, dan koordinat dari hasil PaddleOCR.
        """

        texts = result_data.get(
            "rec_texts"
        ) or []

        scores = result_data.get(
            "rec_scores"
        ) or []

        polygons = result_data.get(
            "rec_polys"
        ) or []

        rectangles = result_data.get(
            "rec_boxes"
        ) or []

        page_lines: list[list[Any]] = []

        for index, raw_text in enumerate(texts):
            text = str(raw_text).strip()

            if not text:
                continue

            confidence = self._safe_float(
                scores[index]
                if index < len(scores)
                else 0.0
            )

            box = self._get_box(
                index=index,
                polygons=polygons,
                rectangles=rectangles,
            )

            page_lines.append([
                box,
                (
                    text,
                    confidence,
                ),
            ])

        # Urutkan dari atas ke bawah dan kiri ke kanan.
        page_lines.sort(
            key=self._reading_order_key
        )

        return page_lines

    @staticmethod
    def _safe_float(
        value: Any,
    ) -> float:
        try:
            return float(value)
        except (TypeError, ValueError):
            return 0.0

    def _get_box(
        self,
        index: int,
        polygons: list[Any],
        rectangles: list[Any],
    ) -> list[list[float]]:
        """
        Ambil koordinat polygon.

        Jika polygon tidak tersedia, ubah rectangle
        [x_min, y_min, x_max, y_max] menjadi empat titik.
        """

        if index < len(polygons):
            polygon = self._normalize_polygon(
                polygons[index]
            )

            if polygon:
                return polygon

        if index < len(rectangles):
            rectangle = rectangles[index]

            if (
                isinstance(rectangle, (list, tuple))
                and len(rectangle) >= 4
            ):
                x_min = self._safe_float(
                    rectangle[0]
                )
                y_min = self._safe_float(
                    rectangle[1]
                )
                x_max = self._safe_float(
                    rectangle[2]
                )
                y_max = self._safe_float(
                    rectangle[3]
                )

                return [
                    [x_min, y_min],
                    [x_max, y_min],
                    [x_max, y_max],
                    [x_min, y_max],
                ]

        return [
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
        ]

    def _normalize_polygon(
        self,
        polygon: Any,
    ) -> list[list[float]]:
        if not isinstance(
            polygon,
            (list, tuple),
        ):
            return []

        points: list[list[float]] = []

        for point in polygon:
            if (
                not isinstance(point, (list, tuple))
                or len(point) < 2
            ):
                continue

            points.append([
                self._safe_float(point[0]),
                self._safe_float(point[1]),
            ])

        if len(points) < 4:
            return []

        return points[:4]

    @staticmethod
    def _reading_order_key(
        line: list[Any],
    ) -> tuple[float, float]:
        box = line[0]

        if not box:
            return (0.0, 0.0)

        x_values = [
            float(point[0])
            for point in box
        ]

        y_values = [
            float(point[1])
            for point in box
        ]

        return (
            min(y_values),
            min(x_values),
        )


ocr_engine = OcrEngine()
