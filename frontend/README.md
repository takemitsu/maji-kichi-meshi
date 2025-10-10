# マジキチメシ - Frontend SPA

> Vue.js 3 + Nuxt.js 3 で構築されたSPAフロントエンド

## 概要

マジキチメシのフロントエンドアプリケーションです。Vue.js 3とNuxt.js 3を使用した、モバイルファーストのSPA（Single Page Application）として実装されています。

## Tech Stack

- **Framework**: Vue.js 3 + Nuxt.js 3
- **Styling**: Tailwind CSS
- **Language**: TypeScript
- **State Management**: Pinia
- **Code Quality**: ESLint v9 + Prettier
- **Build**: Vite

## セットアップ

### 依存関係のインストール

```bash
npm install
```

### 環境設定

`.env`ファイルを作成し、APIエンドポイントを設定：

```env
NUXT_PUBLIC_API_BASE_URL=http://localhost:8000/api
```

### 開発サーバー起動

```bash
npm run dev
# http://localhost:3000 でアクセス可能
```

## 主要コマンド

### 開発

```bash
npm run dev        # 開発サーバー起動 (localhost:3000)
npm run build      # 本番ビルド
npm run preview    # ビルド後プレビュー
```

### コード品質

```bash
npm run lint       # ESLint + Prettier実行
npm run format     # Prettierフォーマット実行
npm run type-check # TypeScript型チェック
```

## ディレクトリ構造

```
frontend/
├── components/        # Vue コンポーネント
│   ├── layout/        # レイアウトコンポーネント
│   ├── shop/          # 店舗関連コンポーネント
│   ├── review/        # レビュー関連コンポーネント
│   └── ranking/       # ランキング関連コンポーネント
├── pages/             # ルーティング用ページコンポーネント
│   ├── index.vue      # トップページ
│   ├── login.vue      # ログインページ
│   ├── shops/         # 店舗関連ページ
│   ├── reviews/       # レビュー関連ページ
│   └── rankings/      # ランキング関連ページ
├── stores/            # Pinia ストア
│   ├── auth.ts        # 認証ストア
│   ├── shop.ts        # 店舗ストア
│   └── review.ts      # レビューストア
├── composables/       # Vue Composables
│   └── useApi.ts      # API呼び出し用Composable
├── plugins/           # Nuxt プラグイン
├── public/            # 静的ファイル
└── nuxt.config.ts     # Nuxt 設定
```

## 主要機能

### 認証

- Google OAuth認証
- JWTトークン管理（localStorage）
- 認証状態管理（Pinia）
- 認証ガード（ミドルウェア）

### 店舗機能

- 店舗一覧表示（ページネーション）
- 店舗詳細表示
- 店舗検索（名前・カテゴリ）
- 新規店舗登録

### レビュー機能

- レビュー一覧表示
- レビュー詳細表示
- レビュー投稿（星評価・リピート意向・写真）
- 写真アップロード（複数枚対応）

### ランキング機能

- カテゴリ別ランキング表示
- ランキング順位変更（スワイプ・ボタン）
- ランキング共有

## API連携

### APIクライアント設定

`composables/useApi.ts`で共通のAPI呼び出し処理を実装：

```typescript
const api = useApi()
const { data, error } = await api.get('/shops')
```

### 認証ヘッダー

JWT認証が必要なエンドポイントには自動的に`Authorization`ヘッダーを付与：

```
Authorization: Bearer {token}
```

## スタイリング

### Tailwind CSS

ユーティリティファーストのアプローチでスタイリング：

```vue
<div class="p-4 md:p-6 gap-4 md:gap-6 bg-white rounded-lg shadow">
  <!-- コンテンツ -->
</div>
```

### レスポンシブデザイン

モバイルファーストで実装：

- デフォルト: モバイル
- `md:` (768px以上): タブレット
- `lg:` (1024px以上): デスクトップ

### アクセシビリティ

- WCAG AA基準準拠
- 最小タップ領域44px確保
- 適切なコントラスト比（text-gray-700使用）

## コード品質

### ESLint

ESLint v9（Flat Config）を使用：

```bash
npm run lint
```

### Prettier

コードフォーマッター：

```bash
npm run format
```

### TypeScript

厳格な型チェック：

```bash
npm run type-check
```

## ビルド & デプロイ

### 本番ビルド

```bash
npm run build
```

静的ファイルは `.output/public/` に生成されます。

### デプロイメント

静的ファイルをnginx/CDNで配信：

```bash
# nginxにデプロイ
cp -r .output/public/* /var/www/maji-kichi-frontend/
```

詳細なデプロイ手順は `docs/deployment-frontend-guide.md` を参照してください。

## 環境変数

### 開発環境 (`.env`)

```env
NUXT_PUBLIC_API_BASE_URL=http://localhost:8000/api
```

### 本番環境 (`.env.production`)

```env
NUXT_PUBLIC_API_BASE_URL=https://maji-kichi-meshi.takemitsu.net/api
```

## トラブルシューティング

### ビルドエラー

```bash
# 依存関係の再インストール
rm -rf node_modules package-lock.json
npm install
```

### 型エラー

```bash
# 型チェック実行
npm run type-check
```

### API接続エラー

`.env`ファイルの`NUXT_PUBLIC_API_BASE_URL`を確認してください。

## 参考ドキュメント

- [Nuxt.js 3 Documentation](https://nuxt.com/docs)
- [Vue.js 3 Documentation](https://vuejs.org/guide/introduction.html)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Pinia Documentation](https://pinia.vuejs.org/)
