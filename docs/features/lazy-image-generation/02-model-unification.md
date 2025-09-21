# モデル処理統一実装

**実装日**: 2025-09-21
**作業者**: Claude Code
**ステータス**: ✅ 完了

## 📋 概要

ReviewImageとShopImageモデル間の処理ロジックを統一し、コードの一貫性と保守性を向上させる作業。

## 🎯 目的

1. **ビジネスロジックの統一**: 両モデルで同じインターフェースを持つメソッドを実装
2. **冗長なコードの削除**: 不要なURLゲッターメソッドの削除
3. **管理画面の一貫性**: Filamentリソースでの統一された操作

## 🔧 実装内容

### 1. モデレーション機能の統一

#### ReviewImageモデルへの追加

```php
// app/Models/ReviewImage.php

public function approve($moderatorId = null): bool
{
    return $this->updateModerationStatus('published', $moderatorId);
}

public function reject($moderatorId = null): bool
{
    return $this->updateModerationStatus('rejected', $moderatorId);
}

public function requireReview($moderatorId = null): bool
{
    return $this->updateModerationStatus('under_review', $moderatorId);
}

private function updateModerationStatus(string $status, $moderatorId = null): bool
{
    $this->moderation_status = $status;
    $this->moderated_by = $moderatorId;
    $this->moderated_at = now();

    return $this->save();
}
```

### 2. URL処理の簡素化

#### 削除した個別URLゲッター

両モデルから以下のメソッドを削除:
- `getThumbnailUrlAttribute()`
- `getSmallUrlAttribute()`
- `getMediumUrlAttribute()`
- `getLargeUrlAttribute()`
- `getOriginalUrlAttribute()`

#### 統一されたURL配列アクセサ

```php
public function getUrlsAttribute()
{
    $appUrl = config('app.url');
    $filename = $this->filename;

    return [
        'thumbnail' => "{$appUrl}/api/images/reviews/thumbnail/{$filename}",
        'small' => "{$appUrl}/api/images/reviews/small/{$filename}",
        'medium' => "{$appUrl}/api/images/reviews/medium/{$filename}",
        'original' => "{$appUrl}/api/images/reviews/original/{$filename}",
        'large' => "{$appUrl}/api/images/reviews/original/{$filename}", // 後方互換性
    ];
}
```

### 3. Filamentリソースの更新

```php
// app/Filament/Resources/ReviewImageResource.php

Tables\Actions\Action::make('view_image')
    ->label('画像表示')
    ->icon('heroicon-o-eye')
    ->url(fn (ReviewImage $record): string => $record->urls['medium']) // 変更
    ->openUrlInNewTab(),
```

### 4. ShopImageの自動承認実装

```php
// app/Models/ShopImage.php

public static function createFromUpload(int $shopId, UploadedFile $file): self
{
    // ... 画像アップロード処理

    $shopImage = self::create([
        // ... 他のフィールド
        'moderation_status' => 'published', // 自動承認
    ]);

    return $shopImage;
}
```

## 🧪 テスト実装

### FilamentImageModerationTest

新規作成したテストファイルで以下を検証:

1. **モデレーションアクション**
   - ReviewImage承認/拒否が正しく動作
   - ShopImage承認/拒否が正しく動作
   - モデレーターIDと日時が記録される

2. **一括操作**
   - 複数画像の一括承認
   - 複数画像の一括拒否

3. **表示制御**
   - 承認ボタンは未承認画像のみ表示
   - 拒否ボタンは未拒否画像のみ表示

4. **URL処理**
   - `urls`配列からのURL取得が正しく動作

### テスト結果

```bash
php artisan test --filter=FilamentImageModerationTest
```

✅ 全10テスト成功

## 📊 影響範囲

### 修正ファイル
- `app/Models/ReviewImage.php`
- `app/Models/ShopImage.php`
- `app/Filament/Resources/ReviewImageResource.php`
- `database/factories/ReviewImageFactory.php` (UUID追加)

### 新規ファイル
- `tests/Feature/FilamentImageModerationTest.php`

### APIレスポンスへの影響
- `filename`フィールドがレスポンスに含まれるようになった（hiddenから削除）
- URL取得は`urls`配列経由で統一

## ✅ 達成事項

- [x] ReviewImageにモデレーションメソッド追加
- [x] 個別URLゲッターメソッド削除
- [x] Filamentリソースの統一された実装
- [x] 包括的なテスト実装
- [x] ShopImageの自動承認実装

## 📝 今後の検討事項

1. **トレイト化の検討**
   - モデレーション機能をトレイトとして抽出
   - 他のモデルでも再利用可能に

2. **インターフェース定義**
   - Moderatableインターフェースの作成
   - より厳密な型定義

3. **イベント発火**
   - 承認/拒否時のイベント発火
   - 通知システムとの連携

## 🔍 関連ドキュメント

- [画像遅延生成機能 README](./README.md)
- [実装計画](./01-implementation-plan.md)
- [進捗管理](./progress.md)