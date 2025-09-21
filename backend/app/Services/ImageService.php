<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class ImageService
{
    private ImageManager $manager;

    private array $sizes = [
        'thumbnail' => ['width' => 100, 'height' => 100],
        'small' => ['width' => 400, 'height' => 300],
        'medium' => ['width' => 800, 'height' => 600],
        // largeサイズを削除（originalを使用）
    ];

    public function __construct(ImageManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * 画像をアップロードしてthumbnailのみ生成（遅延生成対応）
     *
     * @param  UploadedFile  $file  アップロードファイル
     * @param  string  $directory  保存先ディレクトリ
     * @param  string|null  $uuid  使用するUUID（nullの場合は自動生成）
     */
    public function uploadAndResize(UploadedFile $file, string $directory = 'reviews', ?string $uuid = null): array
    {
        // ファイル名生成（指定されたUUID または 新規UUID + 元の拡張子）
        $extension = $file->getClientOriginalExtension();
        $uuid = $uuid ?: Str::uuid()->toString();
        $filename = $uuid . '.' . $extension;

        // 保存先ディレクトリ
        $basePath = "images/{$directory}";
        $originalPath = "{$basePath}/original/{$filename}";

        // オリジナル画像を保存
        $originalDir = "{$basePath}/original";
        if (!Storage::disk('public')->exists($originalDir)) {
            Storage::disk('public')->makeDirectory($originalDir);
        }

        $file->storeAs("{$basePath}/original", $filename, 'public');

        // thumbnailのみ即座に生成
        $image = $this->manager->read($file->getPathname());
        $thumbnailPath = $this->generateSingleSize($image, $basePath, $filename, 'thumbnail');

        $paths = [];
        if ($thumbnailPath) {
            $paths['thumbnail'] = $thumbnailPath;
        }

        // 他のサイズ用のパスを予約（実際の生成は遅延）
        foreach (['small', 'medium'] as $size) {
            $paths[$size] = "{$basePath}/{$size}/{$filename}";
        }

        // originalパスも追加
        $paths['original'] = $originalPath;

        return [
            'uuid' => $uuid,
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'paths' => $paths,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'original_path' => $originalPath,
            'sizes_generated' => ['thumbnail' => true, 'small' => false, 'medium' => false],
        ];
    }

    /**
     * 特定サイズの画像を生成
     */
    public function generateSingleSize($image, string $basePath, string $filename, string $size): ?string
    {
        if (!isset($this->sizes[$size])) {
            return null;
        }

        $dimensions = $this->sizes[$size];

        // アスペクト比を保持してリサイズ（cloneせず直接処理）
        $image->scaleDown(
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
            $image->toJpeg(quality: 85)->toString()
        );

        return $fullPath;
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
        // パスからサイズとファイル名を抽出
        // 例: "images/shops/medium/filename.jpg" → "medium/filename.jpg"
        $pathParts = explode('/', $path);
        $size = $pathParts[count($pathParts) - 2]; // サイズディレクトリ
        $filename = $pathParts[count($pathParts) - 1]; // ファイル名

        // APIのImageControllerを通したURLを生成（サイズ情報を含める）
        $appUrl = config('app.url');

        return "{$appUrl}/api/images/{$size}/{$filename}";
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
