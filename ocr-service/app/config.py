import os
from pydantic_settings import BaseSettings

class Settings(BaseSettings):
    service_token: str = os.getenv("OCR_SERVICE_TOKEN", "your-secret-token-here")
    max_upload_size: int = 10 * 1024 * 1024 # 10MB
    max_pdf_pages: int = 5
    
    class Config:
        env_file = ".env"

settings = Settings()
