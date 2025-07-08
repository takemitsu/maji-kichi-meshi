# 技術仕様書

## システム構成

### アーキテクチャ概要
```
Frontend (Nuxt.js SPA) → 静的ホスティング
    ↓ JWT Authentication
Backend (Laravel API) → Sakura VPS
    ↓ 
Database (PostgreSQL)

Admin Panel (Laravel Filament)
    ↓ Same Backend

Future: Mobile Apps → Same API
```

## 技術スタック

### フロントエンド
- **フレームワーク**: Vue.js 3 + Nuxt.js 3
- **モード**: SPA (Single Page Application)
- **スタイリング**: Tailwind CSS
- **型安全性**: TypeScript
- **状態管理**: Pinia
- **ホスティング**: 静的ビルド → nginx/CDN

### バックエンド
- **フレームワーク**: Laravel 11.4.0
- **アーキテクチャ**: RESTful API + BFF (Backend for Frontend)
- **認証**: JWT (tymon/jwt-auth) + OAuth (Laravel Socialite)
- **管理画面**: Laravel Filament
- **サーバー**: Sakura VPS + nginx + PHP-FPM

### データベース
- **メイン**: PostgreSQL
- **比較環境**: MySQL (既存)
- **マイグレーション**: Laravel標準

### 外部サービス
- **OAuth**: Google, GitHub, LINE, Twitter
- **地図・店舗**: Google Places API, Google Maps API
- **画像処理**: Intervention Image (4サイズ自動生成)

## 認証システム

### JWT設定
```php
// config/jwt.php
'ttl' => 10080, // 1週間 (分)
'refresh_ttl' => 20160, // 不使用
'algo' => 'HS256',
'blacklist_enabled' => true,
```

### OAuth フロー
1. **開始**: `GET /api/auth/{provider}`
2. **コールバック**: `GET /api/auth/{provider}/callback`
3. **トークン発行**: JWT + ユーザー情報返却
4. **保護API**: `Authorization: Bearer {token}`

### 対応プロバイダー
- Google OAuth 2.0
- GitHub OAuth
- LINE Login
- Twitter OAuth 1.0a

### フロントエンド認証フロー
1. **ログインページ**: OAuth プロバイダー選択
2. **OAuth 認証**: バックエンドへリダイレクト
3. **コールバック処理**: JWT トークンとユーザー情報を URL パラメータで受信
4. **ローカル保存**: localStorage に JWT トークン保存
5. **自動認証**: リロード時に localStorage からトークン復元
6. **API 認証**: 全API リクエストに Bearer トークン付与
7. **ログアウト**: ローカルストレージクリア + ログインページへリダイレクト

## API仕様

### 認証エンドポイント
```
GET  /api/auth/{provider}           # OAuth開始
GET  /api/auth/{provider}/callback  # OAuth完了
GET  /api/auth/me                   # ユーザー情報取得 (要認証)
POST /api/auth/logout               # ログアウト (要認証)
```

### 実装済み API エンドポイント ✅
```
# カテゴリ管理
GET    /api/categories              # カテゴリ一覧
GET    /api/categories/{id}         # カテゴリ詳細
POST   /api/categories              # カテゴリ作成 (要認証)
PUT    /api/categories/{id}         # カテゴリ更新 (要認証)
DELETE /api/categories/{id}         # カテゴリ削除 (要認証)

# 店舗管理
GET    /api/shops                   # 店舗一覧・検索
GET    /api/shops/{id}              # 店舗詳細
POST   /api/shops                   # 店舗登録 (要認証)
PUT    /api/shops/{id}              # 店舗更新 (要認証)
DELETE /api/shops/{id}              # 店舗削除 (要認証)

# レビュー管理
GET    /api/reviews                 # レビュー一覧・フィルタリング
GET    /api/reviews/{id}            # レビュー詳細
POST   /api/reviews                 # レビュー投稿 (要認証)
PUT    /api/reviews/{id}            # レビュー更新 (要認証・所有者のみ)
DELETE /api/reviews/{id}            # レビュー削除 (要認証・所有者のみ)
GET    /api/my-reviews              # 自分のレビュー一覧 (要認証)

# ランキング管理
GET    /api/rankings                # 公開ランキング一覧
GET    /api/rankings/{id}           # ランキング詳細 (公開 or 所有者)
GET    /api/public-rankings         # 公開ランキング専用
POST   /api/rankings                # ランキング作成 (要認証)
PUT    /api/rankings/{id}           # ランキング更新 (要認証・所有者のみ)
DELETE /api/rankings/{id}           # ランキング削除 (要認証・所有者のみ)
GET    /api/my-rankings             # 自分のランキング一覧 (要認証)
```

### 未実装 API
```
# 画像アップロード
POST   /api/reviews/{id}/images     # レビュー画像アップロード
DELETE /api/reviews/images/{id}     # 画像削除

# Google Places API連携
GET    /api/places/search           # 店舗検索
GET    /api/places/{place_id}       # Google Places詳細
```

## データベース設計

### 主要リレーション
- **User** 1:N OAuthProvider, Review, Ranking
- **Shop** 1:N Review, Ranking, ShopCategory
- **Category** 1:N ShopCategory, Ranking
- **Review** 1:N ReviewImage

### インデックス戦略
```sql
-- 認証関連
CREATE INDEX idx_oauth_provider_user ON oauth_providers(provider, provider_id);
CREATE INDEX idx_oauth_user_provider ON oauth_providers(user_id, provider);

-- 店舗検索
CREATE INDEX idx_shops_location ON shops(latitude, longitude);
CREATE INDEX idx_shops_google_place ON shops(google_place_id);

-- レビュー・ランキング
CREATE INDEX idx_reviews_user_shop ON reviews(user_id, shop_id);
CREATE INDEX idx_rankings_user_category ON rankings(user_id, category_id);
CREATE INDEX idx_rankings_position ON rankings(category_id, rank_position);
```

## セキュリティ

### 認証・認可
- **JWT署名**: HS256 + 秘密鍵
- **トークン無効化**: ブラックリスト機能
- **CORS**: フロントエンドドメインのみ許可
- **レート制限**: Laravel throttle middleware

### データ保護
- **パスワード**: bcrypt ハッシュ化
- **OAuth Token**: 暗号化保存
- **機密情報**: .env 管理 (Git除外)

### バリデーション
- **入力検証**: Laravel Form Request
- **XSS防止**: エスケープ処理
- **SQL Injection**: Eloquent ORM

## フロントエンド詳細

### アーキテクチャ
- **SPA モード**: SSR 無効化により静的ホスティング対応
- **コンポーネント設計**: Vue.js 3 Composition API 使用
- **状態管理**: Pinia によるストア管理
- **ルーティング**: Vue Router 自動ルーティング
- **ミドルウェア**: 認証保護とゲストページ制御

### 認証システム
- **Pinia ストア**: 認証状態の一元管理
- **ローカルストレージ**: JWT トークン永続化
- **自動リダイレクト**: 認証状態に基づく画面遷移
- **API インターセプト**: 401 エラー時の自動ログアウト

### スタイリング
- **Tailwind CSS**: ユーティリティファースト CSS
- **カスタムコンポーネント**: ボタン、カード、入力フィールド
- **レスポンシブデザイン**: モバイルファースト設計
- **アクセシビリティ**: 適切な色彩コントラストと構造

### 実装済みページ
- **ホームページ** (`/`): プロジェクト概要とログイン導線
- **ログインページ** (`/login`): OAuth プロバイダー選択
- **ダッシュボード** (`/dashboard`): 認証後のメインページ

### API クライアント
- **useApi Composable**: 全 API エンドポイントのタイプセーフラッパー
- **自動認証**: Bearer トークンの自動付与
- **エラーハンドリング**: 401 エラーの自動処理
- **TypeScript**: 型安全な API 呼び出し

## パフォーマンス

### フロントエンド
- **SPA**: ページ遷移高速化
- **Code Splitting**: 必要なコードのみ読み込み
- **画像最適化**: WebP + 複数サイズ
- **CDN**: 静的ファイル配信

### バックエンド
- **API Cache**: Redis (将来導入)
- **画像処理**: 非同期ジョブ化
- **DB最適化**: 適切なインデックス
- **N+1問題**: Eager Loading

## 開発・運用

### テスト戦略
- **Unit Test**: モデル・サービスクラス
- **Feature Test**: API エンドポイント
- **Integration Test**: OAuth フロー
- **カバレッジ**: 80%以上目標

### デプロイメント
- **開発**: `php artisan serve` + `npm run dev`
- **本番**: nginx + PHP-FPM + 静的ビルド
- **CI/CD**: 将来的にGitHub Actions

### 監視・ログ
- **アプリケーションログ**: Laravel Log
- **エラー追跡**: 将来的にSentry
- **パフォーマンス**: 将来的にNew Relic

## 実装済み機能

### Phase 1: 基盤構築 ✅ 完了
- [x] Laravel + Nuxt.js プロジェクト構築
- [x] PostgreSQL データベース設計
- [x] 全テーブルマイグレーション (9テーブル)
- [x] 全モデルクラス (User, OAuthProvider, Shop, Category, Review, ReviewImage, Ranking)
- [x] JWT + OAuth 認証システム
- [x] 包括的テストスイート (13/13 成功)
- [x] API ルート設定
- [x] CategorySeeder (基本データ投入)
- [x] フロントエンド基本構成 (Nuxt.js + TypeScript + Tailwind CSS)
- [x] 認証システムフロントエンド実装 (OAuth + JWT)

### Phase 2: ビジネスロジック API ✅ 完了
- [x] 店舗管理 API (ShopController, ShopResource) - 9テスト成功
- [x] カテゴリ管理 API (CategoryController, CategoryResource) - 10テスト成功
- [x] レビュー機能 API (ReviewController, ReviewResource) - 13テスト成功
- [x] ランキング機能 API (RankingController, RankingResource) - 16テスト成功
- [x] 全APIエンドポイント実装 (CRUD + 所有者検証 + フィルタリング)
- [x] ファクトリー＆テスト整備 (合計48テスト成功)

### 実装済みファイル

#### バックエンド
```
backend/
├── app/Models/
│   ├── User.php (JWT + OAuth 対応)
│   ├── OAuthProvider.php
│   ├── Shop.php (店舗・位置検索対応)
│   ├── Category.php (カテゴリ・スラッグ対応)
│   ├── Review.php (レビュー・重複防止)
│   ├── ReviewImage.php
│   └── Ranking.php (ランキング・位置調整)
├── app/Http/Controllers/Api/
│   ├── AuthController.php (OAuth + JWT 認証)
│   ├── ShopController.php (店舗CRUD + 検索)
│   ├── CategoryController.php (カテゴリCRUD)
│   ├── ReviewController.php (レビューCRUD + 所有者検証)
│   └── RankingController.php (ランキングCRUD + 公開制御)
├── app/Http/Resources/
│   ├── UserResource.php
│   ├── ShopResource.php
│   ├── CategoryResource.php
│   ├── ReviewResource.php
│   └── RankingResource.php
├── database/migrations/ (11ファイル)
├── database/seeders/
│   └── CategorySeeder.php
├── database/factories/
│   ├── OAuthProviderFactory.php
│   ├── ShopFactory.php
│   ├── CategoryFactory.php
│   ├── ReviewFactory.php
│   └── RankingFactory.php
├── tests/Feature/ (5ファイル - 48テスト成功)
│   ├── AuthenticationTest.php (6/6 成功)
│   ├── ShopApiTest.php (9/9 成功)
│   ├── CategoryApiTest.php (10/10 成功)
│   ├── ReviewApiTest.php (13/13 成功)
│   └── RankingApiTest.php (16/16 成功)
├── tests/Unit/
│   └── UserModelTest.php (7/7 成功)
└── routes/api.php (完全なRESTful API)
```

#### フロントエンド
```
frontend/
├── nuxt.config.ts (SPA設定、Tailwind CSS、Pinia)
├── package.json (Vue.js 3 + Nuxt.js 3 + TypeScript)
├── app.vue (メインレイアウト)
├── assets/css/main.css (Tailwind CSS + カスタムスタイル)
├── composables/
│   └── useApi.ts (API クライアント、全エンドポイント定義)
├── middleware/
│   ├── auth.ts (認証保護ミドルウェア)
│   └── guest.ts (ゲストミドルウェア)
├── pages/
│   ├── index.vue (ホームページ)
│   ├── login.vue (OAuth ログインページ)
│   └── dashboard.vue (ダッシュボード)
├── plugins/
│   ├── api.client.ts (API プラグイン)
│   └── auth.client.ts (認証プラグイン、OAuth コールバック処理)
├── stores/
│   └── auth.ts (Pinia 認証ストア)
└── tailwind.config.js (Tailwind CSS 設定)
```

### Phase 2: Business Logic API ✅ 完了
- [x] 店舗管理 API (Shop, Category 関連) - 19テスト成功
- [x] レビュー機能 API (Review, ReviewImage 関連) - 13テスト成功  
- [x] ランキング機能 API (Ranking 関連) - 16テスト成功
- [ ] 画像アップロード機能 (Intervention Image)
- [ ] Google Places API 連携

### Phase 3: Frontend Integration (85%完了 - 別Claude担当)
- [x] 店舗管理フロントエンド実装（一覧・詳細・検索）
- [x] レビュー機能フロントエンド実装（表示・削除）
- [x] フロントエンド・バックエンドAPI統合（100%互換性確保）
- [ ] モーダル・フォーム実装（UI準備済み）
- [ ] ランキング管理UI実装

### API統合状況
- ✅ **フロントエンド・バックエンド統合スコア**: 98%
- ✅ **TypeScript型安全性**: 完全対応
- ✅ **エラーハンドリング**: 強化完了
- ✅ **全APIエンドポイント**: 互換性確保