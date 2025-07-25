# タスク02: useSeoMeta composable実装

## 概要
再利用可能なSEOメタデータ管理用composable関数を実装し、Open Graph、Twitter Cards、canonical URL、構造化データに対応する。

## 実装内容

### 1. composable関数作成
```typescript
// frontend/composables/useSeoMeta.ts
export const useSeoMeta = () => {
  const config = useRuntimeConfig()
  const route = useRoute()
  
  const baseUrl = config.public.siteUrl || 'http://localhost:3000'
  
  const generateSeoMeta = (params: {
    title: string
    description: string
    image?: string
    type?: 'website' | 'article'
    noindex?: boolean
  }) => {
    const fullUrl = `${baseUrl}${route.path}`
    const ogImage = params.image || `${baseUrl}/default-og-image.jpg`
    
    return {
      title: params.title,
      meta: [
        { name: 'description', content: params.description },
        { name: 'robots', content: params.noindex ? 'noindex, nofollow' : 'index, follow' },
        // Open Graph
        { property: 'og:title', content: params.title },
        { property: 'og:description', content: params.description },
        { property: 'og:image', content: ogImage },
        { property: 'og:type', content: params.type || 'website' },
        { property: 'og:url', content: fullUrl },
        { property: 'og:site_name', content: 'マジキチメシ' },
        // Twitter Cards
        { name: 'twitter:card', content: 'summary_large_image' },
        { name: 'twitter:title', content: params.title },
        { name: 'twitter:description', content: params.description },
        { name: 'twitter:image', content: ogImage },
      ],
      link: [
        { rel: 'canonical', href: fullUrl }
      ]
    }
  }
  
  const generateJsonLd = (data: Record<string, any>) => {
    return {
      script: [
        {
          type: 'application/ld+json',
          innerHTML: JSON.stringify({
            '@context': 'https://schema.org',
            ...data
          })
        }
      ]
    }
  }
  
  return { generateSeoMeta, generateJsonLd, baseUrl }
}
```

### 2. TypeScript型定義
```typescript
// frontend/types/seo.ts
export interface SeoMetaParams {
  title: string
  description: string
  image?: string
  type?: 'website' | 'article'
  noindex?: boolean
}

export interface JsonLdData {
  '@type': string
  [key: string]: any
}
```

### 3. 機能要件
- Open Graph tags（Facebook共有）
- Twitter Cards（Twitter共有）
- canonical URL設定
- robots meta tag設定
- JSON-LD構造化データ
- 環境変数による動的URL生成

## テスト要件
- [ ] 各メタデータが正しく生成されることを確認
- [ ] 環境変数によるURL切り替えが動作することを確認
- [ ] 構造化データが有効なJSON-LD形式で出力されることを確認
- [ ] TypeScriptの型チェックが通ることを確認

## 注意点
- `useRoute()`はクライアント側でのみ利用可能
- 画像URLは絶対パスで指定する必要がある
- 構造化データはスキーマ仕様に準拠すること

## 参考情報
- [Open Graph Protocol](https://ogp.me/)
- [Twitter Cards](https://developer.twitter.com/en/docs/twitter-for-websites/cards)
- [Schema.org](https://schema.org/)
- [Google構造化データガイド](https://developers.google.com/search/docs/appearance/structured-data)

## 完了基準
- [ ] `composables/useSeoMeta.ts`実装完了
- [ ] 型定義ファイル作成完了
- [ ] 単体テスト実施・動作確認完了
- [ ] TypeScript型チェック通過確認