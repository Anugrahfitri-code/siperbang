from __future__ import annotations

import re
from dataclasses import dataclass
from datetime import datetime
from difflib import SequenceMatcher
from statistics import median
from typing import Any

from app.schemas import DocumentData, ExtractedValue


MONTHS = {
    "januari": 1,
    "jan": 1,
    "februari": 2,
    "feb": 2,
    "maret": 3,
    "mar": 3,
    "april": 4,
    "apr": 4,
    "mei": 5,
    "juni": 6,
    "jun": 6,
    "juli": 7,
    "jul": 7,
    "agustus": 8,
    "agu": 8,
    "agt": 8,
    "september": 9,
    "sep": 9,
    "oktober": 10,
    "okt": 10,
    "november": 11,
    "nov": 11,
    "desember": 12,
    "des": 12,
}

BARCODE_PATTERN = re.compile(
    r"(?<!\d)\d{8,14}(?!\d)"
)

MAX_PLAUSIBLE_QUANTITY = 10000

RECEIPT_UNITS = (
    "PCS", "PC", "PACK", "BKS", "BOX", "RG", "BTL", "BOTOL",
    "KRT", "LEMBAR", "LBR", "BUAH", "UNIT", "SET", "ROLL",
    "ROL", "KEPING", "DUS", "KOTAK", "JRG", "RIM", "LSN",
    "PAK", "SACHET", "SCT",
)

RECEIPT_UNIT_PATTERN = (
    r"(?:"
    + "|".join(
        sorted(
            (
                re.escape(unit)
                for unit in RECEIPT_UNITS
            ),
            key=len,
            reverse=True,
        )
    )
    + r")"
)

UNIT_ALIASES = {
    "PC": "PCS",
    "PCS": "PCS",
    "PACK": "PAK",
    "PAK": "PAK",
    "BKS": "BKS",
    "BOX": "BOX",
    "KOTAK": "BOX",
    "RG": "RG",
    "BTL": "BOTOL",
    "BOTOL": "BOTOL",
    "KRT": "DUS",
    "DUS": "DUS",
    "LEMBAR": "LEMBAR",
    "LBR": "LEMBAR",
    "BUAH": "BUAH",
    "UNIT": "UNIT",
    "SET": "SET",
    "ROLL": "ROLL",
    "ROL": "ROLL",
    "KEPING": "KEPING",
    "JRG": "JERIGEN",
    "RIM": "RIM",
    "LSN": "LUSIN",
    "SACHET": "SACHET",
    "SCT": "SACHET",
}


def _extract_unit(text: str) -> str | None:
    """Extract a purchasing unit without confusing product sizes such as 5 L."""
    match = re.search(
        rf"(?i)(?<![A-Z0-9])({RECEIPT_UNIT_PATTERN})(?![A-Z0-9])",
        text,
    )

    if match is None:
        return None

    raw = re.sub(
        r"[^A-Z]",
        "",
        match.group(1).upper(),
    )

    return UNIT_ALIASES.get(raw, raw or None)

ITEM_SEQUENCE_PATTERN = re.compile(
    r"^\s*(\d{1,3})\s*[.)\],;:-]\s*(.*)$"
)

TRANSACTION_DATE_KEYWORDS = (
    "TGL TRX",
    "TGL TRANSAKSI",
    "TANGGAL TRANSAKSI",
    "TRANSACTION DATE",
    "INVOICE DATE",
    "TGL BELANJA",
    "TANGGAL BELANJA",
    "DATE OF PURCHASE",
)

NON_TRANSACTION_DATE_KEYWORDS = (
    "REWARD",
    "SEBELUM",
    "BERLAKU",
    "VALID",
    "VALID UNTIL",
    "EXP",
    "EXPIRED",
    "KADALUARSA",
    "JATUH TEMPO",
    "DUE DATE",
    "TENGGAT",
    "BATAS BAYAR",
    "GARANSI",
)

STORE_EXCLUDES = (
    "invoice",
    "faktur",
    "nota",
    "kuitansi",
    "receipt",
    "tanggal",
    "date",
    "kontak",
    "alamat",
    "telp",
    "telepon",
    "phone",
    "whatsapp",
    "email",
    "kasir",
    "cashier",
    "kepada",
    "customer",
    "pelanggan",
    "jumlah yang harus dibayar",
    "total",
    "subtotal",
    "ppn",
    "pajak",
    "bank",
    "rekening",
)


@dataclass(slots=True)
class OcrLine:
    page: int
    text: str
    confidence: float
    box: list[list[float]]
    x1: float
    y1: float
    x2: float
    y2: float

    @property
    def cx(self) -> float:
        return (self.x1 + self.x2) / 2

    @property
    def cy(self) -> float:
        return (self.y1 + self.y2) / 2

    @property
    def width(self) -> float:
        return max(1.0, self.x2 - self.x1)

    @property
    def height(self) -> float:
        return max(1.0, self.y2 - self.y1)


@dataclass(slots=True)
class OcrRow:
    page: int
    lines: list[OcrLine]

    @property
    def cy(self) -> float:
        return sum(line.cy for line in self.lines) / len(self.lines)

    @property
    def text(self) -> str:
        return " ".join(
            line.text
            for line in sorted(self.lines, key=lambda item: item.x1)
        )


def _keyword_text(text: str) -> str:
    normalized = text.upper().replace("\n", " ")
    normalized = re.sub(r"[^A-Z0-9%/#]+", " ", normalized)
    return re.sub(r"\s+", " ", normalized).strip()


def _similar(left: str, right: str) -> float:
    return SequenceMatcher(
        None,
        _keyword_text(left),
        _keyword_text(right),
    ).ratio()


def _line_from_dict(raw: dict[str, Any]) -> OcrLine | None:
    text = str(raw.get("text", "")).strip()
    if not text:
        return None

    points: list[list[float]] = []

    for point in raw.get("box") or []:
        if not isinstance(point, (list, tuple)) or len(point) < 2:
            continue

        try:
            points.append([
                float(point[0]),
                float(point[1]),
            ])
        except (TypeError, ValueError):
            continue

    if not points:
        return None

    x_values = [point[0] for point in points]
    y_values = [point[1] for point in points]

    return OcrLine(
        page=int(raw.get("page", 1)),
        text=text,
        confidence=float(raw.get("confidence") or 0.0),
        box=points,
        x1=min(x_values),
        y1=min(y_values),
        x2=max(x_values),
        y2=max(y_values),
    )


def _prepare_lines(
    lines_data: list[dict[str, Any]],
) -> list[OcrLine]:
    lines: list[OcrLine] = []

    for raw_line in lines_data:
        line = _line_from_dict(raw_line)
        if line is not None:
            lines.append(line)

    return sorted(
        lines,
        key=lambda line: (
            line.page,
            line.cy,
            line.x1,
        ),
    )


def _build_rows(lines: list[OcrLine]) -> list[OcrRow]:
    rows: list[OcrRow] = []
    pages = sorted({line.page for line in lines})

    for page in pages:
        page_lines = [
            line
            for line in lines
            if line.page == page
        ]

        median_height = median(
            line.height
            for line in page_lines
        ) if page_lines else 20.0

        tolerance = max(
            6.0,
            min(34.0, median_height * 0.42),
        )

        page_rows: list[OcrRow] = []

        for line in sorted(
            page_lines,
            key=lambda item: (
                item.cy,
                item.x1,
            ),
        ):
            target_row: OcrRow | None = None

            for row in reversed(page_rows[-3:]):
                if abs(line.cy - row.cy) <= tolerance:
                    target_row = row
                    break

            if target_row is None:
                page_rows.append(
                    OcrRow(
                        page=page,
                        lines=[line],
                    )
                )
            else:
                target_row.lines.append(line)
                target_row.lines.sort(
                    key=lambda item: item.x1
                )

        rows.extend(page_rows)

    return rows


def parse_number(text: str) -> float | None:
    value = str(text).upper().replace("RP", "").strip()
    value = re.sub(r"[^0-9,.-]", "", value)
    value = value.strip(".,-")

    if not value or not re.search(r"\d", value):
        return None

    negative = str(text).strip().startswith("-")

    if "," in value and "." in value:
        if value.rfind(",") > value.rfind("."):
            value = value.replace(".", "").replace(",", ".")
        else:
            value = value.replace(",", "")
    elif "," in value:
        parts = value.split(",")

        if len(parts[-1]) in (1, 2):
            value = "".join(parts[:-1]) + "." + parts[-1]
        else:
            value = "".join(parts)
    elif "." in value:
        parts = value.split(".")

        if len(parts) > 1 and all(
            len(part) == 3
            for part in parts[1:]
        ):
            value = "".join(parts)
        elif len(parts[-1]) in (1, 2):
            value = "".join(parts[:-1]) + "." + parts[-1]
        else:
            value = "".join(parts)

    try:
        result = float(value)
        return -result if negative else result
    except ValueError:
        return None


def _remove_barcode_tokens(
    text: str,
) -> tuple[str, bool]:
    """
    Menghapus barcode, GTIN, atau kode produk panjang
    agar tidak dianggap sebagai qty maupun nominal uang.
    """
    cleaned, count = BARCODE_PATTERN.subn(
        " ",
        str(text),
    )

    cleaned = re.sub(
        r"\s+",
        " ",
        cleaned,
    ).strip()

    return cleaned, count > 0


def _number_tokens(
    text: str,
) -> list[tuple[str, float]]:
    results: list[tuple[str, float]] = []

    searchable_text, _ = _remove_barcode_tokens(
        text
    )

    pattern = re.compile(
        r"(?i)(?:RP\.?\s*)?\d[\d\s.,]*\d|\d"
    )

    for match in pattern.finditer(
        searchable_text
    ):
        token = match.group(0).strip()
        value = parse_number(token)

        if value is not None:
            results.append((
                token,
                value,
            ))

    return results


def _money_value(line: OcrLine) -> float | None:
    values: list[float] = []

    for token, value in _number_tokens(line.text):
        if "/" in token:
            continue

        if value >= 0:
            values.append(value)

    return max(values) if values else None


def _formatted_money_values(
    text: str,
) -> list[float]:
    """
    Mengambil angka yang benar-benar mempunyai format nominal.

    Nomor urut seperti 3. dan ukuran barang seperti 90X120
    tidak boleh dianggap sebagai harga.
    """
    searchable_text, _ = _remove_barcode_tokens(
        text
    )

    # Contoh hasil OCR:
    # 937 . 142,80
    # diubah menjadi:
    # 937.142,80
    searchable_text = re.sub(
        r"(?<=\d)\s*([.,])\s*(?=\d)",
        r"\1",
        searchable_text,
    )

    patterns = (
        re.compile(
            r"(?i)(?:RP\.?\s*|@\s*)?"
            r"(?<!\d)"
            r"\d{1,3}(?:\.\d{3})+"
            r"(?:,\d{1,2})?"
            r"(?!\d)"
        ),
        re.compile(
            r"(?i)(?:RP\.?\s*|@\s*)?"
            r"(?<!\d)"
            r"\d{1,3}(?:,\d{3})+"
            r"(?:\.\d{1,2})?"
            r"(?!\d)"
        ),
        re.compile(
            r"(?i)(?:RP\.?\s*|@\s*)"
            r"(?<!\d)"
            r"\d{3,}"
            r"(?:[.,]\d{1,2})?"
            r"(?!\d)"
        ),
        re.compile(
            r"(?<!\d)"
            r"\d+,\d{2}"
            r"(?!\d)"
        ),
    )

    values: list[float] = []

    for pattern in patterns:
        for match in pattern.finditer(
            searchable_text
        ):
            value = parse_number(
                match.group(0)
            )

            if value is None or value < 0:
                continue

            duplicate = any(
                abs(value - existing)
                <= max(
                    0.000001,
                    abs(existing) * 0.000001,
                )
                for existing in values
            )

            if not duplicate:
                values.append(value)

    stripped = searchable_text.strip()

    # Mendukung nominal tanpa separator, tetapi hanya jika
    # seluruh baris benar-benar berisi nominal.
    if re.fullmatch(
        r"(?i)(?:RP\.?\s*)?\d{4,}",
        stripped,
    ):
        value = parse_number(
            stripped
        )

        if (
            value is not None
            and value not in values
        ):
            values.append(value)

    return values


def _month_number(
    text: str,
) -> int | None:
    token = re.sub(
        r"[^a-z]",
        "",
        text.lower(),
    )

    if token in MONTHS:
        return MONTHS[token]

    best_name = ""
    best_score = 0.0

    for month_name in MONTHS:
        score = SequenceMatcher(
            None,
            token,
            month_name,
        ).ratio()

        if score > best_score:
            best_name = month_name
            best_score = score

    if best_score >= 0.72:
        return MONTHS[best_name]

    return None


def _normalize_date_text(
    text: str,
) -> str:
    value = (
        str(text)
        .replace("—", "-")
        .replace("–", "-")
        .replace("_", "-")
    )

    # I7 -> 17, O6 -> 06, 2O26 -> 2026.
    # Koreksi hanya dilakukan di sekitar angka.
    value = re.sub(
        r"(?<![A-Za-z0-9])[Iil|](?=\d)",
        "1",
        value,
    )

    value = re.sub(
        r"(?<=\d)[Iil|](?=[./\-\s])",
        "1",
        value,
    )

    value = re.sub(
        r"(?<![A-Za-z0-9])[OoQ](?=\d)",
        "0",
        value,
    )

    value = re.sub(
        r"(?<=\d)[OoQ](?=\d|[./\-])",
        "0",
        value,
    )

    return re.sub(
        r"\s+",
        " ",
        value,
    ).strip()


def _valid_iso_date(
    year: int,
    month: int,
    day: int,
) -> str | None:
    if year < 100:
        year += 2000

    if year < 1990 or year > 2100:
        return None

    try:
        return datetime(
            year,
            month,
            day,
        ).date().isoformat()
    except ValueError:
        return None


def _parse_date(
    text: str,
) -> str | None:
    value = _normalize_date_text(text)

    numeric_patterns = (
        (
            re.compile(
                r"(?<!\d)"
                r"(\d{1,2})\s*[./-]\s*"
                r"(\d{1,2})\s*[./-]\s*"
                r"(\d{2,4})"
                r"(?!\d)"
            ),
            "dmy",
        ),
        (
            re.compile(
                r"(?<!\d)"
                r"(\d{4})\s*[./-]\s*"
                r"(\d{1,2})\s*[./-]\s*"
                r"(\d{1,2})"
                r"(?!\d)"
            ),
            "ymd",
        ),
    )

    for pattern, order in numeric_patterns:
        for match in pattern.finditer(
            value
        ):
            first, second, third = map(
                int,
                match.groups(),
            )

            if order == "dmy":
                day, month, year = (
                    first,
                    second,
                    third,
                )
            else:
                year, month, day = (
                    first,
                    second,
                    third,
                )

            parsed = _valid_iso_date(
                year,
                month,
                day,
            )

            if parsed:
                return parsed

    month_pattern = re.compile(
        r"(?<!\d)"
        r"(\d{1,2})"
        r"\s*(?:[./-]\s*)?"
        r"([A-Za-z]{3,12})"
        r"\s*[,]?\s*"
        r"(?:[./-]\s*)?"
        r"(\d{2,4})"
        r"(?!\d)"
    )

    for match in month_pattern.finditer(
        value
    ):
        month = _month_number(
            match.group(2)
        )

        if month is None:
            continue

        parsed = _valid_iso_date(
            int(match.group(3)),
            month,
            int(match.group(1)),
        )

        if parsed:
            return parsed

    return None


def _extract_date_from_rows(
    rows: list[OcrRow],
) -> ExtractedValue:
    for window_size in (
        2,
        3,
        4,
    ):
        for start in range(
            len(rows)
        ):
            window = rows[
                start:start + window_size
            ]

            if (
                len(window)
                != window_size
            ):
                continue

            if len({
                row.page
                for row in window
            }) != 1:
                continue

            text = " ".join(
                row.text
                for row in window
            )

            value = _parse_date(
                text
            )

            if value is None:
                continue

            confidence = min(
                (
                    line.confidence
                    for row in window
                    for line in row.lines
                ),
                default=None,
            )

            return ExtractedValue(
                value=value,
                confidence=confidence,
                source="ocr_rows",
            )

    return ExtractedValue()


def _is_label(text: str, *labels: str) -> bool:
    normalized = _keyword_text(text)

    return any(
        normalized == _keyword_text(label)
        or _similar(normalized, label) >= 0.78
        for label in labels
    )


def _extract_store_name(
    lines: list[OcrLine],
) -> ExtractedValue:
    if not lines:
        return ExtractedValue()

    first_page = min(line.page for line in lines)
    page_lines = [
        line
        for line in lines
        if line.page == first_page
    ]

    min_y = min(line.y1 for line in page_lines)
    max_y = max(line.y2 for line in page_lines)
    top_limit = min_y + ((max_y - min_y) * 0.36)

    legal_candidates: list[OcrLine] = []
    general_candidates: list[OcrLine] = []

    for line in page_lines:
        if line.cy > top_limit:
            continue

        keyword = _keyword_text(line.text).lower()

        if any(excluded in keyword for excluded in STORE_EXCLUDES):
            continue

        if "@" in line.text:
            continue

        compact_digits = re.sub(r"\D", "", line.text)
        if compact_digits.startswith("08") and len(compact_digits) >= 9:
            continue

        letters = sum(character.isalpha() for character in line.text)
        alpha_ratio = letters / max(1, len(line.text))

        if letters < 3 or alpha_ratio < 0.45:
            continue

        if re.match(
            r"(?i)^\s*(PT\.?|CV\.?|UD\.?|PD\.?|TOKO\b|TB\.?|KOPERASI\b)",
            line.text,
        ):
            legal_candidates.append(line)
        else:
            general_candidates.append(line)

    candidates = legal_candidates or general_candidates

    if not candidates:
        return ExtractedValue()

    best = max(
        candidates,
        key=lambda line: (
            line.confidence
            + (0.25 if line.text.upper() == line.text else 0.0)
            - (line.cy / max(max_y * 10, 1)),
        ),
    )

    return ExtractedValue(
        value=best.text,
        confidence=best.confidence,
        source="ocr",
    )


def _extract_date_field(
    lines: list[OcrLine],
) -> ExtractedValue:
    candidates: list[
        tuple[
            OcrLine,
            str,
            float,
        ]
    ] = []

    for line in lines:
        value = _parse_date(
            line.text
        )

        if value is None:
            continue

        keyword = _keyword_text(
            line.text
        )

        score = 0.0

        # Tanggal yang secara jelas diberi label transaksi
        # harus mendapatkan prioritas paling tinggi.
        if any(
            term in keyword
            for term in TRANSACTION_DATE_KEYWORDS
        ):
            score += 10000.0

        if re.search(
            r"\b(?:TGL|TANGGAL|DATE)\b",
            keyword,
        ):
            score += 1200.0

        if re.search(
            r"\b(?:TRX|TRANSAKSI|TRANSACTION)\b",
            keyword,
        ):
            score += 3000.0

        # Tanggal transaksi biasanya disertai jam.
        if re.search(
            r"\b\d{1,2}[.:]\d{2}"
            r"(?:[.:]\d{2})?\b",
            line.text,
        ):
            score += 300.0

        # Tanggal reward, kedaluwarsa, tenggat, dan jatuh tempo
        # tidak boleh dipilih sebagai tanggal transaksi.
        if any(
            term in keyword
            for term in NON_TRANSACTION_DATE_KEYWORDS
        ):
            score -= 20000.0

        score -= (
            line.page * 10.0
            + line.cy * 0.001
        )

        candidates.append((
            line,
            value,
            score,
        ))

    if not candidates:
        return ExtractedValue()

    best_line, best_value, best_score = max(
        candidates,
        key=lambda item: item[2],
    )

    if best_score <= -10000.0:
        return ExtractedValue()

    source = (
        "ocr_transaction_date"
        if best_score >= 3000.0
        else "ocr"
    )

    return ExtractedValue(
        value=best_value,
        confidence=best_line.confidence,
        source=source,
    )

def _looks_like_invoice_value(text: str) -> bool:
    value = text.strip()
    keyword = _keyword_text(value)

    if re.fullmatch(
        r"(?i)\d+(?:[.,]\d+)?\s*"
        r"(?:PCS?|PACK|BKS|BOX|RG|BTL|"
        r"UNIT|LEMBAR|LBR|BUAH|KEPING)",
        value,
    ):
        return False

    if len(value) < 4 or len(value) > 60:
        return False

    if _parse_date(value):
        return False

    if re.fullmatch(r"[\d.,]+", value):
        return False

    if not re.search(r"\d", value):
        return False

    if any(
        word in keyword
        for word in (
            "TELP",
            "PHONE",
            "WA",
            "REKENING",
        )
    ) and not keyword.startswith("INV"):
        return False

    return bool(
        re.search(r"[A-Z]", keyword)
        or "-" in value
        or "/" in value
    )


def _is_invoice_label(text: str) -> bool:
    keyword = _keyword_text(text)

    return (
        any(
            word in keyword
            for word in (
                "INVOICE",
                "FAKTUR",
                "NOTA",
                "STRUK",
            )
        )
        or _similar(keyword, "INVOICE") >= 0.70
    )


def _extract_invoice_number(
    lines: list[OcrLine],
) -> ExtractedValue:
    # Pola INV selalu diprioritaskan.
    direct_pattern = re.compile(
        r"\bINV(?:OICE)?"
        r"[\s:#/-]*"
        r"[A-Z0-9]"
        r"[A-Z0-9/-]{3,}\b",
        re.IGNORECASE,
    )

    direct_candidates: list[
        tuple[OcrLine, str]
    ] = []

    for line in lines:
        for match in (
            direct_pattern.finditer(
                line.text
            )
        ):
            value = (
                match.group(0)
                .strip()
                .replace(" ", "")
            )

            if _looks_like_invoice_value(
                value
            ):
                direct_candidates.append((
                    line,
                    value,
                ))

    if direct_candidates:
        line, value = max(
            direct_candidates,
            key=lambda item:
                item[0].confidence,
        )

        return ExtractedValue(
            value=value,
            confidence=line.confidence,
            source="ocr_pattern",
        )

    explicit_pattern = re.compile(
        r"(?i)\b"
        r"(?:NO(?:TA)?|NOMOR)"
        r"\.?\s*[:#-]?\s*"
        r"([A-Z0-9]"
        r"[A-Z0-9/-]{1,})\b"
    )

    excluded_context = (
        "ALAMAT",
        "JALAN",
        "JL",
        "TELP",
        "PHONE",
        "WHATSAPP",
        "REKENING",
    )

    for line in lines:
        keyword = _keyword_text(
            line.text
        )

        if any(
            word in keyword
            for word
            in excluded_context
        ):
            continue

        match = explicit_pattern.search(
            line.text
        )

        if not match:
            continue

        value = (
            match.group(1)
            .strip()
        )

        if (
            re.search(r"\d", value)
            and _looks_like_invoice_value(
                value
            )
        ):
            return ExtractedValue(
                value=value,
                confidence=line.confidence,
                source=(
                    "ocr_explicit_number"
                ),
            )

    labels = [
        line
        for line in lines
        if _is_invoice_label(
            line.text
        )
    ]

    candidates = [
        line
        for line in lines
        if _looks_like_invoice_value(
            line.text
        )
    ]

    scored: list[
        tuple[
            float,
            OcrLine,
            OcrLine,
        ]
    ] = []

    for label in labels:
        for candidate in candidates:
            if (
                candidate.page
                != label.page
                or candidate is label
            ):
                continue

            delta_y = (
                candidate.cy
                - label.cy
            )

            if (
                delta_y < -label.height
                or delta_y
                > label.height * 4
            ):
                continue

            if abs(delta_y) <= max(
                label.height,
                candidate.height,
            ):
                x_penalty = (
                    max(
                        0.0,
                        label.x1
                        - candidate.cx,
                    ) * 2
                    + abs(
                        candidate.x1
                        - label.x2
                    ) * 0.1
                )
            else:
                x_penalty = abs(
                    candidate.x1
                    - label.x1
                ) * 0.2

            scored.append((
                abs(delta_y) * 2
                + x_penalty,
                candidate,
                label,
            ))

    if not scored:
        return ExtractedValue()

    _, candidate, label = min(
        scored,
        key=lambda item: item[0],
    )

    return ExtractedValue(
        value=candidate.text.strip(),
        confidence=min(
            candidate.confidence,
            label.confidence,
        ),
        source="ocr_label",
    )


def _label_kind(
    text: str,
) -> str | None:
    keyword = _keyword_text(
        text
    )

    if (
        "SUBTOTAL" in keyword
        or "SUB TOTAL" in keyword
    ):
        return "subtotal"

    if re.search(
        r"\b(PPN|PAJAK|TAX|VAT)\b",
        keyword,
    ):
        return "tax"

    if keyword in {
        "TOTAL",
        "GRAND TOTAL",
        "TOTAL BAYAR",
        "TOT BAYAR",
        "JUMLAH BAYAR",
        "TOTAL JUMLAH",
        "JUMLAH YANG HARUS DIBAYAR",
    } or keyword.startswith(
        "GRAND TOTAL"
    ):
        return "total"

    return None


def _extract_labeled_amounts(
    lines: list[OcrLine],
    rows: list[OcrRow],
) -> dict[str, ExtractedValue]:
    page_max_y = {
        page: max(
            line.y2
            for line in lines
            if line.page == page
        )
        for page in {line.page for line in lines}
    }

    candidates: dict[
        str,
        list[tuple[float, float, float]],
    ] = {
        "subtotal": [],
        "tax": [],
        "total": [],
    }

    for row in rows:
        for label in row.lines:
            kind = _label_kind(label.text)
            if kind is None:
                continue

            nearby_values: list[
                tuple[float, OcrLine]
            ] = []

            for candidate in row.lines:
                if candidate is label:
                    continue

                amount = _money_value(candidate)

                if amount is not None and candidate.cx >= label.cx - 10:
                    nearby_values.append((amount, candidate))

            if not nearby_values:
                next_rows = [
                    candidate_row
                    for candidate_row in rows
                    if candidate_row.page == row.page
                    and 0 < candidate_row.cy - row.cy
                    <= max(label.height * 2.5, 80)
                ]

                for next_row in next_rows[:1]:
                    for candidate in next_row.lines:
                        amount = _money_value(candidate)

                        if amount is None:
                            continue

                        if (
                            abs(candidate.cx - label.cx)
                            <= max(label.width * 1.5, 500)
                            or candidate.cx >= label.cx
                        ):
                            nearby_values.append((
                                amount,
                                candidate,
                            ))

            if not nearby_values:
                continue

            amount, amount_line = max(
                nearby_values,
                key=lambda item: item[0],
            )

            priority = 2.0
            keyword = _keyword_text(label.text)

            if keyword in {
                "GRAND TOTAL",
                "TOTAL BAYAR",
                "JUMLAH YANG HARUS DIBAYAR",
            }:
                priority += 2.0

            vertical_score = (
                3 * row.cy / page_max_y[row.page]
            )

            candidates[kind].append((
                priority + vertical_score,
                amount,
                min(
                    label.confidence,
                    amount_line.confidence,
                ),
            ))

    result: dict[str, ExtractedValue] = {}

    for kind, values in candidates.items():
        if not values:
            result[kind] = ExtractedValue()
            continue

        _, amount, confidence = max(
            values,
            key=lambda item: item[0],
        )

        result[kind] = ExtractedValue(
            value=amount,
            confidence=confidence,
            source="ocr_label",
        )

    return result


def _extract_inline_total(
    lines: list[OcrLine],
) -> ExtractedValue:
    """
    Membaca total ketika label dan nominal berada
    pada satu hasil OCR.

    Contoh:
    TOT. BAYAR : 937.142,80
    """
    labels = (
        (
            "JUMLAH YANG HARUS DIBAYAR",
            6,
        ),
        (
            "GRAND TOTAL",
            6,
        ),
        (
            "TOTAL BAYAR",
            6,
        ),
        (
            "TOT BAYAR",
            6,
        ),
        (
            "TRANSAKSI",
            4,
        ),
        (
            "TOTAL",
            3,
        ),
    )

    candidates: list[
        tuple[
            int,
            int,
            float,
            OcrLine,
        ]
    ] = []

    for line in lines:
        keyword = _keyword_text(
            line.text
        )

        priority = 0

        for label, label_priority in labels:
            if (
                keyword == label
                or keyword.startswith(
                    label + " "
                )
            ):
                priority = label_priority
                break

        if priority == 0:
            continue

        values = _formatted_money_values(
            line.text
        )

        if not values:
            continue

        candidates.append((
            priority,
            line.page,
            line.cy,
            line,
        ))

    if not candidates:
        return ExtractedValue()

    _, _, _, line = max(
        candidates,
        key=lambda item: (
            item[0],
            item[1],
            item[2],
        ),
    )

    values = _formatted_money_values(
        line.text
    )

    return ExtractedValue(
        value=max(values),
        confidence=line.confidence,
        source="ocr_inline_total",
    )


def _header_role(
    text: str,
) -> str | None:
    keyword = _keyword_text(text)

    if keyword in {
        "#",
        "NO",
        "NO.",
    }:
        return "index"

    if (
        "HARGA SATUAN" in keyword
        or "UNIT PRICE" in keyword
        or keyword == "HARGA"
    ):
        return "price"

    if (
        "SUBTOTAL" in keyword
        or "SUB TOTAL" in keyword
        or keyword in {
            "TOTAL",
            "JUMLAH",
            "TOTAL JUMLAH",
        }
    ):
        return "subtotal"

    if (
        keyword in {
            "QTY",
            "OTY",
            "JML",
            "BANYAK",
            "BANYAKNYA",
            "KEPING",
        }
        or _similar(
            keyword,
            "QTY",
        ) >= 0.66
    ):
        return "qty"

    if keyword in {
        "SATUAN",
        "UNIT",
    }:
        return "unit"

    if "DESKRIPSI" in keyword:
        return "description"

    if any(
        phrase in keyword
        for phrase in (
            "NAMA BARANG",
            "NAMA PESANAN",
            "JENIS NAMA PESANAN",
            "BARANG",
            "ITEM",
            "PRODUK",
            "URAIAN",
            "PESANAN",
        )
    ):
        return "name"

    return None


def _find_table_header(
    rows: list[OcrRow],
) -> tuple[
    int,
    OcrRow,
    dict[str, OcrLine],
] | None:
    best = None

    for start, first_row in enumerate(rows):
        first_height = median(
            line.height
            for line in first_row.lines
        )

        # Baris yang terlalu jauh secara vertikal tidak boleh
        # dianggap sebagai bagian dari header yang sama.
        max_header_span = max(
            48.0,
            min(
                90.0,
                first_height * 2.8,
            ),
        )

        cluster = [
            row
            for row in rows[start:start + 4]
            if (
                row.page == first_row.page
                and 0 <= (
                    row.cy - first_row.cy
                ) <= max_header_span
            )
        ]

        roles: dict[str, OcrLine] = {}

        for row in cluster:
            for line in row.lines:
                role = _header_role(
                    line.text
                )

                if (
                    role
                    and role not in roles
                ):
                    roles[role] = line

        score = len(roles)

        has_name = (
            "name" in roles
            or "description" in roles
        )

        has_money = (
            "price" in roles
            or "subtotal" in roles
        )

        if (
            not has_name
            or not has_money
            or score < 3
        ):
            continue

        synthetic_row = OcrRow(
            page=first_row.page,
            lines=list(roles.values()),
        )

        candidate = (
            score,
            synthetic_row,
            roles,
        )

        if (
            best is None
            or score > best[0]
        ):
            best = candidate

    return best


def _field(
    value: Any,
    confidence: float | None = None,
    source: str | None = None,
) -> dict[str, Any]:
    return {
        "value": value,
        "confidence": confidence,
        "source": source,
    }


def _derived_confidence(
    *values: float | None,
    factor: float = 0.85,
) -> float | None:
    valid = [
        float(value)
        for value in values
        if value is not None
    ]

    if not valid:
        return None

    return min(valid) * factor


def _extract_qty_marker(
    text: str,
) -> tuple[
    float | None,
    float | None,
]:
    cleaned_text, had_barcode = (
        _remove_barcode_tokens(text)
    )

    patterns = [
        re.compile(
            rf"(?i)(?:QTY|OTY|JUMLAH)?"
            rf"\s*[:=]?\s*"
            rf"(\d{{1,5}}(?:[.,]\d+)?)"
            rf"\s*{RECEIPT_UNIT_PATTERN}\s*"
            rf"(?:X|@)\s*"
            rf"([\d\s.,]+)?"
        ),
        re.compile(
            r"(?i)(?:QTY|OTY|JUMLAH)?"
            r"\s*[:=]?\s*"
            r"(\d{1,5}(?:[.,]\d+)?)"
            r"\s+(?:X|@)\s+"
            r"([\d\s.,]+)?"
        ),
    ]

    if had_barcode:
        patterns.append(
            re.compile(
                r"(?i)^[\s,;:-]*"
                r"(\d{1,5}(?:[.,]\d+)?)"
                r"\s*[:=]\s*"
                r"([\d\s.,]+)?"
            )
        )

    for pattern in patterns:
        match = pattern.search(
            cleaned_text
        )

        if not match:
            continue

        quantity = parse_number(
            match.group(1)
        )

        price: float | None = None

        if match.group(2):
            # Jika harga satuan dan subtotal berada pada
            # baris yang sama, ambil nominal pertama saja.
            formatted = (
                _formatted_money_values(
                    match.group(2)
                )
            )

            if formatted:
                price = formatted[0]
            else:
                first_number = re.search(
                    r"\d[\d\s.,]*",
                    match.group(2),
                )

                if first_number:
                    price = parse_number(
                        first_number.group(0)
                    )

        if (
            quantity is not None
            and 0
            < quantity
            <= MAX_PLAUSIBLE_QUANTITY
        ):
            if (
                price is not None
                and price <= 0
            ):
                price = None

            return quantity, price

    return None, None

def _extract_items(
    rows: list[OcrRow],
) -> list[dict[str, Any]]:
    header_result = _find_table_header(rows)

    if header_result is None:
        return []

    _, header_row, roles = header_result

    centers = {
        role: line.cx
        for role, line in roles.items()
    }

    ordered_centers = sorted(
        centers.items(),
        key=lambda item: item[1],
    )

    def assign_role(line: OcrLine) -> str:
        return min(
            ordered_centers,
            key=lambda item: abs(line.cx - item[1]),
        )[0]

    header_bottom = max(
        line.y2
        for line in header_row.lines
    )

    page_rows = [
        row
        for row in rows
        if row.page == header_row.page
        and row.cy > header_bottom
    ]

    items: list[dict[str, Any]] = []

    for index, row in enumerate(page_rows):
        if items and any(
            _label_kind(line.text) in {
                "subtotal",
                "tax",
                "total",
            }
            for line in row.lines
        ):
            break

        cells: dict[str, list[OcrLine]] = {}

        for line in row.lines:
            cells.setdefault(
                assign_role(line),
                [],
            ).append(line)

        name_lines = (
            cells.get("name")
            or cells.get("description")
            or []
        )

        name = " ".join(
            line.text
            for line in sorted(
                name_lines,
                key=lambda item: item.x1,
            )
        ).strip()

        name = re.sub(
            r"^\s*\d+[.)-]\s*",
            "",
            name,
        ).strip()

        name_confidence = min(
            (line.confidence for line in name_lines),
            default=None,
        )

        alpha_count = sum(
            character.isalpha()
            for character in name
        )

        qty_line = (cells.get("qty") or [None])[0]
        unit_line = (cells.get("unit") or [None])[0]
        price_line = (cells.get("price") or [None])[0]
        subtotal_line = (cells.get("subtotal") or [None])[0]

        unit = _extract_unit(
            unit_line.text
            if unit_line
            else (qty_line.text if qty_line else row.text)
        )

        unit_confidence = (
            unit_line.confidence
            if unit_line
            else (qty_line.confidence if qty_line else None)
        )

        unit_source = "ocr" if unit is not None else None

        quantity = (
            parse_number(qty_line.text)
            if qty_line
            else None
        )

        price = (
            _money_value(price_line)
            if price_line
            else None
        )

        subtotal = (
            _money_value(subtotal_line)
            if subtotal_line
            else None
        )

        qty_confidence = (
            qty_line.confidence
            if qty_line
            else None
        )

        price_confidence = (
            price_line.confidence
            if price_line
            else None
        )

        subtotal_confidence = (
            subtotal_line.confidence
            if subtotal_line
            else None
        )

        qty_source = "ocr" if quantity is not None else None
        price_source = "ocr" if price is not None else None
        subtotal_source = "ocr" if subtotal is not None else None

        if alpha_count < 3:
            continue

        if not any(
            value is not None
            for value in (
                quantity,
                price,
                subtotal,
            )
        ):
            continue

        if index + 1 < len(page_rows):
            next_row = page_rows[index + 1]

            if not any(
                _label_kind(line.text)
                for line in next_row.lines
            ):
                next_quantity, next_price = (
                    _extract_qty_marker(next_row.text)
                )

                adjacent_confidence = min(
                    (
                        line.confidence
                        for line in next_row.lines
                    ),
                    default=name_confidence,
                )

                if quantity is None and next_quantity is not None:
                    quantity = next_quantity
                    qty_confidence = adjacent_confidence
                    qty_source = "ocr_adjacent"

                if unit is None and next_quantity is not None:
                    adjacent_unit = _extract_unit(next_row.text)

                    if adjacent_unit is not None:
                        unit = adjacent_unit
                        unit_confidence = adjacent_confidence
                        unit_source = "ocr_adjacent"

                if price is None and next_price is not None and next_price > 0:
                    price = next_price
                    price_confidence = adjacent_confidence
                    price_source = "ocr_adjacent"

        if (
            quantity is not None
            and price is not None
            and subtotal is None
        ):
            subtotal = quantity * price
            subtotal_confidence = _derived_confidence(
                qty_confidence,
                price_confidence,
                factor=0.90,
            )
            subtotal_source = "derived"

        if (
            quantity is not None
            and subtotal is not None
            and price is None
            and quantity != 0
        ):
            price = subtotal / quantity
            price_confidence = _derived_confidence(
                qty_confidence,
                subtotal_confidence,
            )
            price_source = "derived"

        if (
            price is not None
            and subtotal is not None
            and quantity is None
            and price != 0
        ):
            ratio = subtotal / price
            rounded_ratio = round(ratio)

            if (
                abs(ratio - rounded_ratio) < 0.02
                and 0 < rounded_ratio < 100000
            ):
                quantity = float(rounded_ratio)
                qty_confidence = _derived_confidence(
                    price_confidence,
                    subtotal_confidence,
                )
                qty_source = "derived"

        if (
            quantity is not None
            and abs(quantity - round(quantity)) < 1e-9
        ):
            quantity = int(round(quantity))

        numeric_count = sum(
            value is not None
            for value in (
                quantity,
                price,
                subtotal,
            )
        )

        # Item dengan satu subtotal yang jelas tetap dipertahankan.
        # Qty dan harga dapat dipulihkan dari total dokumen.
        if numeric_count < 2 and subtotal is None:
            continue

        items.append({
            "name": _field(
                name,
                name_confidence,
                "ocr_table",
            ),
            "qty": _field(
                quantity,
                qty_confidence,
                qty_source,
            ),
            "unit": _field(
                unit,
                unit_confidence,
                unit_source,
            ),
            "price": _field(
                price,
                price_confidence,
                price_source,
            ),
            "subtotal": _field(
                subtotal,
                subtotal_confidence,
                subtotal_source,
            ),
        })

    unique_items: list[dict[str, Any]] = []
    seen: set[tuple[Any, ...]] = set()

    for item in items:
        key = (
            str(item["name"]["value"]).strip().upper(),
            item["qty"]["value"],
            item["subtotal"]["value"],
        )

        if key in seen:
            continue

        seen.add(key)
        unique_items.append(item)

    return unique_items


def _extract_tax_rate(
    lines: list[OcrLine],
    subtotal: ExtractedValue,
    tax_amount: ExtractedValue,
) -> ExtractedValue:
    for line in lines:
        if _label_kind(line.text) != "tax":
            continue

        match = re.search(
            r"(\d{1,2}(?:[.,]\d+)?)\s*%",
            line.text,
        )

        if match:
            return ExtractedValue(
                value=parse_number(match.group(1)),
                confidence=line.confidence,
                source="ocr",
            )

    if (
        subtotal.value not in (None, 0)
        and tax_amount.value is not None
    ):
        rate = (
            float(tax_amount.value)
            / float(subtotal.value)
            * 100
        )

        if 0 < rate <= 100:
            return ExtractedValue(
                value=round(rate, 4),
                confidence=_derived_confidence(
                    subtotal.confidence,
                    tax_amount.confidence,
                ),
                source="derived",
            )

    return ExtractedValue()


def _item_sequence_match(
    text: str,
) -> re.Match[str] | None:
    match = ITEM_SEQUENCE_PATTERN.match(
        text.strip()
    )

    if match is None:
        return None

    sequence = int(
        match.group(1)
    )

    remainder = (
        match.group(2)
        .strip()
    )

    if not 1 <= sequence <= 300:
        return None

    if (
        not remainder
        or _parse_date(text)
    ):
        return None

    if (
        sum(
            character.isalpha()
            for character in remainder
        ) < 2
        and not re.search(
            r"\d{4,}",
            remainder,
        )
    ):
        return None

    return match


def _extract_numbered_receipt_items(
    rows: list[OcrRow],
) -> list[dict[str, Any]]:
    """
    Membaca thermal receipt yang itemnya diawali
    nomor 1., 2., 3., dan seterusnya.

    Nomor item berikutnya tidak boleh menjadi harga.
    """

    def row_confidence(
        row: OcrRow,
    ) -> float | None:
        return min(
            (
                line.confidence
                for line in row.lines
            ),
            default=None,
        )

    def clean_description(
        text: str,
    ) -> str:
        value = text.strip()

        match = _item_sequence_match(
            value
        )

        if match is not None:
            value = (
                match.group(2)
                .strip()
            )

        value, _ = _remove_barcode_tokens(
            value
        )

        # Menghapus SKU pada awal baris.
        value = re.sub(
            r"^\s*\d{4,8}"
            r"\s*[:;=-]+\s*",
            "",
            value,
        )

        value = re.sub(
            r"^\s*[-:;,]+\s*",
            "",
            value,
        )

        return re.sub(
            r"\s+",
            " ",
            value,
        ).strip()

    def is_description(
        text: str,
    ) -> bool:
        if not text:
            return False

        keyword = _keyword_text(
            text
        )

        if any(
            term in keyword
            for term in (
                "KASIR",
                "CASHIER",
                "ID CUST",
                "CUSTOMER",
                "PELANGGAN",
                "TRANSAKSI",
                "TOTAL",
                "BAYAR",
                "PEMBAYARAN",
                "QRIS",
                "UANG TUNAI",
                "REWARD",
                "TGL TRX",
                "TANGGAL",
                "NPWP",
                "TEL",
                "SHOPEE",
                "TOKOPEDIA",
                "INSTAGRAM",
                "KRITIK",
                "SARAN",
                "POT ",
                "DISKON",
                "DISC ",
                "PROMO",
            )
        ):
            return False

        quantity, _ = (
            _extract_qty_marker(
                text
            )
        )

        if quantity is not None:
            return False

        if _formatted_money_values(
            text
        ):
            return False

        letters = sum(
            character.isalpha()
            for character in text
        )

        if letters >= 3:
            return True

        # Kelanjutan nama seperti 7 L atau 500 ML.
        return bool(
            re.fullmatch(
                r"\d+(?:[.,]\d+)?"
                r"\s*(?:ML|L|GR|G|KG|CM|MM|M)",
                text,
                re.IGNORECASE,
            )
        )

    starts: list[
        tuple[
            int,
            int,
        ]
    ] = []

    previous_sequence = 0

    for index, row in enumerate(
        rows
    ):
        match = _item_sequence_match(
            row.text
        )

        if match is None:
            continue

        sequence = int(
            match.group(1)
        )

        if (
            starts
            and sequence
            < previous_sequence
        ):
            continue

        starts.append((
            index,
            sequence,
        ))

        previous_sequence = sequence

    if not starts:
        return []

    items: list[
        dict[str, Any]
    ] = []

    for position, (
        start_index,
        _,
    ) in enumerate(starts):
        end_index = (
            starts[position + 1][0]
            if position + 1
            < len(starts)
            else len(rows)
        )

        block: list[OcrRow] = []

        for row in rows[
            start_index:end_index
        ]:
            if any(
                _label_kind(
                    line.text
                ) in {
                    "subtotal",
                    "tax",
                    "total",
                }
                for line in row.lines
            ):
                break

            keyword = _keyword_text(
                row.text
            )

            if any(
                keyword.startswith(
                    label
                )
                for label in (
                    "DETAIL PEMBAYARAN",
                    "QRIS",
                    "UANG TUNAI",
                    "REWARD",
                    "TGL TRX",
                )
            ):
                break

            block.append(row)

        if not block:
            continue

        qty: float | None = None
        inline_price: float | None = None
        qty_row_index: int | None = None
        qty_confidence: float | None = None
        unit: str | None = None

        for local_index, row in enumerate(
            block
        ):
            (
                candidate_qty,
                candidate_price,
            ) = _extract_qty_marker(
                row.text
            )

            if candidate_qty is None:
                continue

            qty = candidate_qty

            inline_price = (
                candidate_price
                if (
                    candidate_price
                    is not None
                    and candidate_price > 0
                )
                else None
            )

            qty_row_index = (
                local_index
            )

            qty_confidence = (
                row_confidence(row)
            )

            unit = _extract_unit(row.text)

            break

        if (
            qty is None
            or qty_row_index is None
        ):
            continue

        descriptions: list[
            tuple[
                int,
                str,
                float | None,
            ]
        ] = []

        for local_index, row in enumerate(
            block[:qty_row_index]
        ):
            text = clean_description(
                row.text
            )

            if is_description(text):
                descriptions.append((
                    local_index,
                    text,
                    row_confidence(row),
                ))

        # Baris pertama biasanya berisi SKU dan merek pendek.
        # Jika ada deskripsi setelahnya, gunakan deskripsi tersebut.
        continuation = [
            value
            for value in descriptions
            if value[0] > 0
        ]

        selected = (
            continuation
            if continuation
            else descriptions
        )[:3]

        name = re.sub(
            r"\s+",
            " ",
            " ".join(
                value[1]
                for value in selected
            ),
        ).strip()

        if sum(
            character.isalpha()
            for character in name
        ) < 3:
            continue

        name_confidence = min(
            (
                value[2]
                for value in selected
                if value[2] is not None
            ),
            default=None,
        )

        amounts: list[
            tuple[
                float,
                float | None,
                int,
            ]
        ] = []

        for local_index, row in enumerate(
            block
        ):
            for line in row.lines:
                for value in (
                    _formatted_money_values(
                        line.text
                    )
                ):
                    amounts.append((
                        value,
                        line.confidence,
                        local_index,
                    ))

        price = inline_price

        price_confidence = (
            qty_confidence
            if inline_price is not None
            else None
        )

        price_source = (
            "ocr_qty_line"
            if inline_price is not None
            else None
        )

        subtotal: float | None = None

        subtotal_confidence: (
            float | None
        ) = None

        subtotal_source: str | None = None

        if price is not None:
            expected = qty * price

            if amounts:
                closest = min(
                    amounts,
                    key=lambda value:
                        abs(
                            value[0]
                            - expected
                        ),
                )

                if _amount_is_close(
                    closest[0],
                    expected,
                    tolerance=0.04,
                ):
                    subtotal = closest[0]

                    subtotal_confidence = (
                        closest[1]
                    )

                    subtotal_source = (
                        "ocr_item_total"
                    )

            if subtotal is None:
                subtotal = expected

                subtotal_confidence = (
                    _derived_confidence(
                        qty_confidence,
                        price_confidence,
                        factor=0.82,
                    )
                )

                subtotal_source = (
                    "derived"
                )

        else:
            best_pair = None

            for first in amounts:
                for second in amounts:
                    if (
                        second[2]
                        < first[2]
                        or second[0]
                        < first[0]
                    ):
                        continue

                    error = abs(
                        qty * first[0]
                        - second[0]
                    ) / max(
                        second[0],
                        1.0,
                    )

                    if (
                        best_pair is None
                        or error
                        < best_pair[0]
                    ):
                        best_pair = (
                            error,
                            first,
                            second,
                        )

            if (
                best_pair is not None
                and best_pair[0] <= 0.04
            ):
                price = best_pair[1][0]

                price_confidence = (
                    best_pair[1][1]
                )

                price_source = (
                    "ocr_arithmetic_pair"
                )

                subtotal = (
                    best_pair[2][0]
                )

                subtotal_confidence = (
                    best_pair[2][1]
                )

                subtotal_source = (
                    "ocr_item_total"
                )

            elif len(amounts) == 1:
                (
                    value,
                    confidence,
                    _,
                ) = amounts[0]

                if qty == 1:
                    price = value
                    subtotal = value
                else:
                    subtotal = value
                    price = value / qty

                price_confidence = (
                    confidence
                )

                subtotal_confidence = (
                    confidence
                )

                price_source = (
                    "derived_single_amount"
                )

                subtotal_source = (
                    "ocr_item_total"
                )

        if (
            price is None
            or subtotal is None
            or price <= 0
            or subtotal <= 0
        ):
            continue

        normalized_qty: float | int = (
            qty
        )

        if abs(
            qty - round(qty)
        ) < 1e-9:
            normalized_qty = int(
                round(qty)
            )

        items.append({
            "name": _field(
                name,
                name_confidence,
                "ocr_numbered_block",
            ),

            "qty": _field(
                normalized_qty,
                qty_confidence,
                "ocr_numbered_block",
            ),

            "unit": _field(
                unit,
                qty_confidence if unit is not None else None,
                "ocr_numbered_block" if unit is not None else None,
            ),

            "price": _field(
                price,
                price_confidence,
                price_source,
            ),

            "subtotal": _field(
                subtotal,
                subtotal_confidence,
                subtotal_source,
            ),
        })

    return items


def _split_receipt_regions(
    lines: list[OcrLine],
) -> list[list[OcrLine]]:
    """
    Memisahkan dua struk yang ditempel
    berdampingan dalam satu halaman.
    """
    regions: list[
        list[OcrLine]
    ] = []

    for page in sorted({
        line.page
        for line in lines
    }):
        page_lines = [
            line
            for line in lines
            if line.page == page
        ]

        if len(page_lines) < 16:
            regions.append(page_lines)
            continue

        min_x = min(
            line.x1
            for line in page_lines
        )

        max_x = max(
            line.x2
            for line in page_lines
        )

        min_y = min(
            line.y1
            for line in page_lines
        )

        max_y = max(
            line.y2
            for line in page_lines
        )

        width = max(
            1.0,
            max_x - min_x,
        )

        height = max(
            1.0,
            max_y - min_y,
        )

        cuts = sorted({
            edge
            for line in page_lines
            for edge in (
                line.x1,
                line.x2,
            )
            if (
                min_x + width * 0.30
                <= edge
                <= min_x + width * 0.70
            )
        })

        best: tuple[
            float,
            list[OcrLine],
            list[OcrLine],
        ] | None = None

        for cut in cuts:
            left = [
                line
                for line in page_lines
                if line.x2 <= cut
            ]

            right = [
                line
                for line in page_lines
                if line.x1 >= cut
            ]

            crossing = [
                line
                for line in page_lines
                if line.x1 < cut < line.x2
            ]

            if (
                len(left) < 8
                or len(right) < 8
            ):
                continue

            if len(crossing) > max(
                2,
                int(
                    len(page_lines)
                    * 0.04
                ),
            ):
                continue

            gutter = (
                min(
                    line.x1
                    for line in right
                )
                - max(
                    line.x2
                    for line in left
                )
            )

            if gutter < max(
                36.0,
                width * 0.055,
            ):
                continue

            left_span = (
                max(
                    line.y2
                    for line in left
                )
                - min(
                    line.y1
                    for line in left
                )
            )

            right_span = (
                max(
                    line.y2
                    for line in right
                )
                - min(
                    line.y1
                    for line in right
                )
            )

            if (
                left_span
                < height * 0.25
                or right_span
                < height * 0.25
            ):
                continue

            score = (
                gutter
                + min(
                    len(left),
                    len(right),
                ) * 0.5
                - len(crossing) * 20.0
            )

            if (
                best is None
                or score > best[0]
            ):
                best = (
                    score,
                    left,
                    right,
                )

        if best is None:
            regions.append(page_lines)
        else:
            regions.extend((
                best[1],
                best[2],
            ))

    return [
        region
        for region in regions
        if region
    ]


def _extract_receipt_items_fallback(
    rows: list[OcrRow],
) -> list[dict[str, Any]]:
    """
    Membaca struk tanpa header tabel.

    Mendukung format terpisah seperti:

        JOYKO GUNTING SC-33
        3 PCS X
        10.000,00
        30.000,00
    """
    items: list[
        dict[str, Any]
    ] = []

    unit_only = re.compile(
        r"(?i)^"
        r"(?:PCS?|PACK|BKS|BOX|RG|"
        r"BTL|BOTOL|KRT|LEMBAR|LBR|"
        r"BUAH|UNIT|SET|ROLL|ROL|"
        r"KEPING|DUS|KOTAK|JRG|"
        r"RIM|LSN)"
        r"$"
    )

    amount_only = re.compile(
        r"^[\sRP.,0-9OGQIl|:-]+$",
        re.IGNORECASE,
    )

    def row_confidence(
        row: OcrRow,
    ) -> float | None:
        return min(
            (
                line.confidence
                for line in row.lines
            ),
            default=None,
        )

    def amounts(
        row: OcrRow,
    ) -> list[
        tuple[
            float,
            float | None,
        ]
    ]:
        result: list[
            tuple[
                float,
                float | None,
            ]
        ] = []

        for line in row.lines:
            value = _money_value(line)

            if (
                value is not None
                and value > 0
            ):
                result.append((
                    value,
                    line.confidence,
                ))

        return result

    for index, row in enumerate(rows):
        qty, inline_price = (
            _extract_qty_marker(
                row.text
            )
        )

        if qty is None:
            continue

        unit = _extract_unit(row.text)

        name_rows: list[OcrRow] = []
        lower_row = row

        for offset in range(1, 7):
            previous_index = (
                index - offset
            )

            if previous_index < 0:
                break

            previous = rows[
                previous_index
            ]

            gap = (
                lower_row.cy
                - previous.cy
            )

            gap_limit = max(
                50.0,
                max(
                    line.height
                    for line in (
                        previous.lines
                        + lower_row.lines
                    )
                ) * 2.8,
            )

            if gap > gap_limit:
                break

            lower_row = previous

            previous_qty, _ = (
                _extract_qty_marker(
                    previous.text
                )
            )

            if previous_qty is not None:
                break

            if any(
                _label_kind(line.text)
                for line
                in previous.lines
            ):
                break

            text = previous.text.strip()

            digits = re.sub(
                r"\D",
                "",
                text,
            )

            if (
                not text
                or unit_only.fullmatch(
                    text
                )
                or len(digits) >= 8
            ):
                continue

            if (
                amount_only.fullmatch(
                    text
                )
                or re.fullmatch(
                    r"\d{1,3}[.)-]?",
                    text,
                )
            ):
                continue

            if sum(
                character.isalpha()
                for character in text
            ) < 3:
                continue

            name_rows.append(previous)

            if len(name_rows) >= 2:
                break

        name_rows.reverse()

        name = re.sub(
            r"\s+",
            " ",
            " ".join(
                item.text.strip()
                for item in name_rows
            ),
        ).strip()

        name = re.sub(
            r"^\s*\d+[.)-]\s*",
            "",
            name,
        ).strip()

        if sum(
            character.isalpha()
            for character in name
        ) < 3:
            continue

        values: list[
            tuple[
                float,
                float | None,
            ]
        ] = []

        if (
            inline_price is not None
            and inline_price > 0
        ):
            values.append((
                inline_price,
                row_confidence(row),
            ))

        for offset in range(1, 5):
            next_index = index + offset

            if next_index >= len(rows):
                break

            next_row = rows[next_index]

            if any(
                _label_kind(line.text)
                for line
                in next_row.lines
            ):
                break

            next_qty, _ = (
                _extract_qty_marker(
                    next_row.text
                )
            )

            if next_qty is not None:
                break

            values.extend(
                amounts(next_row)
            )

            if len(values) >= 2:
                break

        price: float | None = None
        subtotal: float | None = None

        price_confidence: (
            float | None
        ) = None

        subtotal_confidence: (
            float | None
        ) = None

        subtotal_source = "derived"

        if len(values) >= 2:
            best_pair: tuple[
                float,
                tuple[
                    float,
                    float | None,
                ],
                tuple[
                    float,
                    float | None,
                ],
            ] | None = None

            for (
                first_index,
                first,
            ) in enumerate(
                values[:4]
            ):
                for second in values[
                    first_index + 1:
                    first_index + 4
                ]:
                    error = abs(
                        qty * first[0]
                        - second[0]
                    ) / max(
                        second[0],
                        1.0,
                    )

                    candidate = (
                        error,
                        first,
                        second,
                    )

                    if (
                        best_pair is None
                        or error
                        < best_pair[0]
                    ):
                        best_pair = candidate

            if best_pair is not None:
                (
                    _,
                    selected_price,
                    selected_subtotal,
                ) = best_pair

                (
                    price,
                    price_confidence,
                ) = selected_price

                (
                    subtotal,
                    subtotal_confidence,
                ) = selected_subtotal

                subtotal_source = (
                    "ocr_fallback_block"
                )

        elif len(values) == 1:
            (
                price,
                price_confidence,
            ) = values[0]

        if (
            price is None
            or price <= 0
        ):
            continue

        expected_subtotal = (
            qty * price
        )

        if (
            subtotal is None
            or subtotal <= 0
            or not _amount_is_close(
                expected_subtotal,
                subtotal,
                tolerance=0.03,
            )
        ):
            subtotal = expected_subtotal

            subtotal_confidence = (
                _derived_confidence(
                    row_confidence(row),
                    price_confidence,
                    factor=0.75,
                )
            )

            subtotal_source = "derived"

        normalized_qty: float | int = (
            qty
        )

        if abs(
            qty - round(qty)
        ) < 1e-9:
            normalized_qty = int(
                round(qty)
            )

        items.append({
            "name": _field(
                name,
                min(
                    (
                        row_confidence(item)
                        for item
                        in name_rows
                        if row_confidence(
                            item
                        ) is not None
                    ),
                    default=None,
                ),
                "ocr_fallback_block",
            ),

            "qty": _field(
                normalized_qty,
                row_confidence(row),
                "ocr_fallback_block",
            ),

            "unit": _field(
                unit,
                row_confidence(row) if unit is not None else None,
                "ocr_fallback_block" if unit is not None else None,
            ),

            "price": _field(
                price,
                price_confidence,
                "ocr_fallback_block",
            ),

            "subtotal": _field(
                subtotal,
                subtotal_confidence,
                subtotal_source,
            ),
        })

    unique_items: list[
        dict[str, Any]
    ] = []

    seen: set[
        tuple[Any, ...]
    ] = set()

    for item in items:
        key = (
            str(
                item["name"]["value"]
            ).upper().strip(),
            item["qty"]["value"],
            item["subtotal"]["value"],
        )

        if key in seen:
            continue

        seen.add(key)
        unique_items.append(item)

    return unique_items


def _extract_receipt_items_by_regions(
    lines: list[OcrLine],
) -> list[dict[str, Any]]:
    items: list[
        dict[str, Any]
    ] = []

    for region in (
        _split_receipt_regions(lines)
    ):
        region_rows = _build_rows(
            region
        )

        items.extend(
            _extract_receipt_items_fallback(
                region_rows
            )
        )

    unique_items: list[
        dict[str, Any]
    ] = []

    seen: set[
        tuple[Any, ...]
    ] = set()

    for item in items:
        key = (
            str(
                item["name"]["value"]
            ).upper().strip(),
            item["qty"]["value"],
            item["subtotal"]["value"],
        )

        if key in seen:
            continue

        seen.add(key)
        unique_items.append(item)

    return unique_items


def _numeric_field(
    item: dict[str, Any],
    name: str,
) -> float | None:
    field_data = item.get(name) or {}
    value = field_data.get("value")

    try:
        if value is None:
            return None

        return float(value)
    except (TypeError, ValueError):
        return None


def _amount_is_close(
    left: float,
    right: float,
    tolerance: float = 0.05,
) -> bool:
    permitted_difference = max(
        1.0,
        abs(right) * tolerance,
    )

    return abs(
        left - right
    ) <= permitted_difference


def _set_item_field(
    item: dict[str, Any],
    name: str,
    value: float | int | None,
    confidence: float | None,
    source: str,
) -> None:
    item[name] = _field(
        value,
        confidence,
        source,
    )


def _reconcile_totals(
    subtotal: ExtractedValue,
    tax_amount: ExtractedValue,
    total: ExtractedValue,
) -> tuple[
    ExtractedValue,
    ExtractedValue,
    ExtractedValue,
]:
    subtotal_value = (
        float(subtotal.value)
        if subtotal.value is not None
        else None
    )

    tax_value = (
        float(tax_amount.value)
        if tax_amount.value is not None
        else None
    )

    total_value = (
        float(total.value)
        if total.value is not None
        else None
    )

    if (
        subtotal_value is not None
        and total_value is not None
    ):
        if (
            tax_value is None
            and _amount_is_close(
                subtotal_value,
                total_value,
                tolerance=0.01,
            )
        ):
            tax_amount = ExtractedValue(
                value=0,
                confidence=_derived_confidence(
                    subtotal.confidence,
                    total.confidence,
                    factor=0.85,
                ),
                source="reconciled_zero",
            )

        elif (
            tax_value is not None
            and not _amount_is_close(
                subtotal_value + tax_value,
                total_value,
            )
        ):
            # Jika subtotal sama dengan total, nominal yang
            # dianggap pajak kemungkinan hasil OCR yang salah.
            if _amount_is_close(
                subtotal_value,
                total_value,
                tolerance=0.01,
            ):
                tax_amount = ExtractedValue(
                    value=0,
                    confidence=_derived_confidence(
                        subtotal.confidence,
                        total.confidence,
                        factor=0.80,
                    ),
                    source="rejected_inconsistent_tax",
                )
            else:
                tax_amount = ExtractedValue(
                    value=None,
                    confidence=None,
                    source="rejected_inconsistent_tax",
                )

    return (
        subtotal,
        tax_amount,
        total,
    )


def _reconcile_items_with_total(
    items: list[dict[str, Any]],
    subtotal: ExtractedValue,
    tax_amount: ExtractedValue,
    total: ExtractedValue,
) -> list[dict[str, Any]]:
    if not items:
        return items

    subtotal_value = (
        float(subtotal.value)
        if subtotal.value is not None
        else None
    )

    total_value = (
        float(total.value)
        if total.value is not None
        else None
    )

    tax_value = (
        float(tax_amount.value)
        if tax_amount.value is not None
        else None
    )

    # Subtotal merupakan anchor utama.
    anchor = subtotal_value

    # Jika tidak ada subtotal dan tidak ada pajak,
    # gunakan total sebagai anchor.
    if (
        anchor is None
        and total_value is not None
        and (
            tax_value is None
            or abs(tax_value) < 1e-9
        )
    ):
        anchor = total_value

    for item in items:
        qty = _numeric_field(
            item,
            "qty",
        )

        price = _numeric_field(
            item,
            "price",
        )

        item_subtotal = _numeric_field(
            item,
            "subtotal",
        )

        qty_confidence = (
            item.get("qty") or {}
        ).get("confidence")

        price_confidence = (
            item.get("price") or {}
        ).get("confidence")

        subtotal_confidence = (
            item.get("subtotal") or {}
        ).get("confidence")

        if (
            qty is not None
            and (
                qty <= 0
                or qty > MAX_PLAUSIBLE_QUANTITY
            )
        ):
            qty = None

            _set_item_field(
                item,
                "qty",
                None,
                None,
                "rejected_plausibility",
            )

        if (
            qty is not None
            and price is not None
            and item_subtotal is not None
        ):
            expected = qty * price

            if not _amount_is_close(
                expected,
                item_subtotal,
            ):
                ratio = (
                    item_subtotal / price
                    if price > 0
                    else None
                )

                if (
                    ratio is not None
                    and 0
                    < round(ratio)
                    <= MAX_PLAUSIBLE_QUANTITY
                    and abs(
                        ratio - round(ratio)
                    ) < 0.02
                ):
                    qty = float(
                        round(ratio)
                    )

                    _set_item_field(
                        item,
                        "qty",
                        int(qty),
                        _derived_confidence(
                            price_confidence,
                            subtotal_confidence,
                            factor=0.80,
                        ),
                        "reconciled_subtotal",
                    )

                elif qty > 0:
                    price = (
                        item_subtotal / qty
                    )

                    _set_item_field(
                        item,
                        "price",
                        price,
                        _derived_confidence(
                            qty_confidence,
                            subtotal_confidence,
                            factor=0.75,
                        ),
                        "reconciled_subtotal",
                    )

    # Koreksi otomatis dari total hanya dilakukan
    # untuk dokumen dengan tepat satu item.
    if (
        len(items) != 1
        or anchor is None
        or anchor <= 0
    ):
        return items

    item = items[0]

    qty = _numeric_field(
        item,
        "qty",
    )

    price = _numeric_field(
        item,
        "price",
    )

    item_subtotal = _numeric_field(
        item,
        "subtotal",
    )

    item_confidences = [
        (
            item.get(name) or {}
        ).get("confidence")
        for name in (
            "name",
            "qty",
            "price",
            "subtotal",
        )
    ]

    confidence = _derived_confidence(
        *item_confidences,
        total.confidence,
        subtotal.confidence,
        factor=0.78,
    )

    if (
        item_subtotal is not None
        and _amount_is_close(
            item_subtotal,
            anchor,
        )
    ):
        if (
            qty is None
            and (
                price is None
                or price <= 0
            )
        ):
            qty = 1.0
            price = anchor

        elif (
            qty is None
            and price is not None
            and price > 0
        ):
            ratio = anchor / price
            rounded = round(ratio)

            if (
                0
                < rounded
                <= MAX_PLAUSIBLE_QUANTITY
                and abs(
                    ratio - rounded
                ) < 0.02
            ):
                qty = float(rounded)

        elif (
            price is None
            and qty is not None
            and qty > 0
        ):
            price = anchor / qty

        item_subtotal = anchor

    elif (
        qty is not None
        and price is not None
    ):
        expected = qty * price

        if _amount_is_close(
            expected,
            anchor,
        ):
            item_subtotal = anchor

        elif _amount_is_close(
            price,
            anchor,
        ):
            qty = 1.0
            item_subtotal = anchor

        elif expected > anchor * 5:
            # Kasus New Agung:
            # 94.029 × 80.000 tidak mungkin jika
            # total dokumen hanya 80.000.
            qty = 1.0
            price = anchor
            item_subtotal = anchor

    elif (
        price is not None
        and _amount_is_close(
            price,
            anchor,
        )
    ):
        qty = 1.0
        item_subtotal = anchor

    elif qty == 1:
        price = anchor
        item_subtotal = anchor

    elif item_subtotal is None:
        qty = 1.0
        price = anchor
        item_subtotal = anchor

    if qty is not None:
        normalized_qty: float | int = qty

        if abs(
            qty - round(qty)
        ) < 1e-9:
            normalized_qty = int(
                round(qty)
            )

        _set_item_field(
            item,
            "qty",
            normalized_qty,
            confidence,
            "reconciled_total",
        )

    if (
        price is not None
        and price > 0
    ):
        _set_item_field(
            item,
            "price",
            price,
            confidence,
            "reconciled_total",
        )

    if (
        item_subtotal is not None
        and item_subtotal > 0
    ):
        _set_item_field(
            item,
            "subtotal",
            item_subtotal,
            confidence,
            "reconciled_total",
        )

    return items


def parse_receipt(
    lines_data: list[dict[str, Any]],
) -> DocumentData:
    lines = _prepare_lines(lines_data)
    rows = _build_rows(lines)

    store_name = _extract_store_name(lines)
    invoice_number = _extract_invoice_number(lines)
    transaction_date = _extract_date_field(lines)

    if transaction_date.value is None:
        transaction_date = (
            _extract_date_from_rows(
                rows
            )
        )

    totals = _extract_labeled_amounts(
        lines,
        rows,
    )

    inline_total = _extract_inline_total(
        lines
    )

    if inline_total.value is not None:
        totals["total"] = inline_total

    # Parser tabel tetap menjadi pilihan pertama.
    items = _extract_items(rows)

    # Struk bernomor seperti Satu Sama diproses
    # sebelum fallback generik.
    if not items:
        items = (
            _extract_numbered_receipt_items(
                rows
            )
        )

    if not items:
        items = (
            _extract_receipt_items_by_regions(
                lines
            )
        )

    subtotal = totals["subtotal"]
    tax_amount = totals["tax"]
    total = totals["total"]

    if (
        subtotal.value is None
        and items
        and all(
            item["subtotal"]["value"] is not None
            for item in items
        )
    ):
        subtotal = ExtractedValue(
            value=sum(
                float(item["subtotal"]["value"])
                for item in items
            ),
            confidence=_derived_confidence(
                *(
                    item["subtotal"]["confidence"]
                    for item in items
                ),
                factor=0.90,
            ),
            source="derived_items",
        )

    if (
        total.value is None
        and subtotal.value is not None
        and tax_amount.value is not None
    ):
        total = ExtractedValue(
            value=(
                float(subtotal.value)
                + float(tax_amount.value)
            ),
            confidence=_derived_confidence(
                subtotal.confidence,
                tax_amount.confidence,
                factor=0.90,
            ),
            source="derived",
        )

    subtotal, tax_amount, total = (
        _reconcile_totals(
            subtotal,
            tax_amount,
            total,
        )
    )

    items = _reconcile_items_with_total(
        items,
        subtotal,
        tax_amount,
        total,
    )

    tax_rate = _extract_tax_rate(
        lines,
        subtotal,
        tax_amount,
    )

    return DocumentData(
        store_name=store_name,
        invoice_no=invoice_number,
        date=transaction_date,
        subtotal=subtotal,
        tax_rate=tax_rate,
        tax_amount=tax_amount,
        total=total,
        items=items,
    )
