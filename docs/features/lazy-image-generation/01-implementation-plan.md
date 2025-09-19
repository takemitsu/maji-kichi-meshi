# 画像遅延生成機能 - 実装計画（改訂版）

## 画像サイズ戦略の変更

### 変更点
- **largeサイズ（1200x900）を廃止** → **オリジナル画像をそのまま配信**
- thumbnailのみ即座に生成、他は遅延生成

### 新サイズ構成
- **thumbnail** (150x150): アップロード時に即座に生成
- **small** (400x300): 初回アクセス時に遅延生成
- **medium** (800x600): 初回アクセス時に遅延生成
- **original**: リサイズなし（元画像そのまま）

## 実装タスク一覧

### Phase 1: データベース準備（30分）

#### 1.1 マイグレーション作成
```bash
php artisan make:migration add_sizes_generated_to_image_tables
```

マイグレーション内容:
- `review_images`テーブルに`sizes_generated` JSONカラム追加
- `shop_images`テーブルに`sizes_generated` JSONカラム追加
- デフォルト値: `{"thumbnail": false, "small": false, "medium": false}`
- `original_path`カラムを追加（元画像パス保存用）

### Phase 2: バックエンド実装（3時間）

#### 2.1 LazyImageService作成
新規ファイル: `app/Services/LazyImageService.php`

主要メソッド:
- `generateImageIfNeeded($model, $size)`: 画像が存在しない場合生成
- `isGenerated($model, $size)`: サイズが生成済みか確認
- `markAsGenerated($model, $size)`: 生成済みフラグ更新
- `generateSingleSize($originalPath, $size)`: 特定サイズのみ生成
- `getOriginalImage($model)`: オリジナル画像パス取得

#### 2.2 ImageControllerの改修
ファイル: `app/Http/Controllers/Api/ImageController.php`

変更内容:
- 新規メソッド`lazyServe()`追加
- `original`サイズリクエスト時は元画像を直接返す
- 他サイズは存在チェック後、必要なら生成
- ファイルロック機構で重複生成防止

#### 2.3 ImageService改修
ファイル: `app/Services/ImageService.php`

変更内容:
- `uploadAndResize()`メソッドを修正:
  - オリジナル画像を保存
  - thumbnailサイズのみ即座に生成
  - largeサイズ生成を削除
- `generateSingleSize()`メソッド追加（個別サイズ生成用）

#### 2.4 新規APIルート追加
ファイル: `routes/api.php`

```php
// 画像遅延生成エンドポイント
Route::get('/images/{type}/{id}/{size}', [ImageController::class, 'lazyServe'])
    ->where('type', 'reviews|shops')
    ->where('size', 'thumbnail|small|medium|original');
```

### Phase 3: モデル更新（30分）

#### 3.1 ReviewImageモデル
追加メソッド:
- `getSizePath($size)`: サイズ別パス取得
- `isSizeGenerated($size)`: サイズ生成済み確認
- `markSizeAsGenerated($size)`: 生成済みマーク

#### 3.2 ShopImageモデル
同様のメソッドを追加

### Phase 4: フロントエンド調整（1時間）

#### 4.1 画像URL生成ロジック変更
対象ファイル:
- `frontend/components/ShopCard.vue`
- `frontend/pages/reviews/[id]/index.vue`
- `frontend/pages/shops/[id]/index.vue`
- `frontend/components/ProfileImageUpload.vue`

変更内容:
- `urls.large`を`urls.original`に変更
- 画像URLを新しいエンドポイント形式に変更
- `/api/images/{type}/{id}/{size}`形式に統一

### Phase 5: テスト実装（2時間）

#### 5.1 Feature Test
新規ファイル: `tests/Feature/LazyImageGenerationTest.php`

テストケース:
- 画像が存在しない場合の生成テスト
- 既存画像の直接配信テスト
- 同時アクセス時の重複生成防止テスト
- 不正なサイズパラメータのエラーテスト

#### 5.2 Unit Test
新規ファイル: `tests/Unit/LazyImageServiceTest.php`

テストケース:
- サイズ生成状態の確認テスト
- フラグ更新テスト
- パス生成テスト

### Phase 6: 移行処理（30分）

#### 6.1 既存画像の移行コマンド
```bash
php artisan make:command MigrateExistingImages
```

処理内容:
- 既存の生成済み画像のフラグを更新
- 存在確認して`sizes_generated`を適切に設定

### Phase 7: デプロイ準備（30分）

#### 7.1 設定調整
- `.env`にキャッシュ期間設定追加
- `config/images.php`作成（画像関連設定集約）

#### 7.2 デプロイ手順書
- マイグレーション実行
- 既存画像移行コマンド実行
- キャッシュクリア

## 実装順序と依存関係

```
1. データベース準備（マイグレーション）
   ↓
2. モデル更新（sizes_generatedカラム対応）
   ↓
3. LazyImageService作成
   ↓
4. ImageController改修
   ↓
5. APIルート追加
   ↓
6. フロントエンド調整
   ↓
7. テスト実装・実行
   ↓
8. 既存画像移行処理
```

## リスク管理

### 想定されるトラブルと対策

1. **メモリ不足エラー**
   - 対策: `memory_limit`の一時的な増加
   - フォールバック: 低品質での再試行

2. **ファイルロック競合**
   - 対策: flock()使用、タイムアウト設定
   - エラー時は既存画像または404を返す

3. **ディスクI/O過負荷**
   - 対策: 生成キューの実装（将来）
   - 一時的な生成制限

## 成功基準

- [ ] アップロード時間が1秒以内に短縮
- [ ] 初回画像表示が2秒以内
- [ ] 全テストケースがパス
- [ ] 既存画像の表示に影響なし
- [ ] エラーログにクリティカルなエラーなし

## ロールバック計画

問題発生時の切り戻し手順:

1. 新規エンドポイントを無効化
2. ImageServiceを元の実装に戻す
3. フロントエンドのURL生成を元に戻す
4. データベースのカラムは残置（影響なし）

## 実装スケジュール

- **Day 1（4時間）**:
  - Phase 1-3: DB準備、バックエンド基本実装

- **Day 2（4時間）**:
  - Phase 4-5: フロントエンド調整、テスト実装
  - Phase 6-7: 移行処理、デプロイ準備