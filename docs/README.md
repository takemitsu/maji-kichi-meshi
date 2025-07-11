# プロジェクトドキュメント

このディレクトリには、マジキチメシプロジェクトの設計・開発に関するドキュメントが格納されています。

## ドキュメント構成

### 設計・仕様書
- **[concept.md](./concept.md)** - プロジェクト概要とコンセプト
- **[technical-specs.md](./technical-specs.md)** - 技術仕様書・実装済み機能
- **[database-er-diagram.md](./database-er-diagram.md)** - データベース設計・ER図

### 開発記録
- **[session-log.md](./session-log.md)** - 開発セッションの作業記録
- **[planning-log.md](./planning-log.md)** - 計画・検討事項の記録
- **[development-decisions.md](./development-decisions.md)** - 開発中の決定事項
- **[architecture-decision.md](./architecture-decision.md)** - アーキテクチャ選択の理由

### 詳細メモ
- **[concept-memo.md](./concept-memo.md)** - 「マジキチ」コンセプトの詳細解説

### デプロイメント・設定ガイド
- **[deployment-frontend-guide.md](./deployment-frontend-guide.md)** - フロントエンド静的配信設定
- **[oauth-setup-guide.md](./oauth-setup-guide.md)** - OAuth認証設定ガイド
- **[development-progress.md](./development-progress.md)** - 開発進捗・完了機能一覧

### 完了済みレビュー・レポート
時刻付きファイル（20250709〜20250711）による詳細な実装記録・レビュー結果は本番運用には不要のため整理済み

### ファイル命名規則

#### 時刻付きファイルの命名規則
時刻付きファイル（reports, messages, tasks等）は以下の形式で命名する：

```
YYYYmmddHHMMSS-[type]-[description].md
```

**対象ファイルタイプ：**
- `message` - チーム間メッセージ
- `report` - レビュー・テスト結果等の報告書
- `task` - タスク・対応事項リスト
- `review` - コードレビュー・評価結果

**タイムゾーン：** JST (日本標準時)

**例：**
```
20250709110400-report-sync-completion.md
20250709105500-message-to-frontend.md
20250709103000-task-auth-implementation.md
20250709102000-review-backend-code.md
```

#### 固定ファイル
以下のファイルは命名規則対象外（固定名）：
- `README.md`, `CLAUDE.md`
- `concept.md`, `technical-specs.md`, `database-er-diagram.md`
- その他の設計書・仕様書類

## プロジェクト状況

### 🎯 プロジェクト完了状況: **100%** (コア機能完了)
**OAuth設定完了後、即座に本番リリース可能**

### Phase 1: Authentication & Foundation ✅ 完了
- [x] プロジェクト基盤構築 (Laravel + Nuxt.js)
- [x] データベース設計・マイグレーション作成 (9テーブル)
- [x] JWT + OAuth認証システム実装（バックエンド）
- [x] 認証システムテスト作成 (13/13 成功)
- [x] フロントエンド基本設定（SPA、Tailwind CSS、Pinia）
- [x] フロントエンド認証機能実装（OAuth連携、JWT管理）

### Phase 2: Business Logic API ✅ 完了  
- [x] 店舗管理API実装 (ShopController + Resource) - 9テスト成功
- [x] カテゴリ管理API実装 (CategoryController + Resource) - 10テスト成功
- [x] レビュー機能API実装 (ReviewController + Resource) - 13テスト成功
- [x] ランキング機能API実装 (RankingController + Resource) - 16テスト成功
- [x] 全CRUD操作 + 認証・認可・バリデーション (合計64テスト成功)
- [x] JWTエラーハンドリング修正 (500→401)
- [x] 統合テスト実施・問題解決 (98%成功率)

### Phase 3: Frontend Integration ✅ 完了
- [x] 基本レイアウト・ナビゲーション作成
- [x] 店舗管理機能（一覧・詳細・検索）
- [x] レビュー機能実装（表示・削除）
- [x] API統合完全対応（100%互換性確保）
- [x] 認証フロー統合テスト (認証エラー解決)
- [x] フロントエンド・バックエンド統合テスト完了

### Phase 4: Enhancement (後続)
- [ ] 画像アップロード機能実装 (Intervention Image)
- [ ] Google Places API 連携
- [ ] パフォーマンス最適化
- [ ] デプロイメント自動化

## 参考

プロジェクトルートの [CLAUDE.md](../CLAUDE.md) には、Claude Code用の開発ガイダンスが記載されています。