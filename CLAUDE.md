# CLAUDE.md

このファイルはClaude Code用の開発ガイダンスです。

## Project Overview

**マジキチメシ** - 吉祥寺地域の個人的な店舗ランキング作成・共有アプリ

## Tech Stack

**Frontend**: Vue.js 3 + Nuxt.js 3 (SPA) / Tailwind CSS / TypeScript
**Backend**: Laravel 11 API / Laravel Filament (管理画面) / Intervention Image
**Database**: MySQL (本番) / SQLite (開発)
**Cache**: Redis (Rate Limiter専用)
**Auth**: OAuth (Google) → JWT / ハイブリッド認証 (一般:JWT + 管理者:セッション)

## Architecture

```
Frontend (Nuxt.js SPA) → 静的ホスティング (nginx/CDN)
    ↓ JWT Auth
Backend (Laravel API) → Sakura VPS
    ↓ 
Database (MySQL)

Admin (Laravel Filament)
    ↓
Same Laravel Backend

[将来] Mobile Apps (Android/iOS)
    ↓ 同じAPIを利用
Backend (Laravel API)
```


## Common Commands

### Backend (Laravel)
```bash
php artisan serve              # 開発サーバー起動
php artisan migrate:fresh --seed  # DB初期化
php artisan test               # テスト実行
composer pint                  # コードフォーマット
composer stan                  # 静的解析
```

### Frontend (Vue/Nuxt)
```bash
npm run dev        # 開発サーバー起動
npm run build      # ビルド
npm run lint       # Lint + フォーマット
npm run type-check # 型チェック
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

- **レビュー機能**: 星評価・リピート意向・写真アップロード（自動リサイズ）
- **ランキング機能**: カテゴリ別主観的ランキング・簡単順位変更
- **カテゴリ**: 複数選択可能（ラーメン、定食、居酒屋等 + 時間帯タグ）
- **共有機能**: URL共有・ログイン不要閲覧
- **管理機能**: Filament管理画面・ユーザー管理・画像検閲

## Database Schema

全9テーブル: `users`, `oauth_providers`, `shops`, `categories`, `shop_categories`, `reviews`, `review_images`, `rankings`, `cache`

詳細: `docs/database-er-diagram.md`

## Important Notes

### Laravel 11 Specifics
- `'password' => 'hashed'` キャスト使用時は平文パスワード渡し（自動ハッシュ化）
- `User::updateOrCreate()` では手動ハッシュ化が必要な場合あり

### Filament Production Requirements
- `User`モデルに`FilamentUser`インターフェース実装必須
- nginx設定: `location ^~ /admin`でSPA catch-allより優先度を高くする
- 管理者2FA必須（FilamentAdminMiddleware実装済み）

## Project Status

🎯 **100%完了** - 本番デプロイ済み（全9フェーズ完了）

詳細進捗: `docs/technical-specs.md`

## Documentation

詳細ドキュメント: `docs/README.md`

主要ファイル:
- `docs/concept.md` - プロジェクト概要・コンセプト
- `docs/technical-specs.md` - 技術仕様・API仕様・実装状況
- `docs/database-er-diagram.md` - データベース設計・ER図
- `docs/development-workflow.md` - 機能開発ワークフロー
- `docs/20250729-production-deployment-guide.md` - デプロイメントガイド

## Development Workflow

新機能開発は `docs/features/` ディレクトリを使用した標準ワークフローに従う。
詳細: `docs/development-workflow.md`
