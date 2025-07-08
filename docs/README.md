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

## プロジェクト状況

### Phase 1: Authentication & Foundation ✅
- [x] プロジェクト基盤構築 (Laravel + Nuxt.js)
- [x] データベース設計・マイグレーション作成 (8テーブル)
- [x] JWT + OAuth認証システム実装（バックエンド）
- [x] 認証システムテスト作成 (13/13 成功)
- [x] フロントエンド基本設定（SPA、Tailwind CSS、Pinia）
- [x] フロントエンド認証機能実装（OAuth連携、JWT管理）

### Phase 2: Frontend Core Features (進行中)
- [ ] 基本レイアウト・ナビゲーション作成
- [ ] 店舗管理機能（一覧・詳細・検索）
- [ ] レビュー機能実装
- [ ] ランキング機能実装

### Phase 3: Backend API Enhancement (後続)
- [ ] 店舗管理API実装
- [ ] レビュー機能API実装  
- [ ] ランキング機能API実装
- [ ] 画像アップロード実装

## 参考

プロジェクトルートの [CLAUDE.md](../CLAUDE.md) には、Claude Code用の開発ガイダンスが記載されています。