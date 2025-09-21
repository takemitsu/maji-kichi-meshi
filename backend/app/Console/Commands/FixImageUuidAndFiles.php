<?php

namespace App\Console\Commands;

use App\Models\ReviewImage;
use App\Models\ShopImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FixImageUuidAndFiles extends Command
{
    protected $signature = 'images:fix-uuid {--dry-run : Run without making changes}';

    protected $description = 'Fix UUID consistency between database and filenames for all images';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN mode - no changes will be made');
        }

        // 必要なカラムの存在確認
        $reviewColumns = ['uuid', 'thumbnail_path', 'small_path', 'medium_path', 'original_path'];
        $shopColumns = ['thumbnail_path', 'small_path', 'medium_path', 'original_path'];
        $missingColumns = [];

        foreach ($reviewColumns as $column) {
            if (!\Schema::hasColumn('review_images', $column)) {
                $missingColumns[] = "review_images.{$column}";
            }
        }

        foreach ($shopColumns as $column) {
            if (!\Schema::hasColumn('shop_images', $column)) {
                $missingColumns[] = "shop_images.{$column}";
            }
        }

        if (!empty($missingColumns)) {
            $this->error('❌ Required columns are missing:');
            foreach ($missingColumns as $column) {
                $this->error("  - {$column}");
            }
            $this->error('Please run migrations first: php artisan migrate');
            return Command::FAILURE;
        }

        $this->info('Starting UUID fix for ShopImage and ReviewImage...');

        // Fix ShopImages
        $this->fixShopImages($dryRun);

        // Fix ReviewImages
        $this->fixReviewImages($dryRun);

        return Command::SUCCESS;
    }

    private function fixShopImages(bool $dryRun): void
    {
        $this->info("\n📷 Processing ShopImages...");

        $images = ShopImage::all();
        $fixed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($images as $image) {
            try {
                // uuidとfilenameが異なる場合のみ修正
                $currentFilename = $image->filename;
                $expectedFilename = $image->uuid . '.' . pathinfo($currentFilename, PATHINFO_EXTENSION);

                if ($currentFilename === $expectedFilename) {
                    $this->line("✓ ShopImage {$image->id}: Already fixed");
                    $skipped++;
                    continue;
                }

                $this->info("Fixing ShopImage {$image->id}:");
                $this->line("  Current: {$currentFilename}");
                $this->line("  Target:  {$expectedFilename}");

                if (!$dryRun) {
                    DB::beginTransaction();
                    try {
                        // ファイル名を変更（全サイズを一度に処理）
                        $renamedFiles = $this->renameImageFiles('shops', $currentFilename, $expectedFilename);

                        if (empty($renamedFiles)) {
                            throw new \Exception('No files found to rename');
                        }

                        // データベース更新
                        $image->filename = $expectedFilename;
                        $image->thumbnail_path = "images/shops/thumbnail/{$expectedFilename}";
                        $image->small_path = "images/shops/small/{$expectedFilename}";
                        $image->medium_path = "images/shops/medium/{$expectedFilename}";
                        $image->original_path = "images/shops/original/{$expectedFilename}";
                        $image->save();

                        DB::commit();
                        $fixed++;
                    } catch (\Exception $e) {
                        DB::rollBack();

                        // ファイル操作のロールバック
                        foreach ($renamedFiles as $size => $paths) {
                            if (Storage::disk('public')->exists($paths['new'])) {
                                Storage::disk('public')->move($paths['new'], $paths['old']);
                                $this->warn("    Rolled back: {$size}");
                            }
                        }

                        throw $e;
                    }
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("  ❌ Failed: {$e->getMessage()}");
            }
        }

        $this->info("ShopImages: Fixed {$fixed}, Skipped {$skipped}, Failed {$failed}");
    }

    private function fixReviewImages(bool $dryRun): void
    {
        $this->info("\n📸 Processing ReviewImages...");

        $images = ReviewImage::all();
        $fixed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($images as $image) {
            try {
                // すでにUUIDが設定されていて、filenameと一致している場合はスキップ
                if ($image->uuid) {
                    $expectedFilename = $image->uuid . '.' . pathinfo($image->filename, PATHINFO_EXTENSION);
                    if ($image->filename === $expectedFilename) {
                        $this->line("✓ ReviewImage {$image->id}: Already fixed");
                        $skipped++;
                        continue;
                    }
                }

                $currentFilename = $image->filename;
                $fileUuid = pathinfo($currentFilename, PATHINFO_FILENAME);

                // UUID形式をチェック
                if (!$this->isValidUuid($fileUuid)) {
                    $this->warn("ReviewImage {$image->id}: Invalid UUID format in filename: {$currentFilename}");
                    // 新しいUUIDを生成
                    $fileUuid = Str::uuid()->toString();
                }

                $newFilename = $fileUuid . '.' . pathinfo($currentFilename, PATHINFO_EXTENSION);

                $this->info("Fixing ReviewImage {$image->id}:");
                $this->line("  Current: {$currentFilename}");
                $this->line("  Target:  {$newFilename}");
                $this->line("  UUID:    {$fileUuid}");

                if (!$dryRun) {
                    DB::beginTransaction();
                    $renamedFiles = [];

                    try {
                        // ファイル名が変わる場合のみリネーム
                        if ($currentFilename !== $newFilename) {
                            $renamedFiles = $this->renameImageFiles('reviews', $currentFilename, $newFilename);

                            if (empty($renamedFiles)) {
                                throw new \Exception('No files found to rename');
                            }

                            $image->filename = $newFilename;
                            $image->thumbnail_path = "images/reviews/thumbnail/{$newFilename}";
                            $image->small_path = "images/reviews/small/{$newFilename}";
                            $image->medium_path = "images/reviews/medium/{$newFilename}";
                            $image->original_path = "images/reviews/original/{$newFilename}";
                        }

                        // UUID設定
                        $image->uuid = $fileUuid;
                        $image->save();

                        DB::commit();
                        $fixed++;
                    } catch (\Exception $e) {
                        DB::rollBack();

                        // ファイル操作のロールバック
                        foreach ($renamedFiles as $size => $paths) {
                            if (Storage::disk('public')->exists($paths['new'])) {
                                Storage::disk('public')->move($paths['new'], $paths['old']);
                                $this->warn("    Rolled back: {$size}");
                            }
                        }

                        throw $e;
                    }
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("  ❌ Failed: {$e->getMessage()}");
            }
        }

        $this->info("ReviewImages: Fixed {$fixed}, Skipped {$skipped}, Failed {$failed}");
    }

    private function renameImageFiles(string $type, string $oldFilename, string $newFilename): array
    {
        $sizes = ['thumbnail', 'small', 'medium', 'original'];
        $renamedFiles = [];

        foreach ($sizes as $size) {
            $oldPath = "images/{$type}/{$size}/{$oldFilename}";
            $newPath = "images/{$type}/{$size}/{$newFilename}";

            if (Storage::disk('public')->exists($oldPath)) {
                // 新しいパスに既にファイルが存在する場合はエラー
                if (Storage::disk('public')->exists($newPath)) {
                    throw new \Exception("Target file already exists: {$newPath}");
                }

                Storage::disk('public')->move($oldPath, $newPath);
                $this->line("    Renamed: {$size}/{$oldFilename} → {$newFilename}");

                $renamedFiles[$size] = [
                    'old' => $oldPath,
                    'new' => $newPath,
                ];
            }
        }

        return $renamedFiles;
    }

    private function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }
}
