<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageService
{
    private ImageManager $manager;

    private array $sizes = [
        'thumbnail' => ['width' => 150, 'height' => 150],
        'small' => ['width' => 400, 'height' => 300],
        'medium' => ['width' => 800, 'height' => 600],
        'large' => ['width' => 1200, 'height' => 900],
    ];

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * 画像をアップロードして複数サイズを生成
     */
    public function uploadAndResize(UploadedFile $file, string $directory = 'reviews'): array
    {
        // ファイル名生成（ユニークID + 元の拡張子）
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;

        // 保存先ディレクトリ
        $basePath = "images/{$directory}";

        // 元画像を読み込み
        $image = $this->manager->read($file->getPathname());

        $paths = [];

        // 各サイズで画像を生成・保存
        foreach ($this->sizes as $size => $dimensions) {
            $resizedImage = clone $image;

            // アスペクト比を保持してリサイズ
            $resizedImage->scaleDown(
                width: $dimensions['width'],
                height: $dimensions['height']
            );

            // ファイルパス
            $sizePath = "{$basePath}/{$size}";
            $fullPath = "{$sizePath}/{$filename}";

            // ディレクトリが存在しない場合は作成
            if (!Storage::disk('public')->exists($sizePath)) {
                Storage::disk('public')->makeDirectory($sizePath);
            }

            // 画像を保存
            Storage::disk('public')->put(
                $fullPath,
                $resizedImage->toJpeg(quality: 85)->toString()
            );

            $paths[$size] = $fullPath;
        }

        return [
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'paths' => $paths,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }

    /**
     * 画像ファイルを削除
     */
    public function deleteImages(array $paths): bool
    {
        $success = true;

        foreach ($paths as $path) {
            if (Storage::disk('public')->exists($path)) {
                if (!Storage::disk('public')->delete($path)) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * 画像のURLを取得
     */
    public function getImageUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    /**
     * サポートされている画像形式をチェック
     */
    public function isSupportedImageType(string $mimeType): bool
    {
        $supportedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ];

        return in_array($mimeType, $supportedTypes);
    }

    /**
     * 画像ファイルサイズの制限チェック（10MB）
     */
    public function isValidSize(int $size): bool
    {
        return $size <= 10 * 1024 * 1024; // 10MB
    }
}
