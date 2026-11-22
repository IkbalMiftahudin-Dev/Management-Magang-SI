<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateFileService
{
    private const DISK = 'local';

    /**
     * Store an uploaded file inside private storage using an unpredictable UUID filename.
     *
     * @return array{path: string, checksum: string}
     */
    public function storeUploadedFile(UploadedFile $file, string $directory): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: ($file->extension() ?: 'bin'));
        $filename = Str::uuid().'.'.$extension;
        $cleanDir = trim($directory, '/');

        $storedPath = Storage::disk(self::DISK)->putFileAs($cleanDir, $file, $filename);

        if (! is_string($storedPath) || $storedPath === '') {
            abort(500, 'Gagal menyimpan berkas ke penyimpanan aman.');
        }

        $checksum = hash_file('sha256', $file->getRealPath() ?: Storage::disk(self::DISK)->path($storedPath)) ?: '';

        return [
            'path' => $storedPath,
            'checksum' => $checksum,
        ];
    }

    /**
     * Store raw binary content (such as generated PDF bytes) inside private storage.
     *
     * @return array{path: string, checksum: string}
     */
    public function storeRaw(string $contents, string $directory, string $extension = 'pdf'): array
    {
        $cleanDir = trim($directory, '/');
        $path = $cleanDir.'/'.Str::uuid().'.'.strtolower($extension);

        Storage::disk(self::DISK)->put($path, $contents);

        return [
            'path' => $path,
            'checksum' => hash('sha256', $contents),
        ];
    }

    /**
     * Safely remove an existing private file when replaced or deleted.
     */
    public function deleteIfExists(?string $path): void
    {
        if ($path === null || trim($path) === '') {
            return;
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /**
     * Stream or download a private file after controller-level policy authorization has passed.
     */
    public function streamResponse(string $path, ?string $downloadName = null, bool $forceDownload = false): StreamedResponse
    {
        abort_unless(Storage::disk(self::DISK)->exists($path), 404, 'Berkas tidak ditemukan.');

        if ($forceDownload) {
            return Storage::disk(self::DISK)->download($path, $downloadName);
        }

        return Storage::disk(self::DISK)->response($path, $downloadName, [
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
