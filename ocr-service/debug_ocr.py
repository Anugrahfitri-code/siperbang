import traceback
from app.ocr_engine import ocr_engine

try:
    ocr_engine.ensure_loaded()
    print("Engine loaded.")
    res = ocr_engine.process(r'D:\Project\siperbang\ocr-test\260212 New Agung 80.000.pdf')
    print("Success")
except Exception as e:
    print("FAILED:")
    traceback.print_exc()
