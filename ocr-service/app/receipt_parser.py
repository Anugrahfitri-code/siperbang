import re
from typing import List, Optional
from app.schemas import DocumentData, ExtractedValue


# ─── Utility ────────────────────────────────────────────────────────────────

def clean_amount(text: str) -> float:
    """Convert various Indonesian number strings to float.
    
    Handles EasyOCR output like:
      1,415.040,00  →  1415040
      367 , 500 , 06 →  367500
      1.415.040,00  →  1415040
      39.590,00     →  39590
      5.000,00      →  5000
    """
    text = str(text).upper().strip()
    text = re.sub(r'RP\.?\s*', '', text)
    # Fix common OCR mistakes for numbers at the end
    text = re.sub(r'[OG]$', '0', text)
    text = re.sub(r'[,\.]\s*0[0-9OG]?$', '', text)
    text = text.strip()
    # Remove spaces around separators
    text = re.sub(r'\s*[,\.]\s*', lambda m: m.group().strip(), text)
    text = re.sub(r'[\s,\.]', '', text)
    try:
        return float(text)
    except Exception:
        return 0.0


SKIP_STORE_KEYWORDS = [
    'TANGGAL', 'DATE', 'TGL', 'KUITANSI', 'INVOICE', 'PERHATIAN',
    'PESANAN', 'JLN.', 'JLN ', 'JALAN', 'WA.', 'WA:', 'TELP', 'TEL:',
    'LEMBAR', 'KASIR', 'CASHIER', 'NPWP', 'N.P.W.P', 'SHOPEE',
    'TOKOPEDIA', 'INSTAGRAM', 'KRITIK', 'ID CUST', 'REWARD',
    'GUNAKAN', 'KELUHAN', 'BARANG YG', '* * *', '---', '==='
]

ITEM_SKIP_KEYWORDS = [
    'TOTAL', 'SUBTOTAL', 'SUB TOTAL', 'TRANSAKSI', 'TOT.',
    'BAYAR', 'BAYAR:', 'PAJAK', 'PPN', 'TAX', 'DISKON', 'DISC',
    'KEMBALIAN', 'KEMBALI', 'CASH', 'TUNAI', 'DETAIL', 'PEMBAYARAN',
    'REWARD', 'POIN', 'HELPER', 'CHECKER', 'CUSTOMER', 'BARANG WAJIB',
    'KELUHAN', '* * *', '***'
]


def _extract_store_name(lines: List[str]) -> Optional[str]:
    """Find the most likely store name."""
    # Priority 1: Lines explicitly starting with PT., CV., TOKO, UD., etc.
    for line in lines[:20]:
        stripped = line.strip()
        upper = stripped.upper()
        if any(upper.startswith(prefix) for prefix in ['PT.', 'PT,', 'CV.', 'UD.', 'TOKO ', 'TB.', 'PD.']):
            if any(kw in upper for kw in SKIP_STORE_KEYWORDS):
                continue
            return stripped

    # Priority 2: First alpha-rich line that's not a header/footer keyword
    for line in lines[:15]:
        stripped = line.strip()
        if len(stripped) < 4 or not any(c.isalpha() for c in stripped):
            continue
        upper = stripped.upper()
        if any(kw in upper for kw in SKIP_STORE_KEYWORDS):
            continue
        # Must be mostly letters/spaces
        alpha_ratio = sum(c.isalpha() or c == ' ' for c in stripped) / len(stripped)
        if alpha_ratio < 0.4:
            continue
        # Skip if it looks like a barcode/item code
        if re.match(r'^\d+[:\-]\s', stripped):
            continue
        return stripped
    return None


def _extract_date(lines: List[str]) -> Optional[str]:
    """Extract the transaction date from OCR lines."""
    patterns = [
        # DD-MM-YYYY or DD/MM/YYYY or YYYY-MM-DD
        (r'\b(\d{2}[-/]\d{2}[-/]\d{4})\b', None),
        (r'\b(\d{4}[-/]\d{2}[-/]\d{2})\b', None),
        # DD-MM-YY (short year)
        (r'\b(\d{2}[-/]\d{2}[-/]\d{2})\b', 'short_year'),
        # "02-02-2026 13:39:25" style timestamp
        (r'TGL[.\s:]*(\d{1,2}[-/]\d{1,2}[-/]\d{2,4})', None),
        # Indonesian month names
        (r'\b(\d{1,2}\s+(?:Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+\d{2,4})\b', None),
        (r'\b(\d{1,2}\s+(?:Jan|Feb|Mar|Apr|Mei|Jun|Jul|Agu|Sep|Okt|Nov|Des)\w*\s+\d{2,4})\b', None),
    ]
    for line in lines:
        for pat, flag in patterns:
            m = re.search(pat, line, re.IGNORECASE)
            if m:
                val = m.group(1)
                if flag == 'short_year':
                    parts = re.split(r'[-/]', val)
                    if len(parts) == 3 and len(parts[2]) == 2:
                        val = f"{parts[0]}-{parts[1]}-20{parts[2]}"
                return val
    return None


def _extract_invoice(lines: List[str]) -> Optional[str]:
    """Extract invoice/nota number."""
    patterns = [
        r'(?:NO\.?|NOTA|INVOICE|INV|FAKTUR|STRUK)[\s:.#/-]*([A-Z0-9][\w\-\/]+)',
        r'(?:NO\.?)\s+(\d+)\b',
    ]
    for line in lines:
        upper = line.upper()
        for pat in patterns:
            m = re.search(pat, upper)
            if m:
                val = m.group(1).strip()
                # Exclude common false positives
                if val and not any(x in val for x in ['CUST', 'TEL', 'WA', 'HP']):
                    return val
    return None


def _extract_totals(lines: List[str]):
    """Extract subtotal, tax, and total amounts."""
    subtotal_val = 0.0
    tax_val = 0.0
    total_val = 0.0
    tax_rate = 0.0

    def find_amount_in_line(line: str) -> float:
        """Get the largest valid amount from a line."""
        nums = re.findall(r'[\d.,]+', line)
        candidates = []
        for n in nums:
            v = clean_amount(n)
            if v > 100:  # ignore tiny numbers like percentages
                candidates.append(v)
        return max(candidates) if candidates else 0.0

    for i, line in enumerate(lines):
        upper = line.upper()

        # Total Bayar / Total / Grand Total
        if re.search(r'\bTOT(?:AL)?\.?\s*(?:BAYAR|JUMLAH|HARGA)?\b', upper):
            if 'SUB' not in upper:
                val = find_amount_in_line(line)
                if not val and i + 1 < len(lines):
                    val = find_amount_in_line(lines[i + 1])
                if val > total_val:
                    total_val = val

        # Subtotal / Jumlah
        elif re.search(r'\b(?:SUBTOTAL|SUB\s*TOTAL|JML|JUMLAH)\b', upper):
            if 'TOTAL JUMLAH' in upper or 'TOT' in upper:
                continue
            val = find_amount_in_line(line)
            if not val and i + 1 < len(lines):
                val = find_amount_in_line(lines[i + 1])
            if val > subtotal_val:
                subtotal_val = val

        # PPN / Pajak / Tax
        elif re.search(r'\b(?:PPN|PAJAK|TAX)\b', upper):
            pct_match = re.search(r'(\d+)\s*%', line)
            if pct_match:
                tax_rate = float(pct_match.group(1))
            val = find_amount_in_line(line)
            if val > 100:
                tax_val = val

    # Derive missing values
    if subtotal_val == 0 and total_val > 0:
        subtotal_val = total_val - tax_val
    elif total_val == 0 and subtotal_val > 0:
        total_val = subtotal_val + tax_val

    # If tax amount was not explicit but rate is known
    if tax_val == 0 and tax_rate > 0 and subtotal_val > 0:
        tax_val = round(subtotal_val * tax_rate / 100)
        total_val = subtotal_val + tax_val

    return subtotal_val, tax_val, total_val, tax_rate


def _extract_items(lines: List[str]) -> List[dict]:
    items = []
    
    # Pattern: "3 PCS X 10.000,00" or "3 X"
    qty_pat = re.compile(r'(\d+)\s*(?:[A-Z]+)?\s*[Xx]\s*(.*)$', re.IGNORECASE)

    def is_skip(line: str) -> bool:
        up = line.upper()
        return any(kw in up for kw in ITEM_SKIP_KEYWORDS)

    def is_barcode_line(line: str) -> bool:
        """Detect lines that are just item codes/barcodes"""
        return bool(re.match(r'^\d+\.\s+\d{4,}:', line.strip()))

    i = 0
    while i < len(lines):
        line = lines[i].strip()
        if not line or len(line) < 2 or is_skip(line):
            i += 1
            continue

        m = qty_pat.search(line)
        if m:
            qty = int(m.group(1))
            rest = m.group(2).strip()
            name_part = line[:m.start()].strip()
            
            amounts = []
            if rest:
                # Might contain two amounts separated by space e.g. "10.000,00  30.000,00"
                parts = re.split(r'\s{2,}|(?<=\d)\s+(?=\d)', rest)
                for p in parts:
                    a = clean_amount(p)
                    if a > 0: amounts.append(a)
            
            lookahead = 1
            while len(amounts) < 2 and i + lookahead < len(lines):
                next_line = lines[i + lookahead].strip()
                if not is_skip(next_line):
                    a = clean_amount(next_line)
                    if a > 0:
                        amounts.append(a)
                lookahead += 1
                
            if len(amounts) >= 2:
                price = amounts[0]
                subt = amounts[1]
                
                # Verify subtotal
                calculated = round(qty * price)
                if abs(calculated - subt) / max(subt, 1) < 0.05 or subt == price:
                    name = name_part
                    if not name or not any(c.isalpha() for c in name):
                        name = 'Item'
                        for back in range(1, 4):
                            if i - back >= 0:
                                prev = lines[i - back].strip()
                                if len(prev) > 2 and not qty_pat.search(prev) and not re.match(r'^[\d.,]+$', prev) and not is_skip(prev) and not is_barcode_line(prev) and prev != 'L':
                                    name = prev
                                    break
                    # clean barcode codes
                    name = re.sub(r'^\d+\.\s*', '', name)
                    name = re.sub(r'\d{5,}:', '', name).strip()
                    if not name:
                        name = "Item"
                        
                    if not items or items[-1]['subtotal']['value'] != subt:
                        items.append({
                            "name": {"value": name},
                            "qty": {"value": qty},
                            "price": {"value": price},
                            "subtotal": {"value": subt}
                        })
            # Skip the lines we consumed looking for amounts
            i += max(1, lookahead - 1)
        else:
            i += 1
    return items


# ─── Main Entry Point ────────────────────────────────────────────────────────

def parse_receipt(lines_data: List[dict]) -> DocumentData:
    """Parse a list of OCR lines into structured DocumentData."""
    lines = [item['text'] for item in lines_data]

    store_name_str = _extract_store_name(lines)
    date_str = _extract_date(lines)
    invoice_val = _extract_invoice(lines)
    subtotal_val, tax_val, total_val, tax_rate = _extract_totals(lines)
    items = _extract_items(lines)

    return DocumentData(
        store_name=ExtractedValue(value=store_name_str, confidence=0.75 if store_name_str else None),
        invoice_no=ExtractedValue(value=invoice_val, confidence=0.8 if invoice_val else None),
        date=ExtractedValue(value=date_str, confidence=0.85 if date_str else None),
        subtotal=ExtractedValue(value=subtotal_val, confidence=0.85),
        tax_rate=ExtractedValue(value=tax_rate, confidence=0.9),
        tax_amount=ExtractedValue(value=tax_val, confidence=0.85),
        total=ExtractedValue(value=total_val, confidence=0.9),
        items=items
    )
