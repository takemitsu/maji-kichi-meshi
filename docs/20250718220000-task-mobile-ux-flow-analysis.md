# モバイルUXフロー・ナビゲーション問題分析 (2025-07-18)

操作フローとナビゲーションの観点からモバイルUIの問題点を分析し、使い勝手の改善提案を行います。

## 分析観点
- **戻る操作の一貫性**：各ページで戻るボタンや戻り先が統一されているか
- **タスク完了までの手順数**：レビュー作成、ランキング作成などの手順が多すぎないか
- **状態保持**：フォーム入力中の画面遷移で入力内容が失われないか
- **エラーからの回復**：ネットワークエラーなどから簡単に回復できるか
- **ショートカット機能**：よく使う操作にショートカットがあるか

## 🔴 **重大な問題（即座に対応が必要）**

### **1. 状態保持の不備**

**❌ フォーム入力中の状態保持なし**
- 長いフォーム入力中にページを離れると、**入力内容が全て失われる**
- リロード時やブラウザバック時も状態が失われる
- 特にレビュー作成で写真アップロード後に他の入力でエラーが発生すると、写真を再選択する必要がある

**現状の問題コード例:**
```vue
<!-- pages/reviews/create.vue -->
<script setup>
// 状態管理がリアクティブのみで、永続化されていない
const form = ref({
    rating: 0,
    repeat_intention: '',
    visited_at: '',
    memo: '',
})
</script>
```

**改善提案:**
```vue
<script setup>
// localStorage活用での状態保持
const FORM_STORAGE_KEY = 'review-form-draft'

const form = ref({
    rating: 0,
    repeat_intention: '',
    visited_at: '',
    memo: '',
})

// 状態を自動保存
watchEffect(() => {
    localStorage.setItem(FORM_STORAGE_KEY, JSON.stringify(form.value))
})

// 初期化時に復元
onMounted(() => {
    const saved = localStorage.getItem(FORM_STORAGE_KEY)
    if (saved) {
        const parsed = JSON.parse(saved)
        form.value = { ...form.value, ...parsed }
    }
})

// 送信成功時にクリア
const onSubmitSuccess = () => {
    localStorage.removeItem(FORM_STORAGE_KEY)
    // 画面遷移
}
</script>
```

**❌ 検索・フィルタ条件の保持不完全**
- 検索クエリは`useDebounceFn`で管理されているが、ページ離脱時に失われる
- カテゴリフィルタや評価フィルタも同様に保持されない

**改善提案:**
```vue
<script setup>
// URLパラメータでの状態管理
const route = useRoute()
const router = useRouter()

const updateURL = (params) => {
    router.push({
        query: { ...route.query, ...params }
    })
}

const selectedCategory = ref(route.query.category || '')
const selectedRating = ref(route.query.rating || '')

watch(selectedCategory, (newValue) => {
    updateURL({ category: newValue || undefined })
})
</script>
```

**❌ ページネーション状態が保持されない**
- 一覧ページの2ページ目から詳細ページに移動し、戻ると1ページ目に戻る
- URLパラメータでページ番号を管理していない

**改善提案:**
```vue
<script setup>
// ページネーション状態をURLで管理
const currentPage = ref(parseInt(route.query.page) || 1)

const handlePageChange = (page) => {
    currentPage.value = page
    updateURL({ page: page > 1 ? page : undefined })
    // スクロール位置も保持
    window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>
```

### **2. 戻る操作の一貫性不足**

**❌ 戻るボタンの位置が統一されていない**
- 作成・編集フォームで「キャンセル」ボタンが左下に配置されているが、これは**フォームの最下部**にあるため、長いフォームでは画面をスクロールしないと見えない
- モバイルでは画面上部にブラウザの戻るボタンがあるが、アプリ内の戻るボタンは画面下部にあるため、一貫性がない

**現状の問題:**
```vue
<!-- pages/reviews/create.vue -->
<div class="flex items-center justify-between pt-6">
    <NuxtLink to="/reviews" class="btn-secondary">キャンセル</NuxtLink>
    <button type="submit" class="btn-primary">レビューを作成</button>
</div>
```

**改善提案:**
```vue
<!-- 画面上部に固定ヘッダー -->
<div class="sticky top-0 z-10 bg-white border-b border-gray-200 px-4 py-3">
    <div class="flex items-center justify-between">
        <button @click="handleBack" class="flex items-center text-gray-600">
            <svg class="w-5 h-5 mr-2">...</svg>
            戻る
        </button>
        <h1 class="text-lg font-semibold">レビューを作成</h1>
        <button @click="saveDraft" class="text-blue-600">下書き保存</button>
    </div>
</div>
```

**❌ コンテキストを失う戻り操作**
- 検索結果一覧 → 詳細ページ → 戻る → 検索条件リセット
- ページネーション 2ページ目 → 詳細ページ → 戻る → 1ページ目に戻る

**改善提案:**
```vue
<script setup>
// 遷移元の状態を保持
const handleBack = () => {
    // 履歴がある場合は戻る
    if (window.history.length > 1) {
        router.back()
    } else {
        // 直接アクセスの場合は適切な一覧ページへ
        router.push('/reviews')
    }
}
</script>
```

### **3. タスク完了までの手順数の多さ**

**❌ レビュー作成のステップが多すぎる**
現在の手順：
1. 店舗選択（検索→選択）
2. 星評価入力
3. リピート意向選択
4. 訪問日入力
5. コメント入力（任意）
6. 写真アップロード（任意）
7. 送信

**計7ステップ**で、モバイルではスクロールが必要。最低4つの必須項目を入力しないと送信できない。

**改善提案: ステップ分割UI**
```vue
<template>
    <div class="step-container">
        <!-- プログレスバー -->
        <div class="progress-bar">
            <div class="progress-fill" :style="{ width: `${(currentStep / totalSteps) * 100}%` }"></div>
        </div>
        
        <!-- ステップ1: 店舗選択 -->
        <div v-if="currentStep === 1" class="step-content">
            <h2 class="step-title">どの店舗のレビューですか？</h2>
            <!-- 店舗選択UI -->
            <div class="step-actions">
                <button @click="nextStep" :disabled="!form.shop_id" class="btn-primary w-full">
                    次へ
                </button>
            </div>
        </div>
        
        <!-- ステップ2: 評価入力 -->
        <div v-if="currentStep === 2" class="step-content">
            <h2 class="step-title">この店舗の評価を教えてください</h2>
            <!-- 星評価 + リピート意向 -->
            <div class="step-actions">
                <button @click="prevStep" class="btn-secondary">戻る</button>
                <button @click="nextStep" :disabled="!isStep2Valid" class="btn-primary">次へ</button>
            </div>
        </div>
        
        <!-- ステップ3: 詳細情報 -->
        <div v-if="currentStep === 3" class="step-content">
            <h2 class="step-title">詳細情報（任意）</h2>
            <!-- 訪問日、コメント、写真 -->
            <div class="step-actions">
                <button @click="prevStep" class="btn-secondary">戻る</button>
                <button @click="submitReview" class="btn-primary">レビューを投稿</button>
            </div>
        </div>
    </div>
</template>
```

**❌ ランキング作成の複雑さ**
現在の手順：
1. タイトル入力
2. 説明入力（任意）
3. カテゴリ選択
4. 公開設定選択
5. 店舗検索
6. 店舗追加（複数回）
7. ドラッグ&ドロップで順序変更
8. 送信

**改善提案: 簡単作成モード**
```vue
<template>
    <div class="ranking-creation">
        <!-- 簡単作成モード -->
        <div v-if="mode === 'simple'" class="simple-mode">
            <h2>ランキングを作成</h2>
            <input v-model="title" placeholder="ランキング名を入力" class="input-field" />
            <select v-model="category" class="input-field">
                <option value="">カテゴリを選択</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>
            
            <!-- 店舗クイック追加 -->
            <div class="quick-add">
                <h3>店舗を追加（最大5店舗）</h3>
                <div v-for="(slot, index) in quickSlots" :key="index" class="rank-slot">
                    <div class="rank-number">{{ index + 1 }}</div>
                    <ShopQuickPicker v-model="slot.shop" />
                </div>
            </div>
            
            <div class="actions">
                <button @click="createRanking" class="btn-primary w-full">
                    ランキングを作成
                </button>
                <button @click="mode = 'advanced'" class="btn-secondary w-full mt-2">
                    詳細設定で作成
                </button>
            </div>
        </div>
    </div>
</template>
```

## 🟡 **中優先度の問題**

### **4. エラーからの回復**

**✅ 改善されている点：**
- `AlertMessage`コンポーネントでエラーメッセージを統一表示
- エラーのタイプ（認証エラー、バリデーションエラー、ネットワークエラー）に応じた適切なメッセージ
- リトライ機能付きのエラーメッセージ

**❌ 部分的失敗の処理が不十分**
- レビュー作成でレビューは成功したが画像アップロードが失敗した場合、ユーザーは再度全てのフォームを入力する必要がある
- ネットワークエラー時の状態復旧が不完全

**改善提案: 部分的成功の処理**
```vue
<script setup>
const submitReview = async () => {
    try {
        // メインのレビュー作成
        const reviewResponse = await $api.reviews.create(form.value)
        const reviewId = reviewResponse.data.id
        
        // 画像アップロード（別処理）
        const imageResults = []
        for (const image of uploadedImages.value) {
            try {
                const result = await $api.reviews.uploadImage(reviewId, image)
                imageResults.push({ success: true, result })
            } catch (error) {
                imageResults.push({ success: false, error, image })
            }
        }
        
        // 部分的成功の処理
        const failedImages = imageResults.filter(r => !r.success)
        if (failedImages.length > 0) {
            showPartialSuccessModal({
                reviewId,
                failedImages,
                onRetry: () => retryImageUpload(reviewId, failedImages)
            })
        } else {
            // 完全成功
            router.push(`/reviews/${reviewId}`)
        }
    } catch (error) {
        // 完全失敗
        showError('レビューの作成に失敗しました')
    }
}
</script>
```

### **5. ショートカット機能の不在**

**❌ よく使う操作へのクイックアクセスなし**
- 最近アクセスした店舗への直接アクセス機能なし
- お気に入り店舗の管理機能なし
- 同じ店舗への複数レビュー作成時に、前回の入力内容の再利用ができない

**改善提案: クイックアクセス機能**
```vue
<template>
    <div class="quick-access-panel">
        <h3>クイックアクション</h3>
        
        <!-- 最近の店舗 -->
        <div class="recent-shops">
            <h4>最近アクセスした店舗</h4>
            <div v-for="shop in recentShops" :key="shop.id" class="shop-quick-item">
                <div class="shop-info">
                    <h5>{{ shop.name }}</h5>
                    <p>{{ shop.address }}</p>
                </div>
                <div class="quick-actions">
                    <button @click="createReview(shop)" class="btn-sm">レビュー作成</button>
                    <button @click="viewShop(shop)" class="btn-sm">詳細</button>
                </div>
            </div>
        </div>
        
        <!-- お気に入り店舗 -->
        <div class="favorite-shops">
            <h4>お気に入り店舗</h4>
            <div v-for="shop in favoriteShops" :key="shop.id" class="shop-quick-item">
                <!-- 同様の構造 -->
            </div>
        </div>
    </div>
</template>
```

## 🔵 **モバイル固有の問題**

### **6. タップ領域とジェスチャー操作**

**❌ ドラッグ&ドロップによる順序変更がモバイルでは困難**
- 現在のランキング編集では、ドラッグ&ドロップでの順序変更がモバイルでは操作しにくい
- 小さな画面での精密な操作が必要

**改善提案: モバイルフレンドリーな順序変更**
```vue
<template>
    <div class="mobile-ranking-editor">
        <div v-for="(shop, index) in rankedShops" :key="shop.id" class="rank-item">
            <div class="rank-display">
                <span class="rank-number">{{ index + 1 }}</span>
                <div class="shop-info">
                    <h4>{{ shop.name }}</h4>
                    <p>{{ shop.address }}</p>
                </div>
            </div>
            
            <!-- モバイル用順序変更ボタン -->
            <div class="rank-controls">
                <button 
                    @click="moveUp(index)" 
                    :disabled="index === 0"
                    class="rank-btn">
                    <svg class="w-5 h-5">↑</svg>
                </button>
                <button 
                    @click="moveDown(index)" 
                    :disabled="index === rankedShops.length - 1"
                    class="rank-btn">
                    <svg class="w-5 h-5">↓</svg>
                </button>
            </div>
        </div>
    </div>
</template>
```

### **7. 画面占有率の最適化**

**❌ フォームが縦に長く、送信ボタンが画面外**
- 長いフォームでは送信ボタンが画面外にあり、スクロールが必要
- 現在の入力進捗が分からない

**改善提案: 固定アクションバー**
```vue
<template>
    <div class="mobile-form-container">
        <div class="form-content">
            <!-- フォーム内容 -->
        </div>
        
        <!-- 固定アクションバー -->
        <div class="fixed-action-bar">
            <div class="progress-indicator">
                <div class="progress-text">{{ completedFields }}/{{ totalFields }} 完了</div>
                <div class="progress-bar">
                    <div class="progress-fill" :style="{ width: `${progressPercent}%` }"></div>
                </div>
            </div>
            <button 
                @click="submitForm" 
                :disabled="!canSubmit"
                class="submit-btn">
                {{ submitButtonText }}
            </button>
        </div>
    </div>
</template>
```

## 📋 **実装優先度付きロードマップ**

### **🔥 即座に対応（1週間以内）**
1. **フォーム状態のlocalStorage保存**
   - レビュー作成、ランキング作成、店舗作成フォーム
   - 離脱時の下書き保存機能

2. **戻るボタンの統一**
   - 全フォームページに固定ヘッダー追加
   - 一貫した戻る操作の実装

3. **URLパラメータでの状態管理**
   - 検索条件、フィルタ条件の保持
   - ページネーション状態の保持

### **⚡ 短期対応（1ヶ月以内）**
1. **ステップ分割UI**
   - レビュー作成のウィザード形式
   - ランキング作成の簡単モード

2. **クイックアクセス機能**
   - 最近アクセスした店舗一覧
   - お気に入り店舗機能

3. **エラー回復機能の強化**
   - 部分的失敗の適切な処理
   - リトライ機能の充実

### **🔧 中期対応（3ヶ月以内）**
1. **モバイル最適化操作**
   - ドラッグ&ドロップの代替UI
   - タップ領域の最適化

2. **オフライン対応**
   - Service Worker導入
   - オフライン時の状態保存

3. **パフォーマンス最適化**
   - 遅延読み込み
   - 状態管理の最適化

## 🎯 **成功指標**

### **定量的指標**
- **フォーム完了率**: 60% → 85%
- **戻る操作での離脱率**: 40% → 20%
- **エラー発生時の復旧率**: 30% → 70%
- **タスク完了時間**: 30%短縮

### **定性的指標**
- **ユーザビリティテスト**: 満足度4.5/5以上
- **アクセシビリティスコア**: 90%以上
- **モバイルユーザビリティ**: 95%以上

## 🎨 **技術実装要件**

### **状態管理の強化**
```typescript
// composables/useFormPersistence.ts
export const useFormPersistence = <T>(key: string, initialValue: T) => {
    const data = ref<T>(initialValue)
    
    // 自動保存
    watchEffect(() => {
        localStorage.setItem(key, JSON.stringify(data.value))
    })
    
    // 復元
    onMounted(() => {
        const saved = localStorage.getItem(key)
        if (saved) {
            data.value = JSON.parse(saved)
        }
    })
    
    // クリア
    const clearPersistence = () => {
        localStorage.removeItem(key)
    }
    
    return { data, clearPersistence }
}
```

### **URL状態管理**
```typescript
// composables/useUrlState.ts
export const useUrlState = () => {
    const route = useRoute()
    const router = useRouter()
    
    const updateUrlState = (params: Record<string, any>) => {
        const query = { ...route.query }
        
        // 空値は削除
        Object.entries(params).forEach(([key, value]) => {
            if (value === null || value === undefined || value === '') {
                delete query[key]
            } else {
                query[key] = String(value)
            }
        })
        
        router.push({ query })
    }
    
    return { updateUrlState }
}
```

## 📊 **実装コスト見積もり**

### **Phase 1（状態保持・戻る操作）**
- **工数**: 5-7日
- **影響範囲**: 全フォームページ
- **技術難易度**: 中
- **効果**: 即座に体感可能

### **Phase 2（ステップ分割・クイックアクセス）**
- **工数**: 10-14日
- **影響範囲**: 作成フォーム、ダッシュボード
- **技術難易度**: 高
- **効果**: 大幅なUX向上

### **Phase 3（モバイル最適化・オフライン対応）**
- **工数**: 14-21日
- **影響範囲**: 全体
- **技術難易度**: 高
- **効果**: 長期的な満足度向上

## 🎊 **期待される効果**

この改善により、モバイルユーザーの操作体験が劇的に向上し、以下の効果が期待できます：

1. **タスク完了率の向上**: フォーム離脱率の大幅減少
2. **操作効率の向上**: 手順数削減により作業時間短縮
3. **ユーザー満足度の向上**: ストレスフリーな操作体験
4. **リテンション率の向上**: 使いやすさによる継続利用促進

これらの改善は、既存の視覚的改善（文字折り返し、タップ領域、視認性）と組み合わせることで、総合的にモバイルファーストなアプリケーションを実現できます。