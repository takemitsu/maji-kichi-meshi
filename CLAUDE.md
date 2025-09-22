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
- MySQL (本番環境)
- SQLite (開発環境)

### Cache System
- **Redis** (2025-09-22〜)
  - Rate Limiter専用
  - Database 1を使用
  - デッドロック問題解消のためdatabaseキャッシュから移行

### Authentication
- OAuth: Google (専用) ※将来的に要望があれば他プロバイダー追加可能
- Laravel Socialite → JWTトークン発行
- Web/Mobile共通認証
- ハイブリッド認証: 一般ユーザー(JWT) + 管理者(セッション) - ✅ 実装完了

### External Services
- Google Maps リンク連携（実装済み: 店名検索リンク）
- Google Places API（将来実装予定: 店舗情報取得）
- Google OAuth 2.0（実装済み: ユーザー認証）

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

# シーダー実行（データベース初期化）
php artisan migrate:fresh --seed

# キャッシュクリア
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Filament管理画面
php artisan filament:install

# 管理者ログイン情報
# takemitsu@notespace.jp / admin2024
# admin@majikichi-meshi.com / admin123

# テスト実行
php artisan test        # Laravel標準テスト実行
php artisan test --coverage  # カバレッジ付きテスト実行

# コード品質ツール
composer pint           # Laravel Pint (コードフォーマット)
composer stan           # PHPStan (静的解析)

# PHPStan 詳細実行（推奨）
./vendor/bin/phpstan analyse --memory-limit=1024M  # メモリ不足対策
```

### Frontend (Vue/Nuxt)
```bash
# 依存関係インストール
npm install

# 開発サーバー起動 (localhost:3000)
npm run dev

# ビルド
npm run build

# プロダクション確認
npm run preview

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
- 複数写真アップロード（自動リサイズ、最大10MB）
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
- Google Maps リンク（店舗詳細から「Google Mapsで探す」リンク）
- Google Places API（将来実装予定: 店舗情報自動取得）

## Database Design

### 主要テーブル（実装済み）
- `users` - ユーザー情報 + 管理者権限(role/status)
- `oauth_providers` - OAuth連携情報（Google専用）
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
- **OAuth対応済み**: Google専用 (Laravel Socialite) ※GitHub/LINE/Twitter等は要望次第で追加可能
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
- MySQL本番環境採用（SQLite開発環境）
- 管理画面はLaravel Filamentで効率開発

## 技術的発見・注意事項

### Laravel 11 hashedキャストの動作
- `'password' => 'hashed'` キャストは自動的にパスワードをハッシュ化
- `User::create()` では正常動作、`User::updateOrCreate()` では動作しない場合がある
- 手動ハッシュ化（`Hash::make()`）と併用すると二重ハッシュ化される
- シーダーでは平文パスワードを使用し、キャストによる自動ハッシュ化に依存する

### Laravel Filament本番環境要件
- **FilamentUserインターフェース必須**: 本番環境では`User`モデルに`FilamentUser`実装が必要
- **canAccessPanel()メソッド**: `canAccessPanel(\Filament\Panel $panel): bool`の実装必須
- **nginx設定注意**: `location ^~ /admin`でSPAのcatch-allより優先度を高くする
- **PHP-FPMソケット権限**: `sudo usermod -a -G nginx www-data`で権限解決
- **2FA必須設定**: 管理者はTwo-Factor Authentication設定が必要（FilamentAdminMiddleware実装済み）

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

### Phase 6: Management System Completion ✅ 完了
- [x] CategoryResource実装（Filament管理画面）
- [x] 基本シーダー実装（AdminSeeder, ShopSeeder, ReviewSeeder, RankingSeeder）
- [x] Laravel 11 hashedキャスト対応
- [x] 管理システム完全実装

### Phase 7: UI/UX Enhancement & Statistics ✅ 完了
- [x] ダッシュボード統計API実装 (StatsController + StatsApiTest)
- [x] フロントエンド UI改善（ランキング優先・2カラム統計・設定ページ）
- [x] 認証後画面の体験向上（ナビゲーション順序・アクション優先度）
- [x] アカウント設定ページ実装
- [x] 実API統合（ダミーデータ削除・エラーハンドリング強化）

### Phase 8: Mobile UI/UX Optimization ✅ 完了
- [x] モバイルメニュー重複項目削除（レビュー・ランキングの2箇所表示修正）
- [x] アクションリンクを削除してカード全体をクリック可能に統一
- [x] タップ領域の最小44px確保（新しい店舗登録リンク）
- [x] コントラスト比改善（text-gray-500→text-gray-700でWCAG AA基準対応）
- [x] ページタイトルとナビ名の統一（マイページ・マイランキング）
- [x] モバイル余白・パディング最適化（全16ファイル p-4 md:p-6, gap-4 md:gap-6 適用）
- [x] ランキング詳細の視覚的ヒエラルキー調整（順位表示サイズ縮小）
- [x] データ表示の一貫性改善（更新日時プレフィックス統一・不要ボタン削除）
- [x] マイランキングフィルターのモバイル横並び対応
- [x] 未使用コードの削除とlint/prettier実行

**📋 追加改善提案の検討完了（2025-07-18）**：
- [x] 6項目の追加改善提案を検討、5項目は不要と判断
- [x] **文字折り返し・省略** → ❌ 不要（実用性低い）
- [x] **情報密度最適化** → ❌ 不要（現状で問題なし）
- [x] **戻るボタン統一** → ❌ いらない（現状で十分）
- [x] **フォーム状態保存** → ❌ 不要（項目数少なく離脱リスク低）
- [x] **エラーハンドリング強化** → ❌ 不要（エラー頻発しない実装）
- [x] **URL状態管理** → ⚠️ やる方向で棚上げ（検索・フィルタ体験向上、実装コスト高）
- 詳細: `docs/2025071*-task-mobile-ui-*.md` 参照

### Phase 9: Production Deployment & Infrastructure ✅ 完了
- [x] nginx統合設定での管理画面アクセス問題解決
- [x] PHP-FPMソケット権限問題解決（`sudo usermod -a -G nginx www-data`）
- [x] Filament本番環境認証問題解決（FilamentUserインターフェース実装）
- [x] Laravel Filament 2FA設定・動作確認完了
- [x] フロントエンド+バックエンド統合nginx設定での動作確認完了
- [x] 本番デプロイメントガイド作成（`docs/20250729-production-deployment-guide.md`）

### Phase 10: Future Enhancement (計画)
- [ ] Google Places API 連携
- [x] ~~**アプリライク匿名ユーザー機能**~~ ❌ **計画停止** (2025-07-10)
  - **停止理由**: 重大リスクが価値を上回る（詳細: docs/20250710185000-report-anonymous-user-concerns.md）
- [ ] URL状態管理（検索条件・フィルタ条件・ページネーション） ⚠️ **実装予定**
- [ ] 通報システム実装
- [ ] パフォーマンス最適化
- [ ] デプロイメント自動化

### 🎯 プロジェクト完了状況: **100%** (本番デプロイ完了版)
**管理画面含む完全システム + モバイルファースト対応 + 本番環境動作確認済み**

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

## 機能開発ワークフロー

新機能開発は `docs/features/` ディレクトリを使用した標準ワークフローに従ってください。

### ディレクトリ構成
```
docs/features/
├── feature-name/
│   ├── README.md           # 機能概要・仕様・要件
│   ├── 01-task-name.md     # 個別タスク（連番）
│   ├── 02-task-name.md     # 個別タスク
│   └── progress.md         # 進捗管理・完了記録
```

### 開発手順
1. **企画**: `docs/features/feature-name/` ディレクトリ作成
2. **要件定義**: `README.md` に機能仕様記載
3. **タスク分解**: `01-*.md`, `02-*.md` で個別タスク定義
4. **進捗管理**: `progress.md` でタスク状況追跡
5. **完了記録**: 最終結果・引き継ぎ事項を記録

詳細は `docs/development-workflow.md` を参照してください。

## Deployment

### 本番環境デプロイ手順

```bash
# 1. デプロイメント環境に移動
cd ~/deployment/maji-kichi-meshi/

# 2. 最新コードを取得
git pull origin main

# 3. デプロイスクリプト実行
./scripts/deploy.sh
```

### デプロイ後の確認事項

1. **フロントエンド確認**：
   - `https://maji-kichi-meshi.takemitsu.net/` にアクセス
   - ログイン・機能動作確認

2. **管理画面確認**：
   - `https://maji-kichi-meshi.takemitsu.net/admin` にアクセス
   - 2FA設定またはダッシュボードが表示されるか確認

3. **API確認**：
   - `https://maji-kichi-meshi.takemitsu.net/api/shops` 等のAPIエンドポイント動作確認

### トラブルシューティング

**管理画面で403エラーが発生する場合**：
- PHP-FPMソケット権限確認：`sudo usermod -a -G nginx www-data`
- UserモデルにFilamentUserインターフェース実装確認
- nginx設定の`location ^~ /admin`ブロック確認
- 詳細トラブルシューティング：`docs/20250729-production-deployment-guide.md`参照

**500エラーが発生する場合**：
```bash
# Laravelログ確認
tail -f /var/www/maji-kichi-backend/storage/logs/laravel-$(date +%Y-%m-%d).log

# キャッシュクリア
cd /var/www/maji-kichi-backend
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan cache:clear
```

### バックアップ管理

**バックアップ一覧確認**：
```bash
ls -la /var/www/ | grep backup
```

**容量確認**：
```bash
du -sh /var/www/*.backup.*
```

**古いバックアップ削除**：
```bash
# 7日より古いバックアップを自動削除
sudo find /var/www/ -name "*.backup.*" -type d -mtime +7 -exec rm -rf {} \;

# 特定日付のバックアップを削除（例：20250729）
sudo rm -rf /var/www/maji-kichi-backend.backup.20250729_*
sudo rm -rf /var/www/maji-kichi-frontend.backup.20250729_*
```

## Hooks 設定

### Claude Code Hooks 動作確認

このプロジェクトでは Claude Code の UserPromptSubmit hook が設定されています。

**動作確認方法**:
Claude に「hooksは機能していますか？」または「<user-prompt-submit-hook>の内容を教えて」と質問してください。

正常に動作している場合、Claude は以下の内容を確認できると回答します：
- 実装前確認の注意事項
- セキュリティ制限（フルパス禁止、深い遡り禁止、設定ファイル禁止、末尾改行なし禁止）

**ユーザー側でhook動作を可視化したい場合**:
`.claude/prompt-hook.sh` 内のコメントアウトされたecho文をコメント解除すると、Claude が毎回回答の前にhook内容を表示するようになります。（ただし毎回表示されるため通常は不要）

**設定ファイル**:
- `.claude/settings.json` - 共有可能なhooks設定
- `.claude/prompt-hook.sh` - 実行されるスクリプト
- `.claude/settings.local.json` - ローカル固有のpermissions設定
