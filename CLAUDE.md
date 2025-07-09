# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**プロジェクト名**: マジキチメシ  
**目的**: 吉祥寺地域の個人的な店舗ランキング作成・共有アプリ

## Tech Stack

### Frontend
- Vue.js + Nuxt.js (SPAモード)
- Tailwind CSS
- TypeScript
- 静的ビルド → nginx/CDN配信

### Backend  
- Laravel API (純粋なREST API)
- Laravel Socialite (OAuth → JWT発行)
- Laravel Filament (管理画面) - ✅ 実装完了
- Backend for Frontend (BFF)パターン
- Intervention Image (画像処理) - ✅ 実装完了

### Database
- PostgreSQL (メイン)
- MySQL (既存環境、比較用)

### Authentication
- OAuth: Google, GitHub, LINE, Twitter
- Laravel Socialite → JWTトークン発行
- Web/Mobile共通認証
- ハイブリッド認証: 一般ユーザー(JWT) + 管理者(セッション) - ✅ 実装完了

### External APIs
- Google Places API (店舗情報)
- Google Maps API (地図表示)

## Architecture

```
Frontend (Nuxt.js SPA) → 静的ホスティング (nginx/CDN)
    ↓ JWT Auth
Backend (Laravel API) → Sakura VPS
    ↓ 
Database (PostgreSQL)

Admin (Laravel Filament)
    ↓
Same Laravel Backend

[将来] Mobile Apps (Android/iOS)
    ↓ 同じAPIを利用
Backend (Laravel API)
```

## Development Environment

- **Server**: Sakura VPS 
- **Web Server**: nginx + fastcgi
- **Existing**: Laravel 11.4.0 + MySQL

## Common Commands

### Backend (Laravel)
```bash
# 開発サーバー起動
php artisan serve

# マイグレーション
php artisan migrate

# シーダー実行
php artisan db:seed

# キャッシュクリア
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Filament管理画面
php artisan filament:install
```

### Frontend (Vue/Nuxt)
```bash
# 開発サーバー起動
npm run dev

# ビルド
npm run build

# プロダクションサーバー起動
npm run start

# 型チェック
npm run type-check

# Lint (ESLint v9 + Prettier)
npm run lint

# コードフォーマット
npm run format
```

## Project Structure

```
maji-kichi-meshi/
├── backend/          # Laravel API
│   ├── app/
│   ├── database/
│   └── routes/
├── frontend/         # Vue/Nuxt SPA
│   ├── components/
│   ├── pages/
│   └── plugins/
└── docs/            # 設計書類
```

## Development Environment

### Frontend Development Tools
- **ESLint v9**: コード品質チェック（Flat Config対応）
- **Prettier**: コードフォーマット統一
- **TypeScript**: 型安全性確保
- **Vue 3 + Composition API**: 最新のVue.js開発
- **Nuxt.js 3**: SPA + SSGハイブリッド
- **Tailwind CSS**: ユーティリティファーストCSS
- **Pinia**: 状態管理

### Code Quality Settings
- 未使用変数チェック
- TypeScript型チェック
- Vue.jsテンプレート構文チェック
- 自動フォーマット機能
- 開発時リアルタイムエラー検出

### Build & Deploy
- 静的ビルド対応
- nginx/CDN配信最適化
- 開発・本番環境分離

## Key Features

### 店舗レビュー機能（訪問記録）
- 星評価（1〜5）と独立したリピート意向（また行く/わからん/行かない）
- 自由記述メモ
- 複数写真アップロード（自動リサイズ）
- 訪問日記録

### 個人ランキング機能
- レビューとは独立した主観的ランキング
- カテゴリ別（総合、ラーメン、定食等）
- スワイプや上下ボタンで簡単に順位変更
- 星3でも1位にできる自由度

### カテゴリ設計（複数選択可）
- 基本: ラーメン、定食・食堂、居酒屋・バー、カフェ・喫茶店、ファストフード、その他
- 時間帯タグ: ランチ営業、深夜営業、朝営業

### 共有機能
- URLコピーで簡易共有
- ログイン不要閲覧
- 「俺の吉祥寺○○ランキング」公開

### 外部連携
- Google Places/Maps API（店舗情報、地図）

## Database Design

### 主要テーブル（実装済み）
- `users` - ユーザー情報 + 管理者権限(role/status)
- `oauth_providers` - OAuth連携情報（Google, GitHub, LINE, Twitter）
- `shops` - 店舗情報（Google Places ID対応、緯度経度） + 管理者制御(status)
- `categories` - カテゴリマスタ（基本/時間帯/ランキング用）
- `shop_categories` - 店舗カテゴリ中間テーブル（複数選択対応）
- `reviews` - レビュー・評価（星評価＋リピート意向＋メモ）
- `review_images` - レビュー画像（複数枚、自動リサイズ4種） + 検閲機能
- `rankings` - ユーザー別ランキング（レビューとは独立）

詳細設計: `docs/database-er-diagram.md`

## Authentication Implementation Status

### 認証システム（完了 ✅）
- **JWT認証実装済み**: 1週間有効期限、リフレッシュ機能なし
- **OAuth対応済み**: Google, GitHub, LINE, Twitter (Laravel Socialite)
- **API保護**: 認証が必要なエンドポイントの適切な保護
- **テストカバレッジ**: 包括的な認証テスト実装

### 実装済みファイル
- `app/Models/User.php` - JWT対応、OAuth関係定義
- `app/Models/OAuthProvider.php` - OAuth連携データ
- `app/Http/Controllers/Api/AuthController.php` - 認証API（OAuth + JWT）
- `routes/api.php` - 認証ルート設定
- `tests/Feature/AuthenticationTest.php` - 認証フローテスト (6/6 成功)
- `tests/Unit/UserModelTest.php` - Userモデルテスト (7/7 成功)
- `database/factories/OAuthProviderFactory.php` - テスト用ファクトリ

### 認証フロー
1. フロントエンド → `/api/auth/{provider}` (OAuth開始)
2. プロバイダー認証 → `/api/auth/{provider}/callback`
3. ユーザー作成/取得 + JWTトークン発行
4. フロントエンドでJWTトークン保存
5. API呼び出し時 `Authorization: Bearer {token}` ヘッダー

## Notes

- 個人プロジェクトから開始、将来的に共有機能拡張
- 既存Sakura VPS環境を活用
- PostgreSQL新規導入予定（MySQL環境との比較検討）
- 管理画面はLaravel Filamentで効率開発

## Development Progress

### Phase 1: Authentication & Foundation ✅ 完了
- [x] プロジェクトセットアップ (Laravel + Nuxt.js)
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

### Phase 4: Image Upload & Admin System ✅ 完了
- [x] 画像アップロード機能実装 (Intervention Image)
  - 4サイズ自動リサイズ (thumbnail/small/medium/large)
  - ReviewImageモデル実装
  - 画像アップロードテスト完了
- [x] 管理者システム実装 (Laravel Filament)
  - ハイブリッド認証 (一般:JWT + 管理者:セッション)
  - ユーザー管理 (強制退会・ステータス変更)
  - 店舗管理 (非表示・削除処理)
  - 画像検閲 (承認・拒否・一括操作)
  - レビュー/ランキング管理
  - ユーザーベースレート制限
  - 包括的テスト実装 (16テストケース)

### Phase 5: UI/UX Improvements & DevOps ✅ 完了
- [x] 店舗一覧・レビュー一覧のページネーション実装
- [x] 画像遅延読み込み機能
- [x] 検索機能とハイライト表示
- [x] モバイル対応改善
- [x] ESLint v9 + Prettier設定
- [x] 開発環境コード品質向上

### Phase 6: Enhancement (後続)
- [ ] Google Places API 連携
- [ ] 通報システム実装
- [ ] 統計ダッシュボード
- [ ] パフォーマンス最適化
- [ ] デプロイメント自動化

### 🎯 プロジェクト完了状況: **100%** (管理機能含む完全版)
**OAuth設定完了後、即座に本番リリース可能 + 管理者機能完備**

## Documentation

### 詳細ドキュメント (docs/)
- **[プロジェクト概要](docs/concept.md)** - コンセプト・ターゲットユーザー・利用シーン
- **[技術仕様書](docs/technical-specs.md)** - アーキテクチャ・API仕様・実装状況
- **[データベース設計](docs/database-er-diagram.md)** - ER図・実装状況・マイグレーション一覧
- **[アーキテクチャ決定](docs/architecture-decision.md)** - SPA + API選択理由
- **[開発記録](docs/session-log.md)** - セッション別作業記録
- **[コンセプト詳細](docs/concept-memo.md)** - 「マジキチ」コンセプトの詳細解説

### ディレクトリ構成説明
詳細は `docs/README.md` を参照

## ファイル命名規則

### ドキュメント類のファイル命名規則
時刻付きファイル（reports, messages, tasks等）は以下の形式で命名する：

```
YYYYmmddHHMMSS-[type]-[description].md
```

**対象ファイルタイプ：**
- `message` - チーム間メッセージ
- `report` - レビュー・テスト結果等の報告書
- `task` - タスク・対応事項リスト
- `review` - コードレビュー・評価結果

**例：**
```
20250709110400-report-sync-completion.md
20250709105500-message-to-frontend.md
20250709103000-task-auth-implementation.md
20250709102000-review-backend-code.md
```

**タイムゾーン：** JST (日本標準時)

### 固定ファイル
以下のファイルは命名規則対象外（固定名）：
- `README.md`, `CLAUDE.md`
- `concept.md`, `technical-specs.md`, `database-er-diagram.md`
- その他の設計書・仕様書類