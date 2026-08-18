<?php
declare(strict_types=1);

function convert_to_webp(string $sourcePath, string $destPath, int $quality = 80): bool {
    if (!extension_loaded('gd')) return false;

    $info = getimagesize($sourcePath);
    if (!$info) return false;

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg': $image = imagecreatefromjpeg($sourcePath); break;
        case 'image/png': $image = imagecreatefrompng($sourcePath); break;
        case 'image/gif': $image = imagecreatefromgif($sourcePath); break;
        default: return false;
    }

    if (!$image) return false;

    if ($mime === 'image/png') {
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
    }

    $result = imagewebp($image, $destPath, $quality);
    imagedestroy($image);

    return $result;
}

function get_image_url(string $imageKey, string $size = 'medium'): string {
    if (empty($imageKey)) return '';

    $ext = pathinfo($imageKey, PATHINFO_EXTENSION);
    $baseName = pathinfo($imageKey, PATHINFO_FILENAME);

    $webpPath = __DIR__ . '/../assets/uploads/' . $baseName . '.webp';
    if (file_exists($webpPath)) {
        return url('assets/uploads/' . $baseName . '.webp');
    }

    return url('assets/uploads/' . $imageKey);
}

function product_image_srcset(string $imageKey): string {
    if (empty($imageKey)) return '';

    $baseName = pathinfo($imageKey, PATHINFO_FILENAME);
    $ext = pathinfo($imageKey, PATHINFO_EXTENSION);
    $dir = __DIR__ . '/../assets/uploads/';

    $webpExists = file_exists($dir . $baseName . '.webp');

    if ($webpExists) {
        return url('assets/uploads/' . $baseName . '.webp');
    }

    return url('assets/uploads/' . $imageKey);
}