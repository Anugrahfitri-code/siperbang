<?php

namespace App\Support\SiteBranding;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImageOptimizer
{
    public function optimizeAndStore(
        UploadedFile $file,
        string $folder,
        int $maxDimension,
        string $errorField,
    ): string {
        if (! function_exists('imagecreatefromstring')) {
            throw ValidationException::withMessages([
                $errorField => 'Ekstensi PHP GD diperlukan untuk memproses logo.',
            ]);
        }

        $binary = file_get_contents($file->getRealPath());
        $source = $binary !== false ? @imagecreatefromstring($binary) : false;

        if ($source === false) {
            throw ValidationException::withMessages([
                $errorField => 'Berkas gambar tidak dapat dibaca atau rusak.',
            ]);
        }

        $mime = strtolower((string) ($file->getMimeType() ?: $file->getClientMimeType()));
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw ValidationException::withMessages([
                $errorField => 'Format gambar tidak didukung.',
            ]),
        };

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, $maxDimension / max($sourceWidth, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($target === false) {
            imagedestroy($source);
            throw ValidationException::withMessages([
                $errorField => 'Server tidak dapat menyiapkan kanvas gambar.',
            ]);
        }

        if (in_array($extension, ['png', 'webp'], true)) {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
            imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
        } else {
            $white = imagecolorallocate($target, 255, 255, 255);
            imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $white);
        }

        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );

        $temporaryPath = tempnam(sys_get_temp_dir(), 'branding-image-');
        if ($temporaryPath === false) {
            imagedestroy($source);
            imagedestroy($target);
            throw ValidationException::withMessages([
                $errorField => 'Server tidak dapat membuat berkas gambar sementara.',
            ]);
        }

        try {
            $written = match ($extension) {
                'jpg' => imagejpeg($target, $temporaryPath, 88),
                'png' => imagepng($target, $temporaryPath, 7),
                'webp' => function_exists('imagewebp')
                    ? imagewebp($target, $temporaryPath, 88)
                    : false,
            };

            if (! $written) {
                throw ValidationException::withMessages([
                    $errorField => 'Server gagal mengoptimalkan gambar. Pastikan dukungan JPEG, PNG, dan WebP aktif pada PHP GD.',
                ]);
            }

            $path = 'branding/'.trim($folder, '/').'/'.Str::uuid().'.'.$extension;
            $stream = fopen($temporaryPath, 'rb');

            if ($stream === false || ! Storage::disk($this->disk())->put($path, $stream)) {
                if (is_resource($stream)) {
                    fclose($stream);
                }

                throw ValidationException::withMessages([
                    $errorField => 'Gambar tidak dapat disimpan ke media penyimpanan.',
                ]);
            }

            fclose($stream);

            return $path;
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            imagedestroy($source);
            imagedestroy($target);
            @unlink($temporaryPath);
        }
    }

    private function disk(): string
    {
        return (string) config('site_branding.disk', 'public');
    }
}
