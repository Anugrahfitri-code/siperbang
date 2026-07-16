from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path

from app.ocr_engine import (
    NoTextDetectedError,
    OcrEngineError,
    ocr_engine,
)


def parse_arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description=(
            "Menguji PaddleOCR secara langsung tanpa Laravel "
            "dan tanpa FastAPI."
        )
    )

    parser.add_argument(
        "file",
        help="Path gambar atau PDF yang akan dibaca.",
    )

    parser.add_argument(
        "--output",
        default="debug-output",
        help="Folder penyimpanan raw text dan JSON.",
    )

    return parser.parse_args()


def main() -> int:
    args = parse_arguments()

    input_path = Path(args.file).resolve()
    output_directory = Path(args.output).resolve()

    if not input_path.is_file():
        print(
            f"File tidak ditemukan: {input_path}",
            file=sys.stderr,
        )
        return 1

    output_directory.mkdir(
        parents=True,
        exist_ok=True,
    )

    try:
        pages = ocr_engine.process(
            str(input_path)
        )
    except NoTextDetectedError as exc:
        print(
            f"Tidak ada teks yang terbaca: {exc}",
            file=sys.stderr,
        )
        return 2
    except OcrEngineError as exc:
        print(
            f"OCR gagal: {exc}",
            file=sys.stderr,
        )
        return 3
    except Exception as exc:
        print(
            f"Kesalahan tidak terduga: {exc}",
            file=sys.stderr,
        )
        return 4

    json_pages: list[dict] = []
    raw_text_parts: list[str] = []

    for page_number, page_lines in enumerate(
        pages,
        start=1,
    ):
        json_lines: list[dict] = []

        print()
        print(
            f"========== HALAMAN {page_number} =========="
        )

        for box, recognition in page_lines:
            text, confidence = recognition

            raw_text_parts.append(text)

            json_lines.append({
                "text": text,
                "confidence": confidence,
                "box": box,
            })

            print(
                f"[{confidence:.4f}] {text}"
            )

        json_pages.append({
            "page": page_number,
            "lines": json_lines,
        })

    raw_text = "\n".join(
        raw_text_parts
    )

    raw_text_path = (
        output_directory
        / "raw-text.txt"
    )

    raw_json_path = (
        output_directory
        / "raw-ocr.json"
    )

    raw_text_path.write_text(
        raw_text,
        encoding="utf-8",
    )

    raw_json_path.write_text(
        json.dumps(
            {
                "engine": ocr_engine.engine_name,
                "engine_version": (
                    ocr_engine.engine_version
                ),
                "paddle_version": (
                    ocr_engine.paddle_version
                ),
                "input_file": input_path.name,
                "pages": json_pages,
            },
            ensure_ascii=False,
            indent=2,
        ),
        encoding="utf-8",
    )

    print()
    print("OCR selesai.")
    print(
        f"PaddleOCR: {ocr_engine.engine_version}"
    )
    print(
        f"PaddlePaddle: {ocr_engine.paddle_version}"
    )
    print(
        f"Jumlah halaman: {len(json_pages)}"
    )
    print(
        f"Jumlah teks: {len(raw_text_parts)}"
    )
    print(
        f"Raw text: {raw_text_path}"
    )
    print(
        f"Raw JSON: {raw_json_path}"
    )

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
