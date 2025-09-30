<?php

namespace App\Console\Commands;

use App\Models\ShopImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MigrateShopImageData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shop-images:migrate-data {--dry-run : Run without actually updating database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate ShopImage data from image_sizes JSON to individual path columns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Running in DRY RUN mode - no changes will be made');
        }

        // 必要なカラムの存在確認
        $requiredColumns = ['thumbnail_path', 'small_path', 'medium_path', 'large_path', 'original_path', 'sizes_generated'];
        $missingColumns = [];

        foreach ($requiredColumns as $column) {
            if (!\Schema::hasColumn('shop_images', $column)) {
                $missingColumns[] = $column;
            }
        }

        if (!empty($missingColumns)) {
            $this->error('❌ Required columns are missing in shop_images table:');
            foreach ($missingColumns as $column) {
                $this->error("  - {$column}");
            }
            $this->error('Please run migrations first: php artisan migrate');

            return Command::FAILURE;
        }

        $totalCount = ShopImage::count();

        if ($totalCount === 0) {
            $this->info('No shop images found to migrate.');

            return Command::SUCCESS;
        }

        $this->info("Starting migration of {$totalCount} shop images...");

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $migratedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        $errors = [];

        ShopImage::chunk(100, function ($images) use ($bar, $dryRun, &$migratedCount, &$skippedCount, &$failedCount, &$errors) {
            foreach ($images as $image) {
                try {
                    // 既に移行済みの場合はスキップ（冪等性）
                    if ($image->thumbnail_path) {
                        $skippedCount++;
                        $bar->advance();
                        continue;
                    }

                    // ファイル名が存在しない場合はスキップ
                    if (!$image->filename) {
                        $this->warn("\nImage ID {$image->id} has no filename, skipping...");
                        $skippedCount++;
                        $bar->advance();
                        continue;
                    }

                    $updates = [
                        // パス設定（ReviewImageと同じ構造）
                        'thumbnail_path' => "images/shops/thumbnail/{$image->filename}",
                        'small_path' => "images/shops/small/{$image->filename}",
                        'medium_path' => "images/shops/medium/{$image->filename}",
                        'large_path' => null, // largeは廃止
                        'original_path' => "images/shops/original/{$image->filename}",

                        // 遅延生成フラグ（thumbnailのみ生成済み）
                        'sizes_generated' => [
                            'thumbnail' => true,
                            'small' => false,
                            'medium' => false,
                        ],
                    ];

                    // moderation_statusは既にマイグレーションで設定済み

                    if (!$dryRun) {
                        $image->update($updates);
                    }

                    $migratedCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errorMessage = "Failed to migrate image {$image->id}: {$e->getMessage()}";
                    $errors[] = $errorMessage;

                    if (!$dryRun) {
                        Log::error('ShopImage migration failed', [
                            'id' => $image->id,
                            'filename' => $image->filename,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        // 結果サマリー
        $this->info('Migration completed!');
        $this->info("✅ Migrated: {$migratedCount}");
        $this->info("⏭️  Skipped (already migrated): {$skippedCount}");

        if ($failedCount > 0) {
            $this->error("❌ Failed: {$failedCount}");
            $this->error('Failed items:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a DRY RUN - no changes were made.');
            $this->info('Run without --dry-run to actually migrate data.');
        }

        return $failedCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
