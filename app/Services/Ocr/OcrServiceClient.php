<?php

namespace App\Services\Ocr;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use App\Exceptions\OcrServiceException;
use Illuminate\Support\Facades\Log;

class OcrServiceClient
{
    protected string $url;
    protected string $token;
    protected int $timeout;

    public function __construct()
    {
        $this->url = rtrim(config('services.ocr.url', 'http://127.0.0.1:8001'), '/');
        $this->token = (string) config('services.ocr.token', '');
        $this->timeout = (int) config('services.ocr.timeout', 180);
    }

    /**
     * Process an image or PDF via FastAPI OCR service.
     *
     * @param string $filePath Absolute path to the file.
     * @param string $filename Original filename or storage path.
     * @return array The parsed JSON result.
     * @throws OcrServiceException
     */
    public function processReceipt(string $filePath, string $filename): array
    {
        if (!file_exists($filePath)) {
            throw new OcrServiceException("File not found for OCR processing", 404, ['file' => $filename]);
        }

        $fileHandle = fopen($filePath, 'r');
        if (!$fileHandle) {
            throw new OcrServiceException("Unable to open file for reading", 500, ['file' => $filename]);
        }

        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout(5)
                ->withHeaders([
                    'X-Service-Token' => $this->token,
                    'Accept' => 'application/json',
                ])
                ->retry(2, 1000, function ($exception, $request) {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }
                    if ($exception instanceof RequestException && $exception->response->serverError()) {
                        return true;
                    }
                    return false; // Do not retry on 4xx
                })
                ->attach('document', $fileHandle, $filename)
                ->post($this->url . '/internal/v1/receipt-ocr');

            if ($response->failed()) {
                throw new OcrServiceException(
                    "OCR API returned an error: " . $response->status(),
                    $response->status(),
                    ['response_body' => $response->body()]
                );
            }

            $data = $response->json();
            if (!is_array($data)) {
                throw new OcrServiceException("Invalid JSON response from OCR API", 500, ['raw_body' => $response->body()]);
            }

            return $data;

        } catch (ConnectionException $e) {
            throw new OcrServiceException("Connection timeout or failure to OCR API", 504, [], $e);
        } catch (\Exception $e) {
            if ($e instanceof OcrServiceException) {
                throw $e;
            }
            throw new OcrServiceException("Unexpected error during OCR API request: " . $e->getMessage(), 500, [], $e);
        } finally {
            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }
        }
    }
}
