import threading
from paddleocr import PaddleOCR

class OcrEngine:
    _instance = None
    _lock = threading.Lock()
    _ocr = None

    def __new__(cls):
        with cls._lock:
            if cls._instance is None:
                cls._instance = super(OcrEngine, cls).__new__(cls)
                cls._instance._init_engine()
            return cls._instance

    def _init_engine(self):
        # Initialize PaddleOCR singleton for CPU execution
        # Using 'en' which includes latin characters and numbers, sufficient for Indonesian receipts
        self._ocr = PaddleOCR(use_textline_orientation=True, lang='en')

    def process(self, img_path):
        return self._ocr.ocr(img_path, cls=True)

# Export the singleton instance
ocr_engine = OcrEngine()
