import re
from typing import List, Dict, Any
from app.schemas import DocumentData, ExtractedValue

def clean_amount(text: str) -> float:
    # Remove Rp, space, dots, replace comma with dot if it's the decimal separator
    text = text.upper().replace('RP', '').replace(' ', '')
    # Check if there's a comma followed by two digits at the end
    if re.search(r',\d{2}$', text):
        text = text.replace('.', '').replace(',', '.')
    else:
        # Just remove dots and commas
        text = text.replace('.', '').replace(',', '')
    try:
        return float(text)
    except:
        return 0.0

def parse_date(lines: List[str]) -> ExtractedValue:
    date_patterns = [
        r'\d{2}/\d{2}/\d{4}',
        r'\d{2}-\d{2}-\d{4}',
        r'\d{4}-\d{2}-\d{2}'
    ]
    for line in lines:
        for pattern in date_patterns:
            match = re.search(pattern, line)
            if match:
                return ExtractedValue(value=match.group(0), confidence=0.9, source="regex")
    return ExtractedValue()

def parse_receipt(lines_data: List[dict]) -> DocumentData:
    lines = [item['text'] for item in lines_data]
    
    store_name = ExtractedValue()
    if lines:
        store_name = ExtractedValue(value=lines[0], confidence=0.9, source="first_line")
        
    date_val = parse_date(lines)
    
    invoice = ExtractedValue()
    for line in lines:
        if 'INV' in line.upper() or 'NOTA' in line.upper() or 'NO' in line.upper():
            match = re.search(r'(INV|NOTA|NO)[\s\:\-\#\/]*([A-Z0-9\-\/]+)', line, re.IGNORECASE)
            if match:
                invoice = ExtractedValue(value=match.group(2), confidence=0.85)
                break
                
    subtotal = ExtractedValue()
    tax_amount = ExtractedValue()
    total = ExtractedValue()
    
    # Process amounts
    for i, line in enumerate(lines):
        up_line = line.upper()
        # Subtotal
        if 'SUBTOTAL' in up_line:
            val = re.search(r'[\d\.\,]+', up_line)
            if val:
                subtotal = ExtractedValue(value=clean_amount(val.group(0)), confidence=0.85)
            elif i + 1 < len(lines):
                val = re.search(r'[\d\.\,]+', lines[i+1])
                if val:
                    subtotal = ExtractedValue(value=clean_amount(val.group(0)), confidence=0.8)
                    
        # Tax
        if any(t in up_line for t in ['PPN', 'TAX', 'PAJAK', 'VAT']):
            val = re.search(r'[\d\.\,]+', up_line)
            if val:
                tax_amount = ExtractedValue(value=clean_amount(val.group(0)), confidence=0.85)
            elif i + 1 < len(lines):
                val = re.search(r'[\d\.\,]+', lines[i+1])
                if val:
                    tax_amount = ExtractedValue(value=clean_amount(val.group(0)), confidence=0.8)
                    
        # Total
        if ('TOTAL' in up_line or 'GRAND TOTAL' in up_line) and 'SUB' not in up_line:
            val = re.search(r'[\d\.\,]+', up_line)
            if val:
                total = ExtractedValue(value=clean_amount(val.group(0)), confidence=0.9)
            elif i + 1 < len(lines):
                val = re.search(r'[\d\.\,]+', lines[i+1])
                if val:
                    total = ExtractedValue(value=clean_amount(val.group(0)), confidence=0.8)

    # Tax logic
    tax_rate = ExtractedValue(value=None, source="not_detected")
    
    if total.value and subtotal.value and tax_amount.value is None:
        if total.value > subtotal.value:
            tax_amount = ExtractedValue(value=total.value - subtotal.value, confidence=0.9, source="derived")
            
    if tax_amount.value and subtotal.value and subtotal.value > 0:
        rate = round((tax_amount.value / subtotal.value) * 100)
        tax_rate = ExtractedValue(value=rate, confidence=0.9, source="derived")

    return DocumentData(
        store_name=store_name,
        invoice_number=invoice,
        transaction_date=date_val,
        subtotal=subtotal,
        tax_rate=tax_rate,
        tax_amount=tax_amount,
        total=total
    )
