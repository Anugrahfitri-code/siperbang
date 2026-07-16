from __future__ import annotations

import argparse
import json
from pathlib import Path

from app.receipt_parser import parse_receipt


def parse_arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Uji receipt parser menggunakan raw-ocr.json."
    )
    parser.add_argument(
        "raw_ocr",
        help="Path ke file raw-ocr.json.",
    )
    return parser.parse_args()


def main() -> int:
    args = parse_arguments()
    path = Path(args.raw_ocr).resolve()

    if not path.is_file():
        raise FileNotFoundError(f"File tidak ditemukan: {path}")

    payload = json.loads(path.read_text(encoding="utf-8"))

    parser_lines: list[dict] = []

    for page in payload.get("pages", []):
        page_number = int(page.get("page", 1))

        for line in page.get("lines", []):
            parser_lines.append({
                "page": page_number,
                "text": line.get("text", ""),
                "confidence": line.get("confidence", 0.0),
                "box": line.get("box", []),
            })

    result = parse_receipt(parser_lines)

    print(json.dumps(
        result.model_dump(),
        ensure_ascii=False,
        indent=2,
    ))

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
