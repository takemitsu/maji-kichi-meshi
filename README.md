# マジキチメシ

> 吉祥寺地域の個人的な店舗ランキング作成・共有アプリ

## 概要

マジキチメシは、吉祥寺エリアの飲食店に対する個人的な評価とランキングを作成・共有できるWebアプリケーションです。
一般的なレビューサイトとは異なり、「星3でも1位にできる」自由度の高い主観的ランキング機能を提供します。

### 主な機能

- **レビュー機能**: 星評価・リピート意向・写真アップロード（自動リサイズ）
- **ランキング機能**: カテゴリ別主観的ランキング・簡単順位変更
- **共有機能**: URL共有・ログイン不要閲覧
- **管理機能**: Laravel Filament管理画面・ユーザー管理・画像検閲

## Tech Stack

### Frontend
- Vue.js 3 + Nuxt.js 3 (SPA)
- Tailwind CSS
- TypeScript
- 静的ビルド → nginx/CDN配信

### Backend
- Laravel 11 API
- Laravel Filament (管理画面)
- Laravel Socialite (OAuth)
- Intervention Image (画像処理)

### Database & Cache
- MySQL (本番) / SQLite (開発)
- Redis (Rate Limiter専用)

### Authentication
- OAuth (Google) → JWT
- ハイブリッド認証 (一般ユーザー:JWT + 管理者:セッション)

## プロジェクト構成

```
maji-kichi-meshi/
├── backend/          # Laravel API + Filament管理画面
├── frontend/         # Vue/Nuxt SPA
├── docs/             # 設計書・開発記録
└── scripts/          # デプロイメントスクリプト
```

## クイックスタート

### Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

### Frontend (Vue/Nuxt)

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

詳細なセットアップ手順は各ディレクトリのREADME.mdを参照してください。

## ドキュメント

- **[docs/README.md](docs/README.md)** - ドキュメント全体の索引
- **[docs/concept.md](docs/concept.md)** - プロジェクト概要・コンセプト
- **[docs/technical-specs.md](docs/technical-specs.md)** - 技術仕様・API仕様
- **[docs/database-er-diagram.md](docs/database-er-diagram.md)** - データベース設計
- **[CLAUDE.md](CLAUDE.md)** - Claude Code用開発ガイダンス

## 開発コマンド

### Backend

```bash
php artisan test               # テスト実行
composer pint                  # コードフォーマット
composer stan                  # 静的解析
```

### Frontend

```bash
npm run dev        # 開発サーバー起動
npm run build      # ビルド
npm run lint       # Lint + フォーマット
npm run type-check # 型チェック
```

## デプロイメント

本番環境へのデプロイ手順は `docs/20250729-production-deployment-guide.md` を参照してください。

## プロジェクト状況

🎯 **100%完了** - 本番デプロイ済み（全9フェーズ完了）

詳細な実装状況・テスト結果は `docs/technical-specs.md` を参照してください。

## ライセンス

個人プロジェクト（非公開）
