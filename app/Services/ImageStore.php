<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageStore
{
    /** Re-encode every upload as WebP (max 1200px). Re-encoding also strips any hidden payload. */
    public function save(UploadedFile $file, int $storeId, string $folder = 'products', int $max = 1200): string
    {
        $info = @getimagesize($file->getRealPath());
        abort_unless($info && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true), 422, 'Invalid image.');
        $src = match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($file->getRealPath()),
            IMAGETYPE_PNG => imagecreatefrompng($file->getRealPath()),
            default => imagecreatefromwebp($file->getRealPath()),
        };
        abort_unless($src, 422, 'Invalid image.');
        [$w, $h] = [imagesx($src), imagesy($src)];
        $scale = min(1, $max / max($w, $h));
        $dst = imagecreatetruecolor((int) round($w * $scale), (int) round($h * $scale));
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, imagesx($dst), imagesy($dst), $w, $h);
        ob_start();
        imagewebp($dst, null, 82);
        $bin = ob_get_clean();
        $path = "{$folder}/{$storeId}/".Str::random(24).'.webp';
        Storage::disk('public')->put($path, $bin);

        return $path;
    }
}
