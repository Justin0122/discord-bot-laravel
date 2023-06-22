<?php

namespace App\Discord\src\Helpers;

class ImageHelper
{

    public static function getRandomImage($command, $type): ?string
    {
        $command = ucfirst($command);
        $path = __DIR__ . "/../Media/{$command}/{$type}";
        $files = scandir($path);
        $files = array_diff($files, ['.', '..']);
        try {
            $file = $files[array_rand($files)];
            return $path . '/' . $file;
        } catch (\Throwable $th) {
            return null;
        }
    }

    public static function spoilerImage($file): array|string
    {
        $newFile = __DIR__ . '/../Media/tmp/SPOILER_' . basename($file);
        copy($file, $newFile);
        return $newFile;
    }

    public static function deleteFiles(): void
    {
        $files = glob(__DIR__ . '/../Media/tmp/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
