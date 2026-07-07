<?php

namespace App\Support;

class AssetUrl
{
    public static function versioned(?string $path): string
    {
        $path = (string) $path;

        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_contains($path, '?')) {
            return $path;
        }

        $publicPath = public_path(ltrim($path, '/'));

        if (! is_file($publicPath)) {
            return $path;
        }

        return $path.'?v='.filemtime($publicPath);
    }
}
