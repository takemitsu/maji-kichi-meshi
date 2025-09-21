<?php

namespace App\Services;

use App\Models\ReviewImage;
use App\Models\ShopImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class LazyImageService
{
    private ImageManager $manager;

    private array $sizes = [
        'thumbnail' => ['width' => 100, 'height' => 100],
        'small' => ['width' => 400, 'height' => 300],
        'medium' => ['width' => 800, 'height' => 600],
    ];

    public function __construct(ImageManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * 画像が存在しない場合は生成し、パスを返す
     */
    public function generateImageIfNeeded($model, string $size): ?string
    {
        // originalサイズは元画像をそのまま返す
        if ($size === 'original') {
            $originalPath = $this->getOriginalImagePath($model);

            // ファイルが存在するかチェック
            if ($originalPath && Storage::disk('public')->exists($originalPath)) {
                return $originalPath;
            }

            return null;
        }

        // サイズが対応していない場合
        if (!isset($this->sizes[$size])) {
            return null;
        }

        // 既に生成済みの場合は既存パスを返す
        if ($this->isGenerated($model, $size)) {
            return $this->getGeneratedImagePath($model, $size);
        }

        // 生成処理（ファイルロック付き）
        return $this->generateSingleSize($model, $size);
    }

    /**
     * 指定サイズが生成済みかチェック
     */
    public function isGenerated($model, string $size): bool
    {
        $sizesGenerated = $model->sizes_generated ?? [];

        if (is_string($sizesGenerated)) {
            $sizesGenerated = json_decode($sizesGenerated, true) ?? [];
        }

        return isset($sizesGenerated[$size]) && $sizesGenerated[$size] === true;
    }

    /**
     * 生成済みフラグを更新
     */
    public function markAsGenerated($model, string $size): void
    {
        $sizesGenerated = $model->sizes_generated ?? [];

        if (is_string($sizesGenerated)) {
            $sizesGenerated = json_decode($sizesGenerated, true) ?? [];
        }

        $sizesGenerated[$size] = true;
        $model->update(['sizes_generated' => $sizesGenerated]);
    }

    /**
     * 単一サイズを生成
     */
    private function generateSingleSize($model, string $size): ?string
    {
        try {
            $originalPath = $this->getOriginalImagePath($model);

            if (!$originalPath || !Storage::disk('public')->exists($originalPath)) {
                Log::error("Original image not found: {$originalPath}");

                return null;
            }

            // ファイルロック（ファイル名ベース）
            $lockFile = storage_path("app/locks/image_generation_{$model->filename}_{$size}.lock");
            $lockHandle = fopen($lockFile, 'w');

            if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
                // 他のプロセスが生成中の場合は少し待つ
                sleep(1);
                fclose($lockHandle);

                // 再度チェック
                if ($this->isGenerated($model, $size)) {
                    return $this->getGeneratedImagePath($model, $size);
                }

                return null;
            }

            // 生成処理
            $generatedPath = $this->performImageGeneration($model, $originalPath, $size);

            if ($generatedPath) {
                $this->markAsGenerated($model, $size);
            }

            // ロック解除
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            @unlink($lockFile);

            return $generatedPath;
        } catch (\Exception $e) {
            Log::error('Image generation failed: ' . $e->getMessage(), [
                'model_id' => $model->id,
                'size' => $size,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * 実際の画像生成処理
     */
    private function performImageGeneration($model, string $originalPath, string $size): string
    {
        $dimensions = $this->sizes[$size];

        // 保存先パスを決定
        $generatedPath = $this->getGeneratedImagePath($model, $size);
        $generatedDir = dirname($generatedPath);

        // ディレクトリ作成
        if (!Storage::disk('public')->exists($generatedDir)) {
            Storage::disk('public')->makeDirectory($generatedDir);
        }

        // 画像読み込み
        $originalFullPath = Storage::disk('public')->path($originalPath);
        $image = $this->manager->read($originalFullPath);

        // リサイズ
        $image->scaleDown(
            width: $dimensions['width'],
            height: $dimensions['height']
        );

        // 保存
        Storage::disk('public')->put(
            $generatedPath,
            $image->toJpeg(quality: 85)->toString()
        );

        return $generatedPath;
    }

    /**
     * オリジナル画像のパスを取得
     */
    public function getOriginalImagePath($model): ?string
    {
        if ($model instanceof ReviewImage) {
            return $model->original_path ?? $model->large_path;
        }

        if ($model instanceof ShopImage) {
            return $model->original_path ?? "images/shops/large/{$model->filename}";
        }

        return null;
    }

    /**
     * 生成された画像のパスを取得
     */
    private function getGeneratedImagePath($model, string $size): string
    {
        if ($model instanceof ReviewImage) {
            // 既存のパスがある場合はそれを使用
            switch ($size) {
                case 'thumbnail':
                    return $model->thumbnail_path;
                case 'small':
                    return $model->small_path;
                case 'medium':
                    return $model->medium_path;
            }
        }

        if ($model instanceof ShopImage) {
            return "images/shops/{$size}/{$model->filename}";
        }

        return '';
    }

    /**
     * サポートされているサイズかチェック
     */
    public function isSupportedSize(string $size): bool
    {
        return $size === 'original' || isset($this->sizes[$size]);
    }
}
