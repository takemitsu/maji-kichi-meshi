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
npm run lint       # Lint
npm run format     # フォーマット
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

### コミットルール

- **ソースコード**（実装コード）のコミット前には**必ずユーザーの判断を仰ぐ**
- **ドキュメント**（.md ファイル等）のコミットは自己判断でOK
- テスト結果、コード品質チェック結果を提示してからコミット判断を求める

### コード変更後の品質チェック

**該当する変更のみ実行:**

**PHPファイル修正時** (backend/ ディレクトリで実行):
```bash
composer pint                               # コードフォーマット
./vendor/bin/phpstan analyse --memory-limit=1024M  # 静的解析
php artisan test                            # テスト実行（新機能/修正時のみ）
```

**JS/Vue/TSファイル修正時** (frontend/ ディレクトリで実行):
```bash
npm run lint       # ESLint
npm run format     # Prettier
npm run type-check # TypeScript型チェック
```

## ⚠️ 重要: ファイル・ディレクトリ操作の注意事項

### docs/ ディレクトリの誤作成防止

**問題**: `docs/` で始まるパスを指示されたとき、カレントディレクトリに `docs/` を作成してしまう

**例**:
```
ユーザー: "docs/features/xxx/plan.md を更新して"

間違った動作:
1. pwd 確認せず
2. cd backend だと backend/docs/ を作成 ← 間違い！
3. cd frontend だと frontend/docs/ を作成 ← 間違い！

正しい動作:
1. pwd で現在位置確認
2. プロジェクトルートの /Users/takemitsusuzuki/work/personal/maji-kichi-meshi/docs/ を操作
```

**必須手順:**
1. `docs/` で始まるパスの場合、**必ず `pwd` で現在位置を確認**
2. `backend/` または `frontend/` にいる場合は、プロジェクトルートに移動するか、フルパスで操作
3. **絶対に backend/docs/ や frontend/docs/ を作成しない**

### フルパス指定時のディレクトリ作成禁止

**問題**: ユーザーがフルパスを指定しても、カレントディレクトリ基準で相対パスとして解釈してゴミディレクトリを作成してしまう

**例**:
```
ユーザー: "/Users/takemitsusuzuki/work/personal/maji-kichi-meshi/docs/features/like-and-wishlist の plan.md を更新して"

間違った動作:
1. Glob で検索 → 見つからない
2. mkdir -p docs/features/like-and-wishlist  # ← カレントディレクトリにゴミフォルダ作成！
```

### 正しい手順

**必ずフルパスで確認・操作する**

```bash
# ❌ 間違い: 相対パスで操作
ls docs/features/like-and-wishlist/
mkdir -p docs/features/like-and-wishlist

# ✅ 正しい: フルパスで操作
ls /Users/takemitsusuzuki/work/personal/maji-kichi-meshi/docs/features/like-and-wishlist/
mkdir -p /Users/takemitsusuzuki/work/personal/maji-kichi-meshi/docs/features/like-and-wishlist
```

### ルール

1. **ユーザーがフルパスを指定した場合**:
   - 全ての操作を**フルパスで実行**する
   - 相対パスに変換しない

2. **ディレクトリ作成前の確認**:
   ```bash
   # まずフルパスで存在確認
   ls /full/path/to/directory/ 2>/dev/null || echo "ディレクトリが存在しません"

   # 存在しない場合は、ユーザーに確認してから作成
   ```

3. **Globツールの使用**:
   - Glob で見つからなかった場合、すぐに mkdir しない
   - フルパスで ls コマンドで再確認する

### 原因

- カレントディレクトリ: `/Users/takemitsusuzuki/work/personal/maji-kichi-meshi/backend`
- ユーザー指定: `/Users/takemitsusuzuki/work/personal/maji-kichi-meshi/docs/...`
- 相対パス変換時の誤り: `docs/features/...` → backend 配下に作成されてしまう

### 対策

**フルパスが指定された場合は、常にフルパスで操作する**
