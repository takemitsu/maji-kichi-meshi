# テストカバレッジ改善プラン

## 📊 現状分析

### 実施日
2025-10-15

### カバレッジ測定結果
```bash
XDEBUG_MODE=coverage php artisan test --coverage --min=0
```

**Total: 59.4%**

---

## 🔍 発覚した問題

### 最近のバグ発生パターン
1. **行きたいリスト機能** (2025-10-14)
   - 未ログイン時の表示バグ
   - オプショナル認証パターン不備
   - WishlistController のカバレッジ: **61.5%**

2. **いいね機能** (2025-10-15)
   - `likes_count` が常に 0 になる問題
   - `withCount('likes')` の実装漏れ
   - ReviewLikeController のカバレッジ: **86.2%**

### 共通パターン
- レスポンス内容の検証不足
- エッジケース・異常系のテスト不足
- カバレッジが低い箇所で問題が発生

---

## 📈 コントローラー別カバレッジ

### 🔴 危険度: 高（カバレッジ < 70%）

| Controller | Coverage | 未カバー行 | 優先度 |
|---|---|---|---|
| **ImageController** | **30.4%** | 28, 34, 46, 58-116, 122, 141-144 | 🔴 最高 |
| **WishlistController** | **61.5%** | 57-66, 83-85, 93-102, 123-125, 136-145, etc. | 🔴 高 |

### 🟡 危険度: 中（カバレッジ 70-85%）

| Controller | Coverage | 未カバー行 | 優先度 |
|---|---|---|---|
| RankingController | 70.8% | 29-30, 42, 80-81, 96-103, 115-116, etc. | 🟡 中 |
| AuthController | 76.0% | 90-98, 110, 114-115, 127, 133-134, etc. | 🟡 中 |
| ShopController | 81.1% | 205-209, 224-238 | 🟡 中 |

### 🟢 良好（カバレッジ > 85%）

| Controller | Coverage | 備考 |
|---|---|---|
| CategoryController | 100.0% | ✅ 完璧 |
| StatsController | 100.0% | ✅ 完璧 |
| UserController | 100.0% | ✅ 完璧 |
| ProfileController | 93.3% | ✅ 良好 |
| ReviewController | 91.3% | ✅ 良好 |
| ReviewLikeController | 86.2% | ✅ 今日改善 |

---

## 🎯 改善プラン

### Phase 1: 危険度の高いコントローラー（優先度：🔴 最高）

#### 1. ImageController (30.4% → 80%+)

**問題**:
- 画像アップロード/削除の70%が未テスト
- エラーハンドリングが未検証
- ファイル操作の異常系が未カバー

**追加すべきテストケース**:
```
- test_upload_fails_with_oversized_file (ファイルサイズ超過)
- test_upload_fails_with_invalid_mime_type (無効なMIMEタイプ)
- test_upload_fails_with_corrupted_image (破損した画像)
- test_delete_fails_with_nonexistent_image (存在しない画像の削除)
- test_delete_cleans_up_all_sizes (全サイズの削除確認)
- test_s3_upload_failure_handling (S3アップロード失敗)
- test_concurrent_upload_handling (同時アップロード)
```

**推定工数**: 2-3時間

---

#### 2. WishlistController (61.5% → 85%+)

**問題**:
- エラーハンドリング、バリデーション失敗が未テスト
- 最近バグが発覚した箇所

**追加すべきテストケース**:
```
- test_add_wishlist_fails_with_invalid_priority (無効なpriority値)
- test_add_wishlist_fails_with_nonexistent_shop (存在しないshop_id)
- test_update_priority_fails_with_out_of_range (範囲外の値)
- test_change_status_fails_with_invalid_status (無効なステータス)
- test_pagination_edge_cases (ページネーションのエッジケース)
- test_sort_with_invalid_parameter (無効なソートパラメータ)
```

**推定工数**: 1-2時間

---

### Phase 2: 中程度のリスク箇所（優先度：🟡 中）

#### 3. RankingController (70.8% → 85%+)

**追加すべきテストケース**:
```
- test_reorder_fails_with_invalid_order (無効な並び順)
- test_update_fails_with_unauthorized_user (権限エラー)
- test_publish_toggle_edge_cases (公開/非公開切り替え)
- test_delete_with_items (ランキングアイテム含む削除)
```

**推定工数**: 1-2時間

---

#### 4. ShopController (81.1% → 90%+)

**追加すべきテストケース**:
```
- test_search_with_multiple_filters (複数フィルタの組み合わせ)
- test_sort_with_edge_cases (ソート順のエッジケース)
- test_pagination_with_filters (フィルタ + ページネーション)
```

**推定工数**: 1時間

---

#### 5. ReviewLikeController (86.2% → 95%+)

**追加すべきテストケース**:
```
- test_toggle_like_fails_with_nonexistent_review (存在しないレビュー)
- test_my_likes_with_deleted_reviews (削除されたレビュー)
```

**推定工数**: 30分

---

### Phase 3: モデル層の改善（優先度：🟢 低）

#### 6. モデルの単体テスト追加

**対象**:
- RankingItem (50.0%)
- ReviewLike (50.0%)
- Category (66.7%)

**追加すべきテストケース**:
```
- モデルメソッドの単体テスト
- リレーションシップの検証
- スコープの動作確認
```

**推定工数**: 1時間

---

## 🛠️ 実装方針

### カバレッジ測定の継続

#### カバレッジレポート生成
```bash
# コンソール出力
XDEBUG_MODE=coverage php artisan test --coverage --min=80

# HTMLレポート生成（詳細分析用）
# 日付ごとにディレクトリを分けると履歴管理が容易
XDEBUG_MODE=coverage php artisan test --coverage-html ../docs/features/test-coverage-improvement/coverage-report-YYYY-MM-DD

# ブラウザでHTMLレポートを開く（macOS）
open /Users/takemitsusuzuki/work/personal/maji-kichi-meshi/docs/features/test-coverage-improvement/coverage-report-YYYY-MM-DD/index.html

# または直接ファイルパスを指定してブラウザで開く
# 例: open coverage-report-2025-10-15/index.html
```

#### Xdebug設定の確認
```bash
# 現在の設定確認
php -i | grep "xdebug.mode"

# カバレッジモード有効化（環境変数）
export XDEBUG_MODE=coverage

# または php.ini で設定
# xdebug.mode=develop,coverage
```

---

### テスト追加の優先順位

1. **ImageController** - 最もリスクが高い（30.4%）
2. **WishlistController** - 最近バグが出た（61.5%）
3. **RankingController** - 複雑なビジネスロジック（70.8%）
4. **ShopController** - 検索・フィルタの組み合わせ（81.1%）
5. **ReviewLikeController** - 細かい改善（86.2%）
6. **モデル層** - 基礎の強化

---

### 品質基準

#### 必須
- **正常系**: 基本的な動作確認
- **主要な異常系**: バリデーションエラー、権限エラー

#### 推奨
- **エッジケース**: 境界値、空データ、NULL
- **バリデーション**: 全てのバリデーションルールの検証
- **データ隔離**: 複数ユーザーのデータ分離

#### 理想
- **全てのif/elseブランチ**: 分岐条件の完全カバー
- **例外処理**: try-catch の全パターン
- **並行処理**: 同時実行時の動作

---

## 📊 期待される効果

### 1. バグの早期発見
- 実装漏れを開発時に検出
- デプロイ前に問題を修正
- フロントエンドでの `undefined` エラーを防止

### 2. リファクタリングの安全性
- カバレッジがあれば安心して修正できる
- 破壊的変更の影響範囲を把握
- デグレーションを自動検出

### 3. 仕様のドキュメント化
- テストがAPIの仕様書になる
- 新規メンバーのオンボーディングが容易
- エンドポイントの挙動が明確

### 4. 回帰テストの自動化
- 過去のバグの再発を防止
- CI/CDでの自動検証
- リリース前の品質保証

---

## 📝 次のステップ

### 短期（次回セッション）
1. **Phase 1-1**: ImageController のテスト追加
   - 正常系の確認
   - 異常系（ファイルサイズ、MIME type）の追加
   - カバレッジ 80%+ を目指す

### 中期（1週間以内）
2. **Phase 1-2**: WishlistController のテスト追加
3. **Phase 2**: RankingController, ShopController の改善

### 長期（1ヶ月以内）
4. **Phase 3**: モデル層の強化
5. **継続的改善**: 新機能開発時は必ずカバレッジ 80%+ を維持

---

## 🔄 継続的な品質管理

### CI/CDでのカバレッジチェック
```yaml
# .github/workflows/tests.yml (例)
- name: Run tests with coverage
  run: |
    XDEBUG_MODE=coverage php artisan test --coverage --min=80
```

### プルリクエスト時のチェック
- 新規コードは必ずテストを追加
- カバレッジが下がるPRは原則マージしない
- 例外がある場合は理由を明記

### 定期的なレビュー
- 月次でカバレッジレポートを確認
- カバレッジが下がった箇所を特定
- 計画的にテストを追加

---

## 📚 参考資料

### Laravel Testing
- https://laravel.com/docs/11.x/testing
- https://laravel.com/docs/11.x/http-tests

### PHPUnit
- https://docs.phpunit.de/en/11.0/

### Xdebug Coverage
- https://xdebug.org/docs/code_coverage

---

## 🎯 目標

**6ヶ月後の目標カバレッジ: 80%以上**

| 期間 | 目標 | 現状 |
|---|---|---|
| 現在 | - | 59.4% |
| 1ヶ月後 | ImageController 80%+ | 30.4% |
| 2ヶ月後 | 全Controller 70%+ | - |
| 3ヶ月後 | 全Controller 80%+ | - |
| 6ヶ月後 | Total 80%+ | - |
