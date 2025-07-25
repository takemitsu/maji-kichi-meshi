# SEO最適化基盤整備機能

## 概要
SPA構成を維持しながら、本番環境でのSEO対策を効率的に実装するための基盤整備を行う。環境変数による柔軟な設定と、再利用可能なSEOメタデータ管理システムを構築する。

## 背景・目的
- 現在はSPA (`ssr: false`) でSEO対策が不十分
- URL固定化（本番ドメイン確定）前でも準備を完了させたい
- 本番化時に環境変数設定だけで全ページのSEOメタデータを有効化
- SNS共有・検索エンジン向けのメタデータ改善

## 主要要件

### 1. 環境変数対応
- [ ] `.env`ファイルでのドメイン設定対応
- [ ] 開発・本番環境での切り替え機能
- [ ] `nuxt.config.ts`でのruntimeConfig設定

### 2. SEOメタデータ管理システム
- [ ] 再利用可能なcomposable関数 (`useSeoMeta`)
- [ ] Open Graph tags対応
- [ ] Twitter Cards対応
- [ ] canonical URL設定
- [ ] robots meta tag設定

### 3. 構造化データ対応
- [ ] JSON-LD形式での構造化データ出力
- [ ] 店舗情報（Restaurant schema）
- [ ] レビュー情報（Review schema）
- [ ] ランキング情報（ItemList schema）

## 技術要件

### Frontend (Nuxt.js)
- 環境変数: `SITE_URL`, `API_BASE_URL`
- composable: `composables/useSeoMeta.ts`
- 各ページでの`useHead()`置き換え

### 対象ページ
- トップページ (`pages/index.vue`)
- 店舗一覧・詳細 (`pages/shops/`)
- レビュー一覧・詳細 (`pages/reviews/`)
- ランキング一覧・詳細 (`pages/rankings/`)
- 公開ランキング (`pages/rankings/public.vue`)

## 完了基準
- [ ] 環境変数設定システム実装完了
- [ ] `useSeoMeta` composable実装・テスト完了
- [ ] 全対象ページでのSEOメタデータ設定完了
- [ ] 開発環境での動作確認完了（localhost URL）
- [ ] 本番環境切り替えテスト完了
- [ ] ドキュメント更新完了

## 制約・考慮事項
- SPA構成は維持（SSR化はしない）
- 初期表示時のSEO効果は限定的（Google Bot依存）
- SNS共有とメタデータ改善が主目的
- URL固定化前でも開発・テスト可能な設計

## 関連する既存機能
- 現在の`useHead()`実装（各ページで個別設定）
- `nuxt.config.ts`の基本メタデータ設定
- API連携（店舗・レビュー・ランキングデータ）

## 影響範囲
- フロントエンド全ページのメタデータ設定
- 新規composable追加
- 環境変数設定ファイル追加
- 既存の`useHead()`呼び出し置き換え

## 成果物
- `composables/useSeoMeta.ts`
- `frontend/.env.example`
- 各ページの`useHead()`更新
- 設定・運用ドキュメント