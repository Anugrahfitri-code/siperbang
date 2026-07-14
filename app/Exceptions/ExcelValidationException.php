<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by ExcelPersediaanImportService when one or more rows
 * in the uploaded Excel file fail validation.
 *
 * The upload is rejected in full — no data is saved to the database.
 * The caller should display $this->getErrors() to the user and ask
 * them to fix the source Excel file and re-upload.
 */
class ExcelValidationException extends RuntimeException
{
    /**
     * @param  array  $errors  [ ['sheet'=>..., 'no_urut'=>..., 'nama'=>..., 'messages'=>[...]], ... ]
     */
    public function __construct(string $message, private readonly array $errors = [])
    {
        parent::__construct($message);
    }

    /** Structured per-row error list */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
