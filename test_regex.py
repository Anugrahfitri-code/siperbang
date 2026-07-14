import re

def clean_amount(text: str) -> float:
    text = str(text).upper().strip()
    text = re.sub(r'RP\.?\s*', '', text)
    # Fix common OCR mistakes for numbers at the end
    text = re.sub(r'[OG]$', '0', text)
    # Also handle trailing ,0 or ,0G etc
    text = re.sub(r'[,\.]\s*0[0-9OG]?$', '', text)
    text = text.strip()
    # Remove spaces around separators
    text = re.sub(r'\s*[,\.]\s*', lambda m: m.group().strip(), text)
    text = re.sub(r'[\s,\.]', '', text)
    try:
        return float(text)
    except Exception:
        return 0.0

lines = [
    'PASED SYART FacIAL SoFt PacK Bo',
    '30 FACK X 11.500,00',
    '357 . 000 , 00',
    'SoklIN Lantai hiJAv huda JERGEN',
    'L',
    '2 JRG X 50.500,00',
    '101.000,00',
    'Joyko guntiNG 5C-33',
    '3 FCS X',
    '10.000,0G',
    '30 .000 , 0',
    'Joyko GuntING SC-32',
    '3 PCs X 23.500,00',
    '70.500,00',
]

def extract_items(lines):
    items = []
    # match something like '3 PCs X 10.000,00' or '3 PCs X'
    qty_pat = re.compile(r'^(\d+)\s*(?:[a-zA-Z]+)?\s*[Xx]\s*(.*)$', re.IGNORECASE)
    
    i = 0
    while i < len(lines):
        line = lines[i].strip()
        m = qty_pat.search(line)
        if m:
            qty = int(m.group(1))
            rest = m.group(2).strip()
            
            amounts = []
            if rest:
                # Might contain two amounts separated by space e.g. "10.000,00  30.000,00"
                parts = re.split(r'\s{2,}|(?<=\d)\s+(?=\d)', rest) # split on 2 spaces or space between digits
                for p in parts:
                    a = clean_amount(p)
                    if a > 0: amounts.append(a)
            
            lookahead = 1
            while len(amounts) < 2 and i + lookahead < len(lines):
                amt_str = lines[i + lookahead].strip()
                # could be "10.000,0G"
                a = clean_amount(amt_str)
                if a > 0:
                    amounts.append(a)
                lookahead += 1
                
            if len(amounts) >= 2:
                price = amounts[0]
                subt = amounts[1]
                name = 'Item'
                for back in range(1, 4):
                    if i - back >= 0:
                        prev = lines[i - back].strip()
                        if len(prev) > 2 and not qty_pat.search(prev) and not re.match(r'^[\d.,]+$', prev) and prev != 'L':
                            name = prev
                            break
                items.append({"name": name, "qty": qty, "price": price, "subtotal": subt})
            i += lookahead - 1
        i += 1
    return items

for it in extract_items(lines):
    print(it)

print("Tests:")
tests = ['1,415.040,00', '357 . 000 , 00', '101.000,00', '50.500,00', '1.415.040,00', '367 , 500 , 06', '30 .000 , 0']
for t in tests:
    print(f"{t} => {clean_amount(t)}")
