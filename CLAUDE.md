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

## Notes

- 個人プロジェクトから開始、将来的に共有機能拡張
- 既存Sakura VPS環境を活用
- PostgreSQL新規導入予定（MySQL環境との比較検討）
- 管理画面はLaravel Filamentで効率開発