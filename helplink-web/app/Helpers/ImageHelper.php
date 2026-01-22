<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    public static function url($path)
    {
        if (!$path) {
            return asset('images/placeholder.png'); 
        }

        $path = ltrim($path, '/');

        // normalize mixed paths
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }

        return Storage::url($path);
    }
}
