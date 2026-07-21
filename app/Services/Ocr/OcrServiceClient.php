<?php

namespace App\Services\Ocr;

use App\Exceptions\OcrServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

final class OcrServiceClient
{
    private string $url;

    private string $token;

    private int $timeout;

    private int $connectTimeout;

    public function __construct()
    {
        $this->url = rtrim(
            (string) config(
                'services.ocr.url',
                'http://127.0.0.1:8001',
            ),
            '/',
        );

        $this->token = trim(
            (string) config(
                'services.ocr.token',
                '',
            ),
        );

        $this->timeout = (int) config(
            'services.ocr.timeout',
            110,
        );

        $this->connectTimeout = (int) config(
            'services.ocr.connect_timeout',
            5,
        );
    }

    /**
     * @throws OcrServiceException
     */
    public function processReceipt(
        string $filePath,
        string $originalFilename,
    ): array {
        $this->validateConfiguration();

        if (! is_file($filePath)) {
            throw new OcrServiceException(
                message: 'File OCR tidak ditemukan.',
                httpStatus: 404,
                retryable: false,
            );
        }

        if (! is_readable($filePath)) {
            throw new OcrServiceException(
                message: 'File OCR tidak dapat dibaca.',
                httpStatus: 500,
                retryable: false,
            );
        }

        $uploadFilename = $this->sanitizeFilename(
            originalFilename: $originalFilename,
            filePath: $filePath,
        );

        $mimeType = mime_content_type($filePath);

        if (! is_string($mimeType) || $mimeType === '') {
            $mimeType = 'application/octet-stream';
        }

        $fileHandle = fopen(
            $filePath,
            'rb',
        );

        if ($fileHandle === false) {
            throw new OcrServiceException(
                message: 'File OCR gagal dibuka.',
                httpStatus: 500,
                retryable: false,
            );
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'X-Service-Token' => $this->token,
                ])
                ->connectTimeout(
                    $this->connectTimeout,
                )
                ->timeout(
                    $this->timeout,
                )
                ->attach(
                    name: 'document',
                    contents: $fileHandle,
                    filename: $uploadFilename,
                    headers: [
                        'Content-Type' => $mimeType,
                    ],
                )
                ->post(
                    $this->url
                    . '/internal/v1/receipt-ocr',
                );
        } catch (ConnectionException $exception) {
            $connectionMessage = strtolower(
                $exception->getMessage(),
            );

            $isTimeout =
                str_contains(
                    $connectionMessage,
                    'curl error 28',
                )
                || str_contains(
                    $connectionMessage,
                    'timed out',
                )
                || str_contains(
                    $connectionMessage,
                    'timeout',
                );

            throw new OcrServiceException(
                message: $isTimeout
                    ? (
                        'Layanan OCR melewati batas waktu '
                        . $this->timeout
                        . ' detik.'
                    )
                    : (
                        'Layanan OCR tidak dapat dihubungi di '
                        . $this->url
                        . '. Pastikan container atau server OCR aktif.'
                    ),
                httpStatus: 503,
                retryable: true,
                contextData: [
                    'ocr_url' =>
                        $this->url,

                    'failure_type' =>
                        $isTimeout
                            ? 'timeout'
                            : 'connection',

                    'client_timeout_seconds' =>
                        $this->timeout,
                ],
                previous: $exception,
            );
        } catch (Throwable $exception) {
            throw new OcrServiceException(
                message: 'Terjadi kesalahan saat menghubungi layanan OCR.',
                httpStatus: 500,
                retryable: true,
                previous: $exception,
            );
        } finally {
            fclose($fileHandle);
        }

        if ($response->failed()) {
            $this->throwForFailedResponse(
                $response,
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new OcrServiceException(
                message: 'Layanan OCR mengembalikan JSON yang tidak valid.',
                httpStatus: 502,
                retryable: false,
            );
        }

        $this->validateSuccessfulResponse(
            $data,
        );

        return $data;
    }

    /**
     * @throws OcrServiceException
     */
    private function validateConfiguration(): void
    {
        if ($this->url === '') {
            throw new OcrServiceException(
                message: 'OCR_SERVICE_URL belum dikonfigurasi.',
                httpStatus: 500,
                retryable: false,
            );
        }

        if ($this->token === '') {
            throw new OcrServiceException(
                message: 'OCR_SERVICE_TOKEN belum dikonfigurasi.',
                httpStatus: 500,
                retryable: false,
            );
        }

        if ($this->timeout < 1) {
            throw new OcrServiceException(
                message: 'OCR_SERVICE_TIMEOUT tidak valid.',
                httpStatus: 500,
                retryable: false,
            );
        }

        if ($this->connectTimeout < 1) {
            throw new OcrServiceException(
                message: 'OCR_SERVICE_CONNECT_TIMEOUT tidak valid.',
                httpStatus: 500,
                retryable: false,
            );
        }
    }

    private function sanitizeFilename(
        string $originalFilename,
        string $filePath,
    ): string {
        $normalized = str_replace(
            '\\',
            '/',
            $originalFilename,
        );

        $filename = basename(
            $normalized,
        );

        if ($filename === '' || $filename === '.') {
            $filename = basename(
                $filePath,
            );
        }

        return $filename;
    }

    /**
     * @throws OcrServiceException
     */
    private function throwForFailedResponse(
        Response $response,
    ): never {
        $statusCode = $response->status();

        $retryable = in_array(
            $statusCode,
            [408, 425, 429],
            true,
        ) || $statusCode >= 500;

        $detail = $response->json(
            'detail',
        );

        $safeDetail = is_string($detail)
            ? $this->sanitizeMessage($detail)
            : null;

        $message = match ($statusCode) {
            401, 403 =>
                'Autentikasi layanan OCR gagal.',

            413 =>
                'Ukuran dokumen melebihi batas layanan OCR.',

            415 =>
                'Format dokumen tidak didukung layanan OCR.',

            422 =>
                $safeDetail
                ?: 'Tidak ada teks yang dapat dibaca pada dokumen.',

            429 =>
                'Layanan OCR sedang menerima terlalu banyak permintaan.',

            500 =>
                'Layanan OCR gagal memproses dokumen.',

            503 =>
                'Mesin OCR sedang tidak tersedia.',

            default =>
                $safeDetail
                ?: 'Layanan OCR mengembalikan kesalahan.',
        };

        throw new OcrServiceException(
            message: $message,
            httpStatus: $statusCode,
            retryable: $retryable,
            contextData: [
                'http_status' => $statusCode,
            ],
        );
    }

    /**
     * @throws OcrServiceException
     */
    private function validateSuccessfulResponse(
        array $data,
    ): void {
        if (($data['success'] ?? null) !== true) {
            throw new OcrServiceException(
                message: 'Layanan OCR tidak menyatakan proses berhasil.',
                httpStatus: 502,
                retryable: false,
            );
        }

        $engine = $data['engine'] ?? null;

        if (
            ! is_string($engine)
            || trim($engine) === ''
        ) {
            throw new OcrServiceException(
                message: 'Nama mesin OCR tidak terdapat dalam response.',
                httpStatus: 502,
                retryable: false,
            );
        }

        $rawText = $data['raw_text'] ?? null;

        if (
            ! is_string($rawText)
            || trim($rawText) === ''
        ) {
            throw new OcrServiceException(
                message: 'Mesin OCR tidak menghasilkan teks.',
                httpStatus: 422,
                retryable: false,
            );
        }

        $pages = $data['pages'] ?? null;

        if (
            ! is_array($pages)
            || $pages === []
        ) {
            throw new OcrServiceException(
                message: 'Response OCR tidak memiliki data halaman.',
                httpStatus: 502,
                retryable: false,
            );
        }

        $hasReadableLine = false;

        foreach ($pages as $page) {
            if (! is_array($page)) {
                continue;
            }

            $lines = $page['lines'] ?? null;

            if (! is_array($lines)) {
                continue;
            }

            foreach ($lines as $line) {
                if (! is_array($line)) {
                    continue;
                }

                $text = $line['text'] ?? null;

                if (
                    is_string($text)
                    && trim($text) !== ''
                ) {
                    $hasReadableLine = true;

                    break 2;
                }
            }
        }

        if (! $hasReadableLine) {
            throw new OcrServiceException(
                message: 'Response OCR tidak memiliki baris teks.',
                httpStatus: 422,
                retryable: false,
            );
        }

        $document = $data['document'] ?? null;

        if (
            $document !== null
            && ! is_array($document)
        ) {
            throw new OcrServiceException(
                message: 'Struktur parsed document tidak valid.',
                httpStatus: 502,
                retryable: false,
            );
        }
    }

    private function sanitizeMessage(
        string $message,
    ): string {
        $message = preg_replace(
            '/\s+/u',
            ' ',
            trim($message),
        );

        return Str::limit(
            $message ?: 'OCR service error.',
            300,
        );
    }
}
