from __future__ import annotations

from pathlib import Path

import numpy as np
import pypdfium2 as pdfium
from PIL import (
    Image,
    ImageEnhance,
    ImageFilter,
    ImageOps,
    ImageSequence,
)


class DocumentLoadError(RuntimeError):
    """Dokumen tidak dapat dirender menjadi gambar."""


def _projection_bounds(
    projection: np.ndarray,
    minimum_density: int,
) -> tuple[int, int] | None:
    active = np.flatnonzero(
        projection >= minimum_density
    )

    if active.size == 0:
        return None

    return int(active[0]), int(active[-1])


def _crop_white_margin(
    image: Image.Image,
    threshold: int = 245,
) -> Image.Image:
    """
    Crop margin putih menggunakan gambar preview kecil.

    Pemrosesan mask tidak dilakukan pada gambar PDF beresolusi
    penuh agar penggunaan RAM dan waktu CPU tetap rendah.
    """
    rgb_image = image.convert("RGB")
    width, height = rgb_image.size

    if width < 80 or height < 80:
        return rgb_image

    # Hanya abaikan sedikit bagian tepi scanner.
    border = max(
        2,
        min(
            12,
            int(min(width, height) * 0.005),
        ),
    )

    if (
        width <= border * 2
        or height <= border * 2
    ):
        return rgb_image

    inner = rgb_image.crop((
        border,
        border,
        width - border,
        height - border,
    ))

    inner_width, inner_height = inner.size

    # Gunakan preview kecil hanya untuk mencari batas konten.
    # Ini jauh lebih cepat daripada membuat array float32
    # dari seluruh halaman PDF.
    preview = inner.copy()

    preview.thumbnail(
        (1400, 1400),
        Image.Resampling.BILINEAR,
    )

    preview_width, preview_height = (
        preview.size
    )

    if (
        preview_width < 1
        or preview_height < 1
    ):
        return rgb_image

    grayscale = np.asarray(
        ImageOps.grayscale(preview),
        dtype=np.uint8,
    )

    mask = grayscale < threshold

    if int(mask.sum()) < 32:
        return rgb_image

    row_minimum = max(
        3,
        int(preview_width * 0.002),
    )

    column_minimum = max(
        3,
        int(preview_height * 0.002),
    )

    row_bounds = _projection_bounds(
        mask.sum(axis=1),
        row_minimum,
    )

    column_bounds = _projection_bounds(
        mask.sum(axis=0),
        column_minimum,
    )

    if (
        row_bounds is None
        or column_bounds is None
    ):
        return rgb_image

    preview_y_min, preview_y_max = (
        row_bounds
    )

    preview_x_min, preview_x_max = (
        column_bounds
    )

    scale_x = (
        inner_width / preview_width
    )

    scale_y = (
        inner_height / preview_height
    )

    x_min = int(
        preview_x_min * scale_x
    )

    x_max = int(
        (preview_x_max + 1) * scale_x
    )

    y_min = int(
        preview_y_min * scale_y
    )

    y_max = int(
        (preview_y_max + 1) * scale_y
    )

    content_width = max(
        1,
        x_max - x_min,
    )

    content_height = max(
        1,
        y_max - y_min,
    )

    padding_x = max(
        12,
        int(content_width * 0.03),
    )

    padding_y = max(
        12,
        int(content_height * 0.03),
    )

    x_min = max(
        0,
        x_min - padding_x,
    )

    y_min = max(
        0,
        y_min - padding_y,
    )

    x_max = min(
        inner_width,
        x_max + padding_x,
    )

    y_max = min(
        inner_height,
        y_max + padding_y,
    )

    cropped = inner.crop((
        x_min,
        y_min,
        x_max,
        y_max,
    ))

    if (
        cropped.width < 64
        or cropped.height < 64
    ):
        return rgb_image

    return cropped


def _resize_image(
    image: Image.Image,
    max_side: int,
) -> Image.Image:
    width, height = image.size
    longest_side = max(width, height)

    minimum_side = min(
        1600,
        max_side,
    )

    target_side = min(
        max(longest_side, minimum_side),
        max_side,
    )

    if target_side == longest_side:
        return image

    scale = target_side / longest_side

    resized_width = max(
        1,
        round(width * scale),
    )

    resized_height = max(
        1,
        round(height * scale),
    )

    return image.resize(
        (
            resized_width,
            resized_height,
        ),
        Image.Resampling.LANCZOS,
    )


def _prepare_image(
    image: Image.Image,
    max_side: int,
) -> np.ndarray:
    image = ImageOps.exif_transpose(
        image
    ).convert("RGB")

    image = _crop_white_margin(
        image,
        threshold=250,
    )

    image = _resize_image(
        image,
        max_side=max_side,
    )

    image = ImageOps.autocontrast(
        image,
        cutoff=1,
    )

    image = ImageEnhance.Contrast(
        image
    ).enhance(1.15)

    image = image.filter(
        ImageFilter.UnsharpMask(
            radius=1.0,
            percent=110,
            threshold=3,
        )
    )

    return np.ascontiguousarray(
        np.asarray(image)
    )


def load_document_pages(
    file_path: str,
    max_pages: int = 1,
    pdf_dpi: int = 144,
    max_side: int = 1280,
) -> list[np.ndarray]:
    path = Path(file_path)

    if not path.is_file():
        raise DocumentLoadError(
            "File dokumen tidak ditemukan."
        )

    extension = path.suffix.lower()

    pages: list[np.ndarray] = []

    if extension == ".pdf":
        try:
            pdf_document = pdfium.PdfDocument(
                str(path)
            )
        except Exception as exc:
            raise DocumentLoadError(
                "PDF tidak dapat dibuka."
            ) from exc

        try:
            page_count = min(
                len(pdf_document),
                max_pages,
            )

            render_scale = (
                pdf_dpi / 72
            )

            for page_index in range(
                page_count
            ):
                page = pdf_document[
                    page_index
                ]

                bitmap = page.render(
                    scale=render_scale,
                )

                try:
                    pil_image = (
                        bitmap.to_pil()
                    )

                    pages.append(
                        _prepare_image(
                            pil_image,
                            max_side=max_side,
                        )
                    )
                finally:
                    close_bitmap = getattr(
                        bitmap,
                        "close",
                        None,
                    )

                    if callable(close_bitmap):
                        close_bitmap()

                    close_page = getattr(
                        page,
                        "close",
                        None,
                    )

                    if callable(close_page):
                        close_page()
        finally:
            pdf_document.close()

    else:
        try:
            with Image.open(
                path
            ) as source_image:
                for page_index, frame in enumerate(
                    ImageSequence.Iterator(
                        source_image
                    )
                ):
                    if page_index >= max_pages:
                        break

                    pages.append(
                        _prepare_image(
                            frame.copy(),
                            max_side=max_side,
                        )
                    )
        except Exception as exc:
            raise DocumentLoadError(
                "Gambar tidak dapat dibuka."
            ) from exc

    if not pages:
        raise DocumentLoadError(
            "Dokumen tidak menghasilkan halaman gambar."
        )

    return pages
