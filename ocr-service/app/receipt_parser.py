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


def _parse_date(text: str) -> str | None:
    numeric_patterns = (
        r"\b(\d{1,2})[/-](\d{1,2})[/-](\d{2,4})\b",
        r"\b(\d{4})[/-](\d{1,2})[/-](\d{1,2})\b",
    )

    for index, pattern in enumerate(numeric_patterns):
        for match in re.finditer(pattern, text):
            if index == 0:
                day, month, year = map(int, match.groups())
            else:
                year, month, day = map(int, match.groups())

            if year < 100:
                year += 2000

            try:
                return datetime(
                    year,
                    month,
                    day,
                ).date().isoformat()
            except ValueError:
                continue

    month_pattern = re.compile(
        r"\b(\d{1,2})\s+([A-Za-z]+)\s+(\d{2,4})\b"
    )

    for match in month_pattern.finditer(text):
        day = int(match.group(1))
        month = _month_number(
            match.group(2)
        )
        year = int(match.group(3))

        if month is None:
            continue

        if year < 100:
            year += 2000

        try:
            return datetime(
                year,
                month,
                day,
            ).date().isoformat()
        except ValueError:
            continue

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
    labels = [
        line
        for line in lines
        if _is_label(
            line.text,
            "TANGGAL",
            "DATE",
            "TGL",
        )
    ]

    candidates: list[tuple[OcrLine, str]] = []

    for line in lines:
        date_value = _parse_date(line.text)
        if date_value:
            candidates.append((line, date_value))

    if not candidates:
        return ExtractedValue()

    scored: list[
        tuple[float, OcrLine, str, OcrLine]
    ] = []

    for label in labels:
        for line, date_value in candidates:
            if line.page != label.page:
                continue

            delta_y = line.cy - label.cy

            if delta_y < -label.height:
                continue

            if delta_y > label.height * 4:
                continue

            delta_x = abs(line.cx - label.cx)
            score = (abs(delta_y) * 2) + (delta_x * 0.15)

            scored.append((
                score,
                line,
                date_value,
                label,
            ))

    if scored:
        _, line, value, label = min(
            scored,
            key=lambda item: item[0],
        )

        return ExtractedValue(
            value=value,
            confidence=min(
                line.confidence,
                label.confidence,
            ),
            source="ocr_label",
        )

    line, value = min(
        candidates,
        key=lambda item: (
            item[0].page,
            item[0].cy,
        ),
    )

    return ExtractedValue(
        value=value,
        confidence=line.confidence,
        source="ocr",
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
    explicit_pattern = re.compile(
        r"(?i)\b(?:NO(?:TA)?|NOMOR)"
        r"\.?\s*[:#-]?\s*"
        r"([A-Z0-9][A-Z0-9/-]{1,})\b"
    )

    for line in lines:
        match = explicit_pattern.search(
            line.text
        )

        if not match:
            continue

        value = match.group(1).strip()

        if not re.search(
            r"\d",
            value,
        ):
            continue

        return ExtractedValue(
            value=value,
            confidence=line.confidence,
            source="ocr_explicit_number",
        )

    direct_candidates: list[tuple[OcrLine, str]] = []

    direct_pattern = re.compile(
        r"\bINV(?:OICE)?[\s:#/-]*[A-Z0-9][A-Z0-9/-]{3,}\b",
        re.IGNORECASE,
    )

    for line in lines:
        for match in direct_pattern.finditer(line.text):
            value = match.group(0).strip().replace(" ", "")

            if _looks_like_invoice_value(value):
                direct_candidates.append((line, value))

    if direct_candidates:
        line, value = max(
            direct_candidates,
            key=lambda item: item[0].confidence,
        )

        return ExtractedValue(
            value=value,
            confidence=line.confidence,
            source="ocr_pattern",
        )

    labels = [
        line
        for line in lines
        if _is_invoice_label(line.text)
    ]

    candidates = [
        line
        for line in lines
        if _looks_like_invoice_value(line.text)
    ]

    scored: list[tuple[float, OcrLine, OcrLine]] = []

    for label in labels:
        for candidate in candidates:
            if candidate.page != label.page or candidate is label:
                continue

            delta_y = candidate.cy - label.cy

            if delta_y < -label.height:
                continue

            if delta_y > label.height * 4:
                continue

            if abs(delta_y) <= max(
                label.height,
                candidate.height,
            ):
                x_penalty = (
                    max(0.0, label.x1 - candidate.cx) * 2
                    + abs(candidate.x1 - label.x2) * 0.1
                )
            else:
                x_penalty = abs(
                    candidate.x1 - label.x1
                ) * 0.2

            scored.append((
                (abs(delta_y) * 2) + x_penalty,
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
        value=candidate.text,
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
    unit_pattern = (
        r"(?:PCS?|PACK|BKS|BOX|RG|"
        r"BTL|BOTOL|KRT|LEMBAR|LBR|"
        r"BUAH|UNIT|SET|ROLL|ROL|KEPING)"
    )

    cleaned_text, had_barcode = (
        _remove_barcode_tokens(text)
    )

    patterns = [
        re.compile(
            rf"(?i)(?:QTY|OTY|JUMLAH)?"
            rf"\s*[:=]?\s*"
            rf"(\d{{1,5}}(?:[.,]\d+)?)"
            rf"\s*{unit_pattern}\s*"
            rf"(?:X|@)\s*"
            rf"([\d.,]+)?"
        ),
        re.compile(
            r"(?i)(?:QTY|OTY|JUMLAH)?"
            r"\s*[:=]?\s*"
            r"(\d{1,5}(?:[.,]\d+)?)"
            r"\s+(?:X|@)\s+"
            r"([\d.,]+)?"
        ),
    ]

    # Format sejumlah thermal receipt:
    #
    # BARCODE,QTY : HARGA
    #
    # Contoh hasil OCR New Agung:
    #
    # 8993242594029,1 : 00.000
    #
    # Setelah barcode dibuang:
    #
    # ,1 : 00.000
    if had_barcode:
        patterns.append(
            re.compile(
                r"(?i)^[\s,;:-]*"
                r"(\d{1,5}(?:[.,]\d+)?)"
                r"\s*[:=]\s*"
                r"([\d.,]+)?"
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

        price = (
            parse_number(
                match.group(2)
            )
            if match.group(2)
            else None
        )

        if (
            quantity is not None
            and 0 < quantity <= MAX_PLAUSIBLE_QUANTITY
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
        price_line = (cells.get("price") or [None])[0]
        subtotal_line = (cells.get("subtotal") or [None])[0]

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


def _extract_receipt_items_fallback(
    rows: list[OcrRow],
) -> list[dict[str, Any]]:
    items: list[dict[str, Any]] = []

    for index, row in enumerate(rows):
        qty, price = _extract_qty_marker(row.text)

        if qty is None or price is None:
            continue

        # Cari nama barang di baris sebelumnya
        name = ""
        name_confidence = 0.0
        for offset in range(1, 4):
            prev_index = index - offset
            if prev_index < 0:
                break
            prev_row = rows[prev_index]
            
            # Stop jika menemukan subtotal/total
            if any(_label_kind(line.text) for line in prev_row.lines):
                break
            
            # Stop jika baris ini juga qty x harga
            q, p = _extract_qty_marker(prev_row.text)
            if q is not None and p is not None:
                break
                
            text = prev_row.text.strip()
            if sum(c.isalpha() for c in text) >= 3:
                name = text
                name_confidence = min(
                    line.confidence
                    for line in prev_row.lines
                )
                break

        if not name:
            continue

        # Cari subtotal sesudahnya
        subtotal = None
        subtotal_confidence = 0.0
        
        expected_subtotal = qty * price
        
        for offset in range(1, 4):
            next_index = index + offset
            if next_index >= len(rows):
                break
            next_row = rows[next_index]
            
            # Stop jika ketemu label
            if any(_label_kind(line.text) for line in next_row.lines):
                break
                
            # Stop jika baris ini qty x harga lagi
            q, p = _extract_qty_marker(next_row.text)
            if q is not None and p is not None:
                break
                
            for line in next_row.lines:
                amount = _money_value(line)
                if amount is not None:
                    # Toleransi perhitungan 1%
                    if abs(amount - expected_subtotal) <= max(1.0, expected_subtotal * 0.01):
                        subtotal = amount
                        subtotal_confidence = line.confidence
                        break
                        
            if subtotal is not None:
                break

        if subtotal is None:
            subtotal = expected_subtotal
            subtotal_confidence = _derived_confidence(
                min(line.confidence for line in row.lines),
                name_confidence,
                factor=0.9,
            )

        items.append({
            "name": _field(
                name,
                name_confidence,
                "ocr_fallback",
            ),
            "qty": _field(
                qty,
                min(line.confidence for line in row.lines),
                "ocr_fallback",
            ),
            "price": _field(
                price,
                min(line.confidence for line in row.lines),
                "ocr_fallback",
            ),
            "subtotal": _field(
                subtotal,
                subtotal_confidence,
                "ocr_fallback" if subtotal != expected_subtotal else "derived",
            ),
        })

    return items


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

    items = _extract_items(rows)
    
    if not items:
        items = (
            _extract_receipt_items_fallback(
                rows
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
