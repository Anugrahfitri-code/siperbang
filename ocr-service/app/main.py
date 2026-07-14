import os
import tempfile
from fastapi import FastAPI, File, UploadFile, Depends, HTTPException, status
from app.config import settings
from app.schemas import OcrResponse, PageData, LineData
from app.security import verify_service_token
from app.ocr_engine import ocr_engine
from app.receipt_parser import parse_receipt

app = FastAPI(title="Siperbang OCR Service")

@app.get("/health")
async def health_check():
    return {
        "status": "healthy",
        "service": "siperbang-ocr",
        "engine_loaded": True
    }

@app.post("/internal/v1/receipt-ocr", response_model=OcrResponse)
async def process_receipt(
    document: UploadFile = File(...),
    token: str = Depends(verify_service_token)
):
    allowed_exts = {".jpg", ".jpeg", ".png", ".pdf", ".tif", ".tiff"}
    ext = os.path.splitext(document.filename)[1].lower()
    if ext not in allowed_exts:
        raise HTTPException(status_code=400, detail="Unsupported file extension")
        
    allowed_mimes = {"image/jpeg", "image/png", "application/pdf", "image/tiff"}
    if document.content_type not in allowed_mimes:
        raise HTTPException(status_code=400, detail="Unsupported mime type")

    fd, tmp_path = tempfile.mkstemp(suffix=ext)
    
    try:
        size = 0
        with os.fdopen(fd, 'wb') as f:
            while chunk := await document.read(8192):
                size += len(chunk)
                if size > settings.max_upload_size:
                    raise HTTPException(status_code=413, detail="File too large")
                f.write(chunk)
                
        try:
            result = ocr_engine.process(tmp_path, document.filename)
        except Exception as e:
            import traceback
            traceback.print_exc()
            return OcrResponse(
                success=False,
                engine="paddleocr",
                engine_version="2.7.0",
                error="OCR Processing failed: " + str(e)
            )
            
        if not result or not result[0]:
            return OcrResponse(
                success=True,
                engine="paddleocr",
                engine_version="2.7.0",
                pages=[]
            )
            
        lines_data = []
        raw_text_parts = []
        # result for single image is a list of lists. result[0] contains lines.
        for line in result[0]:
            if line is None: continue
            box, (text, conf) = line
            lines_data.append({
                "text": text,
                "confidence": float(conf),
                "box": box
            })
            raw_text_parts.append(text)
            
        parsed_doc = parse_receipt(lines_data)
        
        pages = [PageData(
            page=1,
            width=0, # Width/Height would require opening image, omitted for speed
            height=0,
            lines=[LineData(text=l['text'], confidence=l['confidence'], box=l['box']) for l in lines_data]
        )]
        
        # Calculate overall confidence
        confs = [l['confidence'] for l in lines_data]
        overall_conf = sum(confs) / len(confs) if confs else 0.0
        
        warnings = []
        if parsed_doc.tax_amount.source == "derived":
            warnings.append("Tax amount was derived from total and subtotal difference")
            
        return OcrResponse(
            success=True,
            engine="paddleocr",
            engine_version="2.7.0",
            overall_confidence=overall_conf,
            raw_text="\n".join(raw_text_parts),
            pages=pages,
            document=parsed_doc,
            items=parsed_doc.items if hasattr(parsed_doc, 'items') else [],
            warnings=warnings
        )
        
    finally:
        if os.path.exists(tmp_path):
            try:
                os.remove(tmp_path)
            except:
                pass
