from fastapi import Security, HTTPException, status
from fastapi.security.api_key import APIKeyHeader
from app.config import settings
import secrets

api_key_header = APIKeyHeader(name="X-Service-Token", auto_error=False)

async def verify_service_token(api_key_header: str = Security(api_key_header)):
    if not api_key_header:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Missing X-Service-Token header"
        )
    
    # Secure comparison
    if not secrets.compare_digest(api_key_header, settings.service_token):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid service token"
        )
    return api_key_header
