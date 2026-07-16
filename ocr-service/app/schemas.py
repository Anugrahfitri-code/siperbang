from __future__ import annotations

from typing import Any, Literal

from pydantic import BaseModel, Field


class ExtractedValue(BaseModel):
    value: str | float | int | None = None
    confidence: float | None = None
    source: str | None = None


class DocumentData(BaseModel):
    store_name: ExtractedValue = Field(
        default_factory=ExtractedValue
    )

    invoice_no: ExtractedValue = Field(
        default_factory=ExtractedValue
    )

    date: ExtractedValue = Field(
        default_factory=ExtractedValue
    )

    subtotal: ExtractedValue = Field(
        default_factory=ExtractedValue
    )

    tax_rate: ExtractedValue = Field(
        default_factory=ExtractedValue
    )

    tax_amount: ExtractedValue = Field(
        default_factory=ExtractedValue
    )

    total: ExtractedValue = Field(
        default_factory=ExtractedValue
    )

    items: list[dict[str, Any]] = Field(
        default_factory=list
    )


class LineData(BaseModel):
    text: str
    confidence: float
    box: list[list[float]]


class PageData(BaseModel):
    page: int
    width: int
    height: int
    lines: list[LineData] = Field(
        default_factory=list
    )


class HealthResponse(BaseModel):
    status: Literal["healthy", "unhealthy"]
    service: str
    engine: str
    engine_version: str
    paddle_version: str
    model: str
    device: str
    engine_loaded: bool
    error: str | None = None


class OcrWarning(BaseModel):
    code: str
    field: str | None = None
    message: str
    severity: Literal[
        "info",
        "warning",
        "error",
    ] = "warning"


class OcrResponse(BaseModel):
    success: bool
    engine: str
    engine_version: str
    paddle_version: str
    overall_confidence: float | None = None
    raw_text: str | None = None

    pages: list[PageData] = Field(
        default_factory=list
    )

    document: DocumentData | None = None

    items: list[dict[str, Any]] = Field(
        default_factory=list
    )

    warnings: list[OcrWarning] = Field(
        default_factory=list
    )
