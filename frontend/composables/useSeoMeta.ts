export const useCustomSeoMeta = () => {
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
    const ogImage = params.image || `${baseUrl}/android-chrome-512x512.png`
    
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
        { rel: 'canonical', href: fullUrl },
      ],
    }
  }
  
  const generateJsonLd = (data: Record<string, unknown>) => {
    return {
      script: [
        {
          type: 'application/ld+json',
          innerHTML: JSON.stringify({
            '@context': 'https://schema.org',
            ...data,
          }),
        },
      ],
    }
  }
  
  return { generateSeoMeta, generateJsonLd, baseUrl }
}