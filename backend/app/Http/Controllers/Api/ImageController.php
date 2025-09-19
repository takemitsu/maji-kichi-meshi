<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReviewImage;
use App\Models\ShopImage;
use App\Services\LazyImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    private LazyImageService $lazyImageService;

    public function __construct(LazyImageService $lazyImageService)
    {
        $this->lazyImageService = $lazyImageService;
    }

    /**
     * 遅延生成対応の画像配信エンドポイント
     */
    public function lazyServe(Request $request, string $type, int $id, string $size)
    {
        // サイズの妥当性チェック
        if (!$this->lazyImageService->isSupportedSize($size)) {
            abort(400, 'Invalid image size');
        }

        // 画像モデルを取得
        $model = $this->getImageModel($type, $id);
        if (!$model) {
            abort(404, 'Image not found');
        }

        // モデレーション状態チェック
        if (!$model->isPublished()) {
            abort(403, 'Image not available');
        }

        // 画像生成またはパス取得
        $imagePath = $this->lazyImageService->generateImageIfNeeded($model, $size);

        if (!$imagePath) {
            abort(404, 'Image could not be generated or found');
        }

        return $this->respondWithImage($imagePath);
    }

    /**
     * 既存の画像配信（後方互換性用）
     */
    public function serve(Request $request, string $size, string $filename)
    {
        // ReviewImageから検索
        $reviewImage = ReviewImage::where(function ($query) use ($filename) {
            $query->where('thumbnail_path', 'like', '%' . $filename)
                ->orWhere('small_path', 'like', '%' . $filename)
                ->orWhere('medium_path', 'like', '%' . $filename)
                ->orWhere('large_path', 'like', '%' . $filename);
        })->first();

        if ($reviewImage) {
            return $this->serveReviewImage($reviewImage, $size, $filename);
        }

        // ShopImageから検索
        $shopImage = ShopImage::where('filename', $filename)->first();

        if ($shopImage) {
            return $this->serveShopImage($shopImage, $size, $filename);
        }

        abort(404, 'Image not found');
    }

    private function serveReviewImage(ReviewImage $reviewImage, string $size, string $filename)
    {
        // モデレーション状態チェック
        if (!$reviewImage->isPublished()) {
            abort(403, 'Image not available');
        }

        // サイズに基づいて適切なパスを取得
        $path = null;
        switch ($size) {
            case 'thumbnail':
                $path = $reviewImage->thumbnail_path;
                break;
            case 'small':
                $path = $reviewImage->small_path;
                break;
            case 'medium':
                $path = $reviewImage->medium_path;
                break;
            case 'large':
                $path = $reviewImage->large_path;
                break;
        }

        return $this->respondWithImage($path);
    }

    private function serveShopImage(ShopImage $shopImage, string $size, string $filename)
    {
        // モデレーション状態チェック
        if (!$shopImage->isPublished()) {
            abort(403, 'Image not available');
        }

        // サイズに基づいて適切なパスを決定
        $path = "images/shops/{$size}/{$filename}";

        return $this->respondWithImage($path);
    }

    private function respondWithImage(string $path)
    {
        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'Image file not found');
        }

        $file = Storage::disk('public')->get($path);
        $mimeType = Storage::disk('public')->mimeType($path);

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000'); // 1年キャッシュ
    }

    /**
     * 画像モデルを取得
     */
    private function getImageModel(string $type, int $id)
    {
        switch ($type) {
            case 'reviews':
                return ReviewImage::find($id);
            case 'shops':
                return ShopImage::find($id);
            default:
                return;
        }
    }
}
