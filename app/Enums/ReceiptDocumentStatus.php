<?php

namespace App\Enums;

enum ReceiptDocumentStatus: string
{
    case UPLOADED = 'uploaded';
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case NEEDS_REVIEW = 'needs_review';
    case DRAFT = 'draft';
    case VERIFIED = 'verified';
    case FAILED = 'failed';
}
