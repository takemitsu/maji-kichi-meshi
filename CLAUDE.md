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
- Laravel Filament (管理画面)
- Backend for Frontend (BFF)パターン

### Database
- PostgreSQL (メイン)
- MySQL (既存環境、比較用)

### Authentication
- OAuth: Google, GitHub, LINE, Twitter
- Laravel Socialite → JWTトークン発行
- Web/Mobile共通認証

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

# Lint
npm run lint
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
- `users` - ユーザー情報
- `oauth_providers` - OAuth連携情報（Google, GitHub, LINE, Twitter）
- `shops` - 店舗情報（Google Places ID対応、緯度経度）
- `categories` - カテゴリマスタ（基本/時間帯/ランキング用）
- `shop_categories` - 店舗カテゴリ中間テーブル（複数選択対応）
- `reviews` - レビュー・評価（星評価＋リピート意向＋メモ）
- `review_images` - レビュー画像（複数枚、自動リサイズ4種）
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

### Phase 1: Authentication & Foundation ✅
- [x] プロジェクトセットアップ (Laravel + Nuxt.js)
- [x] データベース設計・マイグレーション作成
- [x] JWT + OAuth認証システム実装（バックエンド）
- [x] 認証システムテスト作成
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