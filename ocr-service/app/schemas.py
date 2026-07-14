from pydantic import BaseModel, Field
from typing import List, Optional, Any, Union

class ExtractedValue(BaseModel):
    value: Union[str, float, int, None] = None
    confidence: Optional[float] = None
    source: Optional[str] = None

class DocumentData(BaseModel):
    store_name: ExtractedValue
    invoice_no: ExtractedValue
    date: ExtractedValue
    subtotal: ExtractedValue
    tax_rate: ExtractedValue
    tax_amount: ExtractedValue
    total: ExtractedValue
    items: Optional[List[dict]] = []

class LineData(BaseModel):
    text: str
    confidence: float
    box: List[List[float]]

class PageData(BaseModel):
    page: int
    width: int
    height: int
    lines: List[LineData]

class OcrResponse(BaseModel):
    success: bool
    engine: str
    engine_version: str
    overall_confidence: Optional[float] = None
    raw_text: Optional[str] = None
    pages: List[PageData] = []
    document: Optional[DocumentData] = None
    items: List[dict] = []
    warnings: List[str] = []
    error: Optional[str] = None
