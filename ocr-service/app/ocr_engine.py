"""
OCR Engine: Multi-strategy text extractor (100% local, no cloud).

Strategy order:
1. PDF native text (pdfium)    - fast, for digital/vector PDFs
2. EasyOCR on rendered image   - for scanned/photo PDFs and images
3. Filename fallback           - last resort
"""

import os
import re
import logging
import tempfile
import pypdfium2 as pdfium
from typing import List

log = logging.getLogger(__name__)

os.environ['FLAGS_use_mkldnn'] = '0'
os.environ['KMP_DUPLICATE_LIB_OK'] = 'TRUE'


class OcrEngine:
    def __init__(self):
        self._reader = None
        self._reader_loaded = False

    def _get_reader(self):
        """Lazy-load EasyOCR Reader on first use."""
        if not self._reader_loaded:
            try:
                import easyocr
                log.info("Loading EasyOCR model (Indonesian + English)...")
                self._reader = easyocr.Reader(
                    ['id', 'en'],
                    gpu=False,
                    verbose=True,
                    download_enabled=True
                )
                log.info("EasyOCR loaded successfully")
            except Exception as e:
                log.error(f"EasyOCR load failed: {e}")
                self._reader = None
            self._reader_loaded = True
        return self._reader

    def _pdf_native_text(self, pdf_path: str) -> str:
        """Extract embedded text from a digital PDF."""
        try:
            pdf = pdfium.PdfDocument(pdf_path)
            parts = []
            for page in pdf:
                text = page.get_textpage().get_text_range()
                if text:
                    parts.append(text)
            return '\n'.join(parts).strip()
        except Exception as e:
            log.warning(f"pdfium text extraction failed: {e}")
            return ''

    def _pdf_to_images(self, pdf_path: str) -> List[str]:
        """Render PDF pages to PNG temp files."""
        image_paths = []
        try:
            pdf = pdfium.PdfDocument(pdf_path)
            for i, page in enumerate(pdf):
                bitmap = page.render(scale=2.5)
                pil_img = bitmap.to_pil()
                fd, tmp = tempfile.mkstemp(suffix=f'_page{i}.png')
                os.close(fd)
                pil_img.save(tmp, 'PNG')
                image_paths.append(tmp)
        except Exception as e:
            log.error(f"PDF render failed: {e}")
        return image_paths

    def _run_easyocr(self, image_path: str) -> List:
        """Run EasyOCR on an image, return paddle-format lines."""
        reader = self._get_reader()
        if reader is None:
            return []
        try:
            results = reader.readtext(image_path, detail=1, paragraph=False)
            paddle_lines = []
            for (box, text, conf) in results:
                paddle_lines.append([box, (text, float(conf))])
            return paddle_lines
        except Exception as e:
            log.error(f"EasyOCR readtext failed: {e}")
            return []

    def _text_to_lines(self, text: str) -> List:
        """Convert plain text to paddle-format lines."""
        lines = []
        box = [[0, 0], [100, 0], [100, 20], [0, 20]]
        for line in text.split('\n'):
            line = line.strip()
            if line:
                lines.append([box, (line, 0.99)])
        return lines

    def _filename_fallback(self, filename: str) -> List:
        """Parse from filename: 'YYMMDD StoreName Amount.pdf'"""
        store, date, total, invoice = "Toko", "", "", ""
        box = [[0, 0], [100, 0], [100, 20], [0, 20]]
        m = re.match(
            r'^(\d{2})(\d{2})(\d{2})\s+(.+?)\s+([\d.,]+)\.pdf$',
            filename, re.IGNORECASE
        )
        if m:
            yy, mm, dd = m.group(1), m.group(2), m.group(3)
            date = f"20{yy}-{mm}-{dd}"
            store = m.group(4).strip()
            total = m.group(5).strip()
            invoice = f"INV-{dd}{mm}"
        lines = []
        if store:
            lines.append([box, (store, 0.9)])
        if invoice:
            lines.append([box, (f"No. {invoice}", 0.9)])
        if date:
            lines.append([box, (f"TGL. {date}", 0.9)])
        if total:
            lines.append([box, (f"TOT. BAYAR : {total}", 0.9)])
        return lines

    def process(self, img_path: str, original_filename: str = "") -> List:
        """Main entry: returns [list_of_paddle_format_lines]"""

        if img_path.lower().endswith('.pdf'):
            # Strategy 1: native PDF text
            native = self._pdf_native_text(img_path)
            if native:
                log.info("Strategy 1: native PDF text OK")
                return [self._text_to_lines(native)]

            # Strategy 2: EasyOCR on rendered pages
            log.info("Strategy 2: rendering PDF → EasyOCR")
            images = self._pdf_to_images(img_path)
            all_lines = []
            for img in images:
                all_lines.extend(self._run_easyocr(img))
                try:
                    os.remove(img)
                except Exception:
                    pass
            if all_lines:
                return [all_lines]

        else:
            # Image: EasyOCR directly
            log.info("Strategy 2: EasyOCR on image")
            lines = self._run_easyocr(img_path)
            if lines:
                return [lines]

        # Strategy 3: filename fallback
        log.warning("All strategies failed – using filename fallback")
        return [self._filename_fallback(original_filename)]


ocr_engine = OcrEngine()
