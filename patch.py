import re

file_path = r"d:\Project\siperbang\ocr-service\app\receipt_parser.py"

with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# 1. Insert constants
constants = """MAX_PLAUSIBLE_QUANTITY = 10000

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

ITEM_SEQUENCE_PATTERN = re.compile(
    r"^\\s*(\\d{1,3})\\s*[.)\\],;:-]\\s*(.*)$"
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
)"""
content = content.replace("MAX_PLAUSIBLE_QUANTITY = 10000", constants)

# 2. Add _formatted_money_values before _month_number
formatted_money = """def _formatted_money_values(
    text: str,
) -> list[float]:
    \"\"\"
    Mengambil angka yang benar-benar mempunyai format nominal.

    Nomor urut seperti 3. dan ukuran barang seperti 90X120
    tidak boleh dianggap sebagai harga.
    \"\"\"
    searchable_text, _ = _remove_barcode_tokens(
        text
    )

    # Contoh hasil OCR:
    # 937 . 142,80
    # diubah menjadi:
    # 937.142,80
    searchable_text = re.sub(
        r"(?<=\\d)\\s*([.,])\\s*(?=\\d)",
        r"\\1",
        searchable_text,
    )

    patterns = (
        re.compile(
            r"(?i)(?:RP\\.?\\s*|@\\s*)?"
            r"(?<!\\d)"
            r"\\d{1,3}(?:\\.\\d{3})+"
            r"(?:,\\d{1,2})?"
            r"(?!\\d)"
        ),
        re.compile(
            r"(?i)(?:RP\\.?\\s*|@\\s*)?"
            r"(?<!\\d)"
            r"\\d{1,3}(?:,\\d{3})+"
            r"(?:\\.\\d{1,2})?"
            r"(?!\\d)"
        ),
        re.compile(
            r"(?i)(?:RP\\.?\\s*|@\\s*)"
            r"(?<!\\d)"
            r"\\d{3,}"
            r"(?:[.,]\\d{1,2})?"
            r"(?!\\d)"
        ),
        re.compile(
            r"(?<!\\d)"
            r"\\d+,\\d{2}"
            r"(?!\\d)"
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
        r"(?i)(?:RP\\.?\\s*)?\\d{4,}",
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


def _month_number("""
content = content.replace("def _month_number(", formatted_money)


# 3. Replace _extract_date_field
new_extract_date_field = """def _extract_date_field(
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
            r"\\b(?:TGL|TANGGAL|DATE)\\b",
            keyword,
        ):
            score += 1200.0

        if re.search(
            r"\\b(?:TRX|TRANSAKSI|TRANSACTION)\\b",
            keyword,
        ):
            score += 3000.0

        # Tanggal transaksi biasanya disertai jam.
        if re.search(
            r"\\b\\d{1,2}[.:]\\d{2}"
            r"(?:[.:]\\d{2})?\\b",
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

def _looks_like_invoice_value"""
match1 = re.search(r"def _extract_date_field\(.*?def _looks_like_invoice_value", content, flags=re.DOTALL)
if match1:
    content = content.replace(match1.group(0), new_extract_date_field)


# 4. Insert _extract_inline_total before _header_role
inline_total = """def _extract_inline_total(
    lines: list[OcrLine],
) -> ExtractedValue:
    \"\"\"
    Membaca total ketika label dan nominal berada
    pada satu hasil OCR.

    Contoh:
    TOT. BAYAR : 937.142,80
    \"\"\"
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


def _header_role("""
content = content.replace("def _header_role(", inline_total)


# 5. Replace _extract_qty_marker
new_qty_marker = """def _extract_qty_marker(
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
            rf"\\s*[:=]?\\s*"
            rf"(\\d{{1,5}}(?:[.,]\\d+)?)"
            rf"\\s*{RECEIPT_UNIT_PATTERN}\\s*"
            rf"(?:X|@)\\s*"
            rf"([\\d\\s.,]+)?"
        ),
        re.compile(
            r"(?i)(?:QTY|OTY|JUMLAH)?"
            r"\\s*[:=]?\\s*"
            r"(\\d{1,5}(?:[.,]\\d+)?)"
            r"\\s+(?:X|@)\\s+"
            r"([\\d\\s.,]+)?"
        ),
    ]

    if had_barcode:
        patterns.append(
            re.compile(
                r"(?i)^[\\s,;:-]*"
                r"(\\d{1,5}(?:[.,]\\d+)?)"
                r"\\s*[:=]\\s*"
                r"([\\d\\s.,]+)?"
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
                    r"\\d[\\d\\s.,]*",
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

def _extract_items("""
match2 = re.search(r"def _extract_qty_marker\(.*?def _extract_items\(", content, flags=re.DOTALL)
if match2:
    content = content.replace(match2.group(0), new_qty_marker)


# 6. Insert numbered receipt items before _split_receipt_regions
numbered_items = """def _item_sequence_match(
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
            r"\\d{4,}",
            remainder,
        )
    ):
        return None

    return match


def _extract_numbered_receipt_items(
    rows: list[OcrRow],
) -> list[dict[str, Any]]:
    \"\"\"
    Membaca thermal receipt yang itemnya diawali
    nomor 1., 2., 3., dan seterusnya.

    Nomor item berikutnya tidak boleh menjadi harga.
    \"\"\"

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
            r"^\\s*\\d{4,8}"
            r"\\s*[:;=-]+\\s*",
            "",
            value,
        )

        value = re.sub(
            r"^\\s*[-:;,]+\\s*",
            "",
            value,
        )

        return re.sub(
            r"\\s+",
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
                r"\\d+(?:[.,]\\d+)?"
                r"\\s*(?:ML|L|GR|G|KG|CM|MM|M)",
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
            r"\\s+",
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


def _split_receipt_regions("""
content = content.replace("def _split_receipt_regions(", numbered_items)


# 7. Update parse_receipt logic
old_parse_logic = """    totals = _extract_labeled_amounts(
        lines,
        rows,
    )

    items = _extract_items(rows)
    
    if not items:
        items = (
            _extract_receipt_items_by_regions(
                lines
            )
        )

    subtotal = totals["subtotal"]
    tax_amount = totals["tax"]
    total = totals["total"]"""

new_parse_logic = """    totals = _extract_labeled_amounts(
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
    total = totals["total"]"""

content = content.replace(old_parse_logic, new_parse_logic)

with open(file_path, "w", encoding="utf-8") as f:
    f.write(content)

print("Patch applied to receipt_parser.py successfully!")
