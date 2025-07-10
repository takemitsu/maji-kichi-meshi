/**
 * 検索結果のハイライト機能を提供するコンポーザブル
 */
export function useSearchHighlight() {
    /**
     * テキスト内の検索クエリをハイライト
     * @param text ハイライト対象のテキスト
     * @param query 検索クエリ
     * @returns ハイライト済みHTML
     */
    const highlightText = (text: string, query: string): string => {
        if (!query || !text) return text

        // 特殊文字をエスケープ
        const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')

        // 大文字小文字を区別しない検索
        const regex = new RegExp(`(${escapedQuery})`, 'gi')

        return text.replace(regex, '<mark class="bg-yellow-200 text-yellow-900 font-medium rounded px-1">$1</mark>')
    }

    /**
     * 複数の検索語に対応したハイライト
     * @param text ハイライト対象のテキスト
     * @param queries 検索クエリの配列
     * @returns ハイライト済みHTML
     */
    const highlightMultipleTerms = (text: string, queries: string[]): string => {
        if (!queries.length || !text) return text

        let result = text

        queries.forEach((query) => {
            if (query.trim()) {
                result = highlightText(result, query.trim())
            }
        })

        return result
    }

    /**
     * 検索クエリを単語に分割
     * @param query 検索クエリ
     * @returns 分割された単語の配列
     */
    const splitSearchQuery = (query: string): string[] => {
        return query.split(/\s+/).filter((term) => term.length > 0)
    }

    /**
     * 検索結果の関連度を計算
     * @param text 対象テキスト
     * @param query 検索クエリ
     * @returns 関連度スコア (0-1)
     */
    const calculateRelevanceScore = (text: string, query: string): number => {
        if (!query || !text) return 0

        const lowerText = text.toLowerCase()
        const lowerQuery = query.toLowerCase()

        // 完全一致
        if (lowerText === lowerQuery) return 1.0

        // 前方一致
        if (lowerText.startsWith(lowerQuery)) return 0.9

        // 単語の境界での一致
        const wordBoundaryRegex = new RegExp(`\\b${lowerQuery}\\b`)
        if (wordBoundaryRegex.test(lowerText)) return 0.8

        // 部分一致
        if (lowerText.includes(lowerQuery)) return 0.7

        // 類似度計算（簡易版）
        const queryWords = splitSearchQuery(lowerQuery)
        const matchingWords = queryWords.filter((word) => lowerText.includes(word))

        return (matchingWords.length / queryWords.length) * 0.5
    }

    return {
        highlightText,
        highlightMultipleTerms,
        splitSearchQuery,
        calculateRelevanceScore,
    }
}
