export interface SeoMetaParams {
  title: string
  description: string
  image?: string
  type?: 'website' | 'article'
  noindex?: boolean
}

export interface JsonLdData {
  '@type': string
  [key: string]: unknown
}