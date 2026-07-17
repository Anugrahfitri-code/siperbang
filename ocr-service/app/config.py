from __future__ import annotations

from pydantic import Field
from pydantic_settings import (
    BaseSettings,
    SettingsConfigDict,
)


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore",
    )

    service_token: str = Field(
        default="",
        validation_alias="OCR_SERVICE_TOKEN",
    )

    max_upload_size: int = Field(
        default=10 * 1024 * 1024,
        validation_alias="OCR_MAX_UPLOAD_SIZE",
    )

    max_pdf_pages: int = Field(
        default=1,
        validation_alias="OCR_MAX_PDF_PAGES",
    )

    pdf_dpi: int = Field(
        default=144,
        validation_alias="OCR_PDF_DPI",
    )

    max_image_side: int = Field(
        default=1280,
        validation_alias="OCR_MAX_IMAGE_SIDE",
    )

    enable_mkldnn: bool = Field(
        default=True,
        validation_alias="OCR_ENABLE_MKLDNN",
    )

    hard_timeout_seconds: int = Field(
        default=95,
        validation_alias=(
            "OCR_HARD_TIMEOUT_SECONDS"
        ),
    )

    cpu_threads: int = Field(
        default=4,
        validation_alias="OCR_CPU_THREADS",
    )

    recognition_batch_size: int = Field(
        default=4,
        validation_alias=(
            "OCR_RECOGNITION_BATCH_SIZE"
        ),
    )


settings = Settings()
