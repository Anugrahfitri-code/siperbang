import sys
sys.path.insert(0, 'ocr-service')
from app.receipt_parser import clean_amount, parse_receipt

# Test amount parsing
tests = ['1,415.040,00', '357 . 000 , 00', '101.000,00', '50.500,00', '1.415.040,00', '367 , 500 , 06']
print('=== Amount Tests ===')
for t in tests:
    print(f"  {t!r} => {clean_amount(t)}")

# Test with actual EasyOCR lines from Satu Sama
print()
print('=== Full Parse Test ===')
lines = [
    {'text': 'PT, SATV 5ANA JaYA Abad;', 'confidence': 0.7, 'box': []},
    {'text': '7RANShKS9', 'confidence': 0.7, 'box': []},
    {'text': '1,415.040,00', 'confidence': 0.9, 'box': []},
    {'text': 'Tot, BAYaR', 'confidence': 0.9, 'box': []},
    {'text': '1,415.040,00', 'confidence': 0.9, 'box': []},
    {'text': 'TGL. TRX (K13)', 'confidence': 0.9, 'box': []},
    {'text': '02-02-2026 13,39825', 'confidence': 0.9, 'box': []},
    {'text': 'PASED SYART FacIAL SoFt PacK Bo', 'confidence': 0.7, 'box': []},
    {'text': '30 FACK X 11.500,00', 'confidence': 0.9, 'box': []},
    {'text': '357 . 000 , 00', 'confidence': 0.9, 'box': []},
    {'text': '034002: 8998866696609 WINGS', 'confidence': 0.9, 'box': []},
    {'text': 'SoklIN Lantai hiJAv huda JERGEN', 'confidence': 0.8, 'box': []},
    {'text': 'L', 'confidence': 0.9, 'box': []},
    {'text': '2 JRG X 50.500,00', 'confidence': 0.9, 'box': []},
    {'text': '101.000,00', 'confidence': 0.9, 'box': []},
    {'text': '003304: 8886030324301 YURI', 'confidence': 0.9, 'box': []},
    {'text': 'PORSTEX BIRU BotoL Z0o0 ML', 'confidence': 0.8, 'box': []},
    {'text': '4 BTL X 39.580,00', 'confidence': 0.9, 'box': []},
    {'text': '158 . 360 ,00', 'confidence': 0.9, 'box': []},
    {'text': '053239 ,', 'confidence': 0.8, 'box': []},
    {'text': 'SPA', 'confidence': 0.9, 'box': []},
    {'text': 'TopLES PLASTIK 84*60', 'confidence': 0.8, 'box': []},
    {'text': '1 PCs X 5.000,00', 'confidence': 0.9, 'box': []},
    {'text': '5.000,00', 'confidence': 0.9, 'box': []},
    {'text': 'Joyko guntiNG 5C-33', 'confidence': 0.8, 'box': []},
    {'text': '3 FCS X', 'confidence': 0.7, 'box': []},
    {'text': '10.000,0G', 'confidence': 0.8, 'box': []},
    {'text': '30 .000 , 0', 'confidence': 0.9, 'box': []},
    {'text': 'Joyko GuntING SC-32', 'confidence': 0.8, 'box': []},
    {'text': '3 PCs X 23.500,00', 'confidence': 0.9, 'box': []},
    {'text': '70.500,00', 'confidence': 0.9, 'box': []},
    {'text': 'PISAU IDEAL IdF-1005', 'confidence': 0.8, 'box': []},
    {'text': '2 PCS X 17.590,00', 'confidence': 0.9, 'box': []},
    {'text': '35.180, 00', 'confidence': 0.9, 'box': []},
    {'text': 'PapeRINE buKu Folid HC 200 LBR', 'confidence': 0.8, 'box': []},
    {'text': '4 PCs X 36.500,00', 'confidence': 0.9, 'box': []},
    {'text': '146.000,00', 'confidence': 0.9, 'box': []},
]
result = parse_receipt(lines)
print('Store:', result.store_name.value)
print('Date:', result.date.value)
print('Total:', result.total.value)
print('Items:', len(result.items))
for it in result.items:
    print(f"  - {it['name']['value']} x{it['qty']['value']} @{it['price']['value']} = {it['subtotal']['value']}")
