<?php

namespace Modules\Operations\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Photos and signatures for forms and checklists. Stored on the private disk
 * (persisted on Cloudron) and only served through an authorised controller.
 */
final class Uploads
{
    private const SIGNATURE_PREFIX = 'data:image/png;base64,';

    /**
     * @return list<string>
     */
    public static function photoRules(bool $required): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'mimes:jpg,jpeg,png,webp,heic,heif',
            'max:'.(int) config('operations.uploads.max_kilobytes'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function signatureRules(): array
    {
        return ['nullable', 'string', 'starts_with:'.self::SIGNATURE_PREFIX, 'max:1500000'];
    }

    public static function storePhoto(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');

        return $file->storeAs($directory, Str::uuid().'.'.$extension, self::disk());
    }

    public static function storeSignature(string $dataUrl, string $directory): string
    {
        $bytes = base64_decode(substr($dataUrl, strlen(self::SIGNATURE_PREFIX)), true);

        if ($bytes === false || ! str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
            throw new RuntimeException('The signature could not be read. Please sign again.');
        }

        $path = $directory.'/'.Str::uuid().'.png';
        Storage::disk(self::disk())->put($path, $bytes);

        return $path;
    }

    public static function response(string $path): StreamedResponse
    {
        abort_unless(Storage::disk(self::disk())->exists($path), 404);

        return Storage::disk(self::disk())->response($path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private static function disk(): string
    {
        return (string) config('operations.uploads.disk');
    }
}
