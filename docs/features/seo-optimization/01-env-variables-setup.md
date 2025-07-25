# タスク01: 環境変数設定システム実装

## 概要
Nuxt.jsの`.env`ファイル対応と`runtimeConfig`設定により、開発・本番環境でのドメイン切り替えを可能にする。

## 実装内容

### 1. 環境変数ファイル作成
```bash
# frontend/.env.example
SITE_URL=https://majikichi-meshi.com
API_BASE_URL=https://api.majikichi-meshi.com/api

# frontend/.env (gitignore済み、ローカル開発用)
SITE_URL=http://localhost:3000
API_BASE_URL=http://localhost:8000/api
```

### 2. nuxt.config.ts更新
```typescript
// frontend/nuxt.config.ts
export default defineNuxtConfig({
  runtimeConfig: {
    public: {
      apiBase: process.env.API_BASE_URL || 'http://localhost:8000/api',
      siteUrl: process.env.SITE_URL || 'http://localhost:3000', // 追加
    },
  },
})
```

### 3. .gitignore確認
```bash
# frontend/.gitignore
.env
.env.local
```

## テスト要件
- [ ] 開発環境で`localhost:3000`が設定されることを確認
- [ ] 環境変数設定時に正しい値が反映されることを確認
- [ ] `useRuntimeConfig()`でアクセス可能なことを確認

## 注意点
- `.env`ファイルは機密情報を含むため必ずgitignore対象
- `.env.example`をリポジトリに含めて設定例を提供
- `runtimeConfig.public`に設定された値はクライアント側でも利用可能

## 参考情報
- [Nuxt.js Runtime Config](https://nuxt.com/docs/guide/going-further/runtime-config)
- [Nuxt.js Environment Variables](https://nuxt.com/docs/guide/directory-structure/env)

## 完了基準
- [ ] `.env.example`ファイル作成
- [ ] `nuxt.config.ts`のruntimeConfig更新
- [ ] ローカル環境での動作確認完了