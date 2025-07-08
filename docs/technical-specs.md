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

## API仕様

### 認証エンドポイント
```
GET  /api/auth/{provider}           # OAuth開始
GET  /api/auth/{provider}/callback  # OAuth完了
GET  /api/auth/me                   # ユーザー情報取得 (要認証)
POST /api/auth/logout               # ログアウト (要認証)
```

### 今後実装予定
```
# 店舗管理
GET    /api/shops                   # 店舗一覧
POST   /api/shops                   # 店舗登録
GET    /api/shops/{id}              # 店舗詳細
PUT    /api/shops/{id}              # 店舗更新

# レビュー
GET    /api/reviews                 # レビュー一覧
POST   /api/reviews                 # レビュー投稿
GET    /api/reviews/{id}            # レビュー詳細
PUT    /api/reviews/{id}            # レビュー更新
DELETE /api/reviews/{id}            # レビュー削除

# ランキング
GET    /api/rankings                # ランキング一覧
POST   /api/rankings                # ランキング作成
PUT    /api/rankings/{id}           # ランキング更新
GET    /api/rankings/{id}/public    # 公開ランキング表示

# カテゴリ
GET    /api/categories              # カテゴリ一覧
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

### Phase 1 完了 ✅
- [x] Laravel + Nuxt.js プロジェクト構築
- [x] PostgreSQL データベース設計
- [x] 全テーブルマイグレーション (8テーブル)
- [x] 全モデルクラス (User, OAuthProvider, Shop, Category, Review, ReviewImage, Ranking)
- [x] JWT + OAuth 認証システム
- [x] 包括的テストスイート (13/13 成功)
- [x] API ルート設定
- [x] CategorySeeder (基本データ投入)

### 実装済みファイル
```
backend/
├── app/Models/
│   ├── User.php (JWT + OAuth 対応)
│   ├── OAuthProvider.php
│   ├── Shop.php
│   ├── Category.php
│   ├── Review.php
│   ├── ReviewImage.php
│   └── Ranking.php
├── app/Http/Controllers/Api/
│   └── AuthController.php (OAuth + JWT 認証)
├── database/migrations/ (8ファイル)
├── database/seeders/
│   └── CategorySeeder.php
├── database/factories/
│   └── OAuthProviderFactory.php
├── tests/Feature/
│   └── AuthenticationTest.php (6/6 成功)
├── tests/Unit/
│   └── UserModelTest.php (7/7 成功)
└── routes/api.php
```

### Phase 2: Business Logic API (次の実装)
- [ ] 店舗管理 API (Shop, Category 関連)
- [ ] レビュー機能 API (Review, ReviewImage 関連)
- [ ] ランキング機能 API (Ranking 関連)
- [ ] 画像アップロード機能 (Intervention Image)
- [ ] Google Places API 連携
- [ ] フロントエンド実装開始