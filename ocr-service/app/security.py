from __future__ import annotations

import secrets

from fastapi import HTTPException, Security, status
from fastapi.security.api_key import APIKeyHeader

from app.config import settings


service_token_header = APIKeyHeader(
    name="X-Service-Token",
    auto_error=False,
)


async def verify_service_token(
    provided_token: str | None = Security(
        service_token_header
    ),
) -> str:
    if not settings.service_token:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="OCR service token is not configured.",
        )

    if not provided_token:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Missing X-Service-Token header.",
        )

    if not secrets.compare_digest(
        provided_token,
        settings.service_token,
    ):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid service token.",
        )

    return provided_token
