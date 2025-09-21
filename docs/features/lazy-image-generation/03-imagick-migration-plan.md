# ImageMagick移行プラン

## 背景・問題点

### 現在の実装の問題
1. **GDドライバーのメモリ問題**
   - GDは画像全体をメモリに展開して処理
   - 大容量画像でメモリ不足が発生しやすい

2. **不要なclone操作**
   - `ImageService.php`で`clone $image`によりメモリ使用量が2倍に
   - 画像処理時に不必要なメモリ消費

### 影響範囲
- `backend/app/Services/ImageService.php`
- `backend/app/Services/LazyImageService.php`
- `backend/app/Services/ProfileImageService.php`

## 移行計画

### Phase 1: 環境準備

#### ローカル環境 (macOS)
```bash
# ImageMagick本体のインストール（CLIツールのみで十分）
brew install imagemagick

# 確認
which magick    # ImageMagick 7.x
which convert   # ImageMagick 6.x互換コマンド
```

**注意**: PHP拡張（pecl install imagick）は不要です。Intervention/Image v3はCLI経由で動作します。

#### 本番環境 (Sakura VPS / Ubuntu)
```bash
# ImageMagick本体のインストール
sudo apt-get update
sudo apt-get install imagemagick

# PHP拡張もインストールした場合（オプション）
sudo apt-get install php-imagick
sudo systemctl restart php8.3-fpm  # PHP再起動が必要

# 確認
which magick
which convert
php -m | grep imagick  # 拡張をインストールした場合
```

**注意**:
- Intervention/Image v3はImageMagickのCLIコマンドを使用するため、PHP拡張（php-imagick）は必須ではありません
- ただし、本番環境では既にphp-imagickをインストール済みのため、PHP-FPMの再起動を実施済み

### Phase 2: Laravel統合パッケージのインストール

```bash
cd backend

# Laravel統合パッケージのインストール
composer require intervention/image-laravel

# 設定ファイルの発行
php artisan vendor:publish --provider="Intervention\Image\Laravel\ServiceProvider"
```

これにより `config/image.php` が作成されます。

### Phase 3: 設定ファイルの編集

#### config/image.php
```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    | Intervention Image supports "GD Library" and "Imagick" to process images.
    | Imagick uses less memory and is more efficient for large images.
    */
    'driver' => \Intervention\Image\Drivers\Imagick\Driver::class,
];
```

### Phase 4: コード修正

#### 1. ImageService.php
```php
// 変更前
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

public function __construct()
{
    $this->manager = new ImageManager(new Driver);
}

// generateSingleSizeメソッド内
$resizedImage = clone $image; // 不要なclone

// 変更後
use Intervention\Image\Laravel\Facades\Image;
// または
use Intervention\Image\ImageManager;

public function __construct(ImageManager $manager)
{
    $this->manager = $manager; // DIで注入
}

// generateSingleSizeメソッドの修正
// cloneを削除して直接処理
$image->scaleDown(
    width: $dimensions['width'],
    height: $dimensions['height']
);
```

**注意**: このファイルは遅延生成実装で既に最適化済み（cloneを使用せず、各サイズごとに個別処理）

#### 2. LazyImageService.php
```php
// 変更前
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

public function __construct()
{
    $this->manager = new ImageManager(new Driver);
}

// 変更後
use Intervention\Image\ImageManager;

public function __construct(ImageManager $manager)
{
    $this->manager = $manager; // DIで注入
}
```
※ このファイルは既にcloneを使用していない正しい実装

#### 3. ProfileImageService.php
```php
// 変更前
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

public function __construct()
{
    $this->manager = new ImageManager(new Driver);
}

// 変更後
use Intervention\Image\ImageManager;

public function __construct(ImageManager $manager)
{
    $this->manager = $manager; // DIで注入
}
```

### Phase 5: サービスプロバイダーの登録確認

`config/app.php` の providers 配列に自動追加されているか確認：
```php
'providers' => [
    // ...
    Intervention\Image\Laravel\ServiceProvider::class,
],
```

### Phase 6: テスト実施

#### 1. 既存テストの実行
```bash
cd backend

# 画像関連テストのみ実行
php artisan test --filter ImageUploadTest
php artisan test --filter LazyImageGenerationTest
php artisan test --filter ProfileApiTest
php artisan test --filter ShopImageTest

# 全テスト実行
php artisan test
```

#### 2. メモリ使用量の計測
```php
// テストコード例
$memoryBefore = memory_get_usage(true);

// 大容量画像（例: 10MB）のアップロード処理
$response = $this->postJson('/api/reviews/1/images', [
    'image' => UploadedFile::fake()->image('large.jpg', 4000, 3000)
]);

$memoryAfter = memory_get_usage(true);
$memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024;

echo "Memory used: {$memoryUsed} MB\n";
```

### Phase 7: デプロイ

#### 1. 本番環境の準備
```bash
# ImageMagick拡張の確認
ssh production
php -m | grep imagick

# なければインストール
sudo apt-get install php-imagick
sudo systemctl restart php8.2-fpm
```

#### 2. デプロイ手順
```bash
# 1. コードをデプロイ
git pull origin main

# 2. Composerパッケージ更新（必要に応じて）
composer install --no-dev

# 3. キャッシュクリア
php artisan config:clear
php artisan cache:clear

# 4. 動作確認
# - 画像アップロードテスト
# - 遅延生成テスト
# - プロフィール画像アップロードテスト
```

## 期待される効果

### メモリ使用量の改善
- **改善前（GD + 不要なclone）**:
  - 10MB画像 → 約40-60MBのメモリ使用
  - cloneによる無駄なメモリ消費

- **改善後（ImageMagick + clone削除）**:
  - 10MB画像 → 約15-20MBのメモリ使用
  - 約60-70%のメモリ削減
  - 遅延生成では各サイズを個別処理するため、cloneは不要

### パフォーマンス向上
- 大容量画像の処理速度向上
- メモリ不足エラーの解消
- 同時処理能力の向上

## リスクと対策

### リスク
1. **環境依存**
   - ImageMagick拡張が必要
   - 一部のホスティングでは利用不可

2. **互換性**
   - 画像処理結果の微妙な差異
   - 一部のフォーマットで挙動の違い

### 対策
1. **Laravel統合パッケージによる一元管理**
   - `config/image.php`で設定を一元化
   - DIによる自動注入でドライバー選択を統一

2. **段階的移行**
   - 開発環境で十分にテスト
   - ステージング環境で検証
   - 本番環境へ慎重にデプロイ

## チェックリスト

- [x] ImageMagick本体のインストール（開発環境）
- [x] PHP imagick拡張のインストール（PECL経由、phpenv環境対応）
- [x] php.ini設定追加（extension=imagick.so）
- [x] config/image.phpでImageMagickドライバー設定
- [x] テストファイルのドライバー変更（5ファイル）
  - ImageUploadTest.php
  - ShopImageTest.php
  - ProfileApiTest.php
  - LazyImageGenerationTest.php
  - FilamentImageModerationTest.php
- [x] 既存テストの実行と成功確認（175テスト全成功）
- [x] PHPStan/Pint実行（エラーなし）
- [x] ImageMagick確認（本番環境）※php-imagickもインストール済み
- [ ] 本番環境へのデプロイ
- [ ] 本番環境での動作確認
- [ ] メモリ使用量の計測と改善確認

## 参考資料

- [Intervention Image v3 ドキュメント](https://image.intervention.io/v3)
- [ImageMagick vs GD 比較](https://stackoverflow.com/questions/4900448/imagemagick-vs-gd)
- [PHP ImageMagick拡張](https://www.php.net/manual/ja/book.imagick.php)