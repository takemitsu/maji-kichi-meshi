<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class ProfileImageService
{
    private ImageManager $manager;

    private array $sizes = [
        'small' => ['width' => 200, 'height' => 200],
    ];

    public function __construct(ImageManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * ユーザーのプロフィール画像をアップロード・リサイズ
     */
    public function uploadProfileImage(User $user, UploadedFile $file): bool
    {
        // 既存のプロフィール画像を削除
        $this->deleteProfileImage($user);

        try {
            // ファイル名生成（ユニークID + 元の拡張子）
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;

            // 保存先ディレクトリ
            $basePath = "images/profiles/{$user->id}";

            // 元画像を読み込み
            $image = $this->manager->read($file->getPathname());

            $paths = [];

            // 各サイズで画像を生成・保存
            foreach ($this->sizes as $size => $dimensions) {
                // 毎回元画像から読み込み直し（clone使用せず）
                $resizedImage = $this->manager->read($file->getPathname());

                // 正方形にクロップしてリサイズ（プロフィール画像は正方形が一般的）
                $resizedImage->cover(
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
                    $resizedImage->toJpeg(quality: 90)->toString()
                );

                $paths[$size] = $fullPath;
            }

            // ユーザーモデルを更新（smallサイズのみ）
            $user->update([
                'profile_image_filename' => $filename,
                'profile_image_original_name' => $file->getClientOriginalName(),
                'profile_image_small_path' => $paths['small'],
                'profile_image_file_size' => $file->getSize(),
                'profile_image_mime_type' => $file->getMimeType(),
                'profile_image_uploaded_at' => now(),
                // 不要なカラムはnullに設定
                'profile_image_thumbnail_path' => null,
                'profile_image_medium_path' => null,
                'profile_image_large_path' => null,
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Profile image upload failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * ユーザーのプロフィール画像を削除
     */
    public function deleteProfileImage(User $user): bool
    {
        if (!$user->hasProfileImage()) {
            return true;
        }

        try {
            // 物理ファイルを削除（smallサイズのみ）
            if ($user->profile_image_small_path && Storage::disk('public')->exists($user->profile_image_small_path)) {
                Storage::disk('public')->delete($user->profile_image_small_path);
            }

            // ユーザーのプロフィール画像情報をクリア
            $user->update([
                'profile_image_filename' => null,
                'profile_image_original_name' => null,
                'profile_image_thumbnail_path' => null,
                'profile_image_small_path' => null,
                'profile_image_medium_path' => null,
                'profile_image_large_path' => null,
                'profile_image_file_size' => null,
                'profile_image_mime_type' => null,
                'profile_image_uploaded_at' => null,
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Profile image deletion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * プロフィール画像のURLを取得
     */
    public function getProfileImageUrl(User $user, string $size = 'small'): ?string
    {
        return $user->getProfileImageUrl($size);
    }

    /**
     * アップロード用のバリデーションルール
     */
    public function getValidationRules(): array
    {
        return [
            'profile_image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:10240', // 10MB
                'dimensions:min_width=100,min_height=100,max_width=3000,max_height=3000',
            ],
        ];
    }
}
