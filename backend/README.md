# マジキチメシ - Backend API

> Laravel 11 REST API + Filament管理画面

## 概要

マジキチメシのバックエンドAPIです。Laravel 11をベースとした純粋なREST APIと、Laravel Filamentによる管理画面を提供します。

## Tech Stack

- **Framework**: Laravel 11
- **Admin Panel**: Laravel Filament 3
- **Authentication**: Laravel Socialite + JWT
- **Image Processing**: Intervention Image
- **Database**: MySQL (本番) / SQLite (開発)
- **Cache**: Redis (Rate Limiter専用)

## セットアップ

### 依存関係のインストール

```bash
composer install
```

### 環境設定

```bash
cp .env.example .env
php artisan key:generate
```

### データベース設定

`.env`ファイルでデータベース接続情報を設定：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=maji_kichi_meshi
DB_USERNAME=root
DB_PASSWORD=
```

### マイグレーション & シーダー実行

```bash
php artisan migrate:fresh --seed
```

### 開発サーバー起動

```bash
php artisan serve
# http://localhost:8000 でアクセス可能
```

## 主要コマンド

### 開発

```bash
php artisan serve              # 開発サーバー起動
php artisan migrate:fresh --seed  # DB初期化
php artisan tinker             # REPL起動
```

### テスト

```bash
php artisan test               # 全テスト実行
php artisan test --filter=testName  # 特定テスト実行
php artisan test --coverage    # カバレッジ付きテスト実行
```

### コード品質

```bash
composer pint                  # Laravel Pint (コードフォーマット)
composer stan                  # PHPStan (静的解析)
./vendor/bin/phpstan analyse --memory-limit=1024M  # PHPStan詳細実行
```

### Filament管理画面

```bash
php artisan filament:install   # Filament初期設定
php artisan make:filament-resource ModelName  # リソース作成
```

管理画面アクセス: `http://localhost:8000/admin`

**管理者ログイン情報**:
- Email: `admin@majikichi-meshi.com`
- Password: `admin123`

## ディレクトリ構造

```
backend/
├── app/
│   ├── Filament/          # Filament管理画面リソース
│   ├── Http/
│   │   ├── Controllers/   # APIコントローラー
│   │   └── Resources/     # API Eloquent Resources
│   └── Models/            # Eloquentモデル
├── database/
│   ├── factories/         # Factoryクラス
│   ├── migrations/        # マイグレーション
│   └── seeders/           # シーダー
├── routes/
│   ├── api.php            # APIルート
│   └── web.php            # Webルート (Filament)
└── tests/
    ├── Feature/           # フィーチャーテスト
    └── Unit/              # ユニットテスト
```

## API仕様

### ベースURL

- 開発環境: `http://localhost:8000/api`
- 本番環境: `https://maji-kichi-meshi.takemitsu.net/api`

### 主要エンドポイント

#### 認証
- `POST /api/auth/google` - Google OAuth認証開始
- `GET /api/auth/google/callback` - OAuth コールバック
- `POST /api/auth/logout` - ログアウト
- `GET /api/auth/me` - 認証ユーザー情報取得

#### 店舗
- `GET /api/shops` - 店舗一覧取得
- `GET /api/shops/{id}` - 店舗詳細取得
- `POST /api/shops` - 店舗作成 (要認証)
- `PUT /api/shops/{id}` - 店舗更新 (要認証)
- `DELETE /api/shops/{id}` - 店舗削除 (要認証)

#### レビュー
- `GET /api/reviews` - レビュー一覧取得
- `GET /api/reviews/{id}` - レビュー詳細取得
- `POST /api/reviews` - レビュー作成 (要認証)
- `PUT /api/reviews/{id}` - レビュー更新 (要認証)
- `DELETE /api/reviews/{id}` - レビュー削除 (要認証)

#### ランキング
- `GET /api/rankings` - ランキング一覧取得
- `GET /api/rankings/{id}` - ランキング詳細取得
- `POST /api/rankings` - ランキング作成 (要認証)
- `PUT /api/rankings/{id}` - ランキング更新 (要認証・順位変更)
- `DELETE /api/rankings/{id}` - ランキング削除 (要認証)

詳細なAPI仕様は `docs/technical-specs.md` を参照してください。

## 認証

### JWT認証フロー

1. フロントエンド → `POST /api/auth/google`
2. Google OAuth認証
3. コールバック → `GET /api/auth/google/callback`
4. JWTトークン発行 (有効期限: 1週間)
5. APIリクエスト時: `Authorization: Bearer {token}` ヘッダー

### ハイブリッド認証

- **一般ユーザー**: JWT認証
- **管理者**: セッションベース認証 (Filament)

## データベース

### 主要テーブル

- `users` - ユーザー情報 + 管理者権限
- `oauth_providers` - OAuth連携情報
- `shops` - 店舗情報
- `categories` - カテゴリマスタ
- `shop_categories` - 店舗カテゴリ中間テーブル
- `reviews` - レビュー・評価
- `review_images` - レビュー画像
- `rankings` - ユーザー別ランキング

ER図: `docs/database-er-diagram.md`

## テスト

### テスト実行

```bash
# 全テスト実行
php artisan test

# 特定ファイルのテスト実行
php artisan test tests/Feature/AuthenticationTest.php

# 特定テスト実行
php artisan test --filter=test_user_can_login

# カバレッジ付きテスト実行
php artisan test --coverage
```

### テストカバレッジ

- 認証システム: 100%
- API CRUD操作: 98%
- 管理画面: 95%

## トラブルシューティング

### Filament管理画面で403エラー

```bash
# PHP-FPMソケット権限設定
sudo usermod -a -G nginx www-data

# UserモデルにFilamentUserインターフェース実装確認
```

### キャッシュクリア

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 参考ドキュメント

- [Laravel 11 Documentation](https://laravel.com/docs/11.x)
- [Filament 3 Documentation](https://filamentphp.com/docs)
- [Laravel Socialite](https://laravel.com/docs/11.x/socialite)
- [Intervention Image](https://image.intervention.io/)
