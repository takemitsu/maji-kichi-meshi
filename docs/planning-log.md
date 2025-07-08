# マジキチメシ - プロジェクト企画ログ

## プロジェクト概要
- **プロジェクト名**: マジキチメシ
- **目的**: 吉祥寺地域の個人的な店舗ランキングを作成・共有
- **コンセプト**: 客観的評価とは別の「完全に主観的で理不尽な個人ランキング」を作れるサービス
- **対象**: 自分用 → 後に共有機能追加予定

## 主要機能

### 1. 店舗レビュー機能（訪問記録）
- 星評価（1〜5）
- リピート意向（また行く/わからん/行かない）※星評価とは独立
- 訪問メモ（自由記述）
- 写真アップロード（複数枚可、自動リサイズ）
- 訪問日記録

### 2. 個人ランキング機能 ※レビューとは独立
- カテゴリ別ランキング作成
  - 総合TOP10
  - カテゴリ別TOP5（ラーメン、定食等）
- スワイプや上下ボタンで簡単に順位入れ替え
- 星評価とランキングは独立（星3でも1位可能）

### 3. カテゴリ設計（複数選択可）
**基本カテゴリ**:
- ラーメン
- 定食・食堂
- 居酒屋・バー
- カフェ・喫茶店
- ファストフード
- その他

**時間帯タグ**:
- ランチ営業あり
- 深夜営業（22時以降）
- 朝営業（10時前）

### 4. 共有機能
- URLコピーで簡易共有
- ログイン不要で閲覧可能
- 「俺の吉祥寺○○ランキング」として公開

## 技術スタック（決定済み）
### フロントエンド
- Vue.js + Nuxt.js（SPAモード）
- Tailwind CSS
- 静的ホスティング（nginx or CDN）

### バックエンド
- Laravel API（純粋なREST/JSON API）
- Laravel Socialite（OAuth認証・JWT発行）
- Backend for Frontend (BFF)パターン
- 既存Sakura VPSサーバー活用

### データベース
- PostgreSQL（新規導入予定）
- MySQL（既存サーバー、比較検討用）

### 認証
- OAuth（Google, GitHub, LINE, Twitter）
- Laravel Socialite使用

### 管理画面
- Laravel Filament
- 機能: ユーザー管理、店舗データ管理、ランキング集計、不適切投稿管理

## 外部API連携（検討中）
- Google Places API（店舗情報、評価、写真）
- Google Maps API（地図表示、リンク）
- 食べログ連携（API制限あり）

## 構成方針
- **管理画面**: Laravel + Filament
- **API**: Laravel API（純粋なREST API）
- **フロントエンド**: Nuxt.js SPAモード（静的ビルド）
- **認証**: Laravel Socialite → JWT発行
- **デプロイ**: 
  - API: Sakura VPS（既存環境）
  - フロント: nginx or CDN配信
- **将来拡張**: Android/iOSアプリも同じAPIを利用

## 開発進捗

### ✅ 完了済み (2025/07/08)
1. **プロジェクト初期化**
   - Laravel 12 + 必要パッケージ (Socialite, JWT-Auth, Intervention Image)
   - Nuxt.js + Tailwind CSS
   - Git リポジトリ初期化

2. **データベース設計完了**
   - 完全なER図作成 (`docs/database-er-diagram.md`)
   - 全テーブルのマイグレーションファイル作成
   - カテゴリマスタの初期データシーダー作成
   - 全モデルファイル作成

### 🚧 今後のタスク
1. **認証基盤実装** (次回優先)
   - JWT設定
   - OAuth実装 (Google, GitHub, LINE, Twitter)
   - 認証ミドルウェア

2. **API設計・実装**
   - ルーティング設計
   - コントローラー実装
   - API Resource作成

3. **フロントエンド実装**
   - Nuxt.js SPA設定
   - 認証フロー
   - 基本UI実装

4. **外部API連携**
   - Google Places API
   - Google Maps API

5. **デプロイ準備**

## 開発環境
- Sakura VPS
- Laravel 11.4.0 + nginx + fastcgi
- MySQL既存環境
- PostgreSQL新規導入予定

## メモ
- 複数人での共有機能は将来的に実装
- 「俺のランキングを見てくれ」機能を重視
- 個人プロジェクトからスタート