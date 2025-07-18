# フロントエンドUI要素の詳細調査レポート

## 1. ボタン vs リンクの使い分けの問題

### 1.1 作成系アクション

#### 現状の実装（ボタンスタイル使用）
- `/rankings/index.vue:29`: `<NuxtLink to="/rankings/create" class="btn-primary">ランキングを作成</NuxtLink>`
- `/rankings/public.vue:225`: `<NuxtLink to="/rankings/create" class="btn-primary">あなたもランキングを作成</NuxtLink>`
- `/reviews/index.vue:20`: `<NuxtLink to="/reviews/create" class="btn-primary">レビューを作成</NuxtLink>`
- `/shops/index.vue:20`: `<NuxtLink to="/shops/create" class="btn-primary">店舗を追加</NuxtLink>`

#### 空の状態での実装（同じくボタンスタイル）
- `/rankings/index.vue:245`: `<NuxtLink to="/rankings/create" class="btn-primary">ランキングを作成</NuxtLink>`
- `/reviews/index.vue:278`: `<NuxtLink to="/reviews/create" class="btn-primary">レビューを作成</NuxtLink>`

### 1.2 ナビゲーション系

#### ボタンスタイル（btn-secondary）を使用
- `/rankings/index.vue:14`: `<NuxtLink to="/rankings/public" class="btn-secondary">公開ランキング</NuxtLink>`

#### リンクスタイルを使用
- `/rankings/public.vue:14`: `<NuxtLink to="/rankings" class="text-gray-600 hover:text-gray-900">マイランキング</NuxtLink>`

#### ボタンスタイル（btn-primary）を使用
- `/index.vue:32-35`: `<NuxtLink to="/rankings/public" class="btn-primary">ランキングを見る</NuxtLink>`
- `/index.vue:58-61`: `<NuxtLink to="/reviews" class="btn-primary">レビューを見る</NuxtLink>`

### 1.3 詳細表示系

#### テキストリンクスタイル
- `/rankings/index.vue:158`: `<NuxtLink class="text-sm text-blue-600">詳細</NuxtLink>`
- `/rankings/public.vue:136`: `<NuxtLink class="bg-gray-100 text-gray-700">詳細を見る</NuxtLink>`（ボタン風だが別スタイル）

## 2. 用語の不統一

### 2.1 「マイランキング」vs「ランキング管理」
- **ヘッダー（TheHeader.vue）**: 
  - L28: `マイランキング`（ナビゲーション項目）
  - L169: `マイランキング`（モバイルメニュー）
- **ランキング管理ページ（rankings/index.vue）**:
  - L9: `ランキング管理`（ページタイトル）
- **公開ランキングページ（rankings/public.vue）**:
  - L18: `マイランキング`（戻るリンク）
- **ランキング詳細ページ（rankings/[id]/index.vue）**:
  - L19: `マイランキング`（ブレッドクラム）

### 2.2 「公開ランキング」vs「ランキング」
- **ヘッダー（TheHeader.vue）**:
  - L20: `ランキング`（公開ランキングへのリンク）
  - L180: `ランキング`（モバイルメニュー）
- **ランキング管理ページ（rankings/index.vue）**:
  - L27: `公開ランキング`（ボタンテキスト）
- **公開ランキングページ（rankings/public.vue）**:
  - L9: `ランキング`（ページタイトル）

### 2.3 助詞の有無
- **作成系**:
  - `ランキングを作成` （rankings/index.vue:33, rankings/create.vue:7）
  - `レビューを作成` （reviews/index.vue:24, reviews/create.vue:7）
  - `店舗を追加` （shops/index.vue:24）
  - `あなたもランキングを作成` （rankings/public.vue:229）
- **管理系**:
  - `ランキングを編集` （dashboard.vue:65）
  - `レビュー一覧を見る` （dashboard.vue:71）
  - `店舗一覧を見る` （dashboard.vue:77）
- **閲覧系**:
  - `ランキングを見る` （index.vue:35）
  - `レビューを見る` （index.vue:61）
  - `店舗を探す` （index.vue:87）

## 3. ナビゲーション階層の問題

### 3.1 公開ランキング ← → マイランキング間の導線

#### マイランキング → 公開ランキング
- **方法**: btn-secondaryボタン（rankings/index.vue:14）
- **位置**: ページヘッダー右側

#### 公開ランキング → マイランキング
- **方法**: テキストリンク（rankings/public.vue:14）
- **位置**: ページヘッダー右側
- **問題**: スタイルが異なり、対称性がない

### 3.2 詳細ページからの戻り導線

#### ランキング詳細ページ（rankings/[id]/index.vue）
- **ブレッドクラム**: 「公開ランキング」または「マイランキング」（L19）
- **ページ下部**: 「[公開ランキング/マイランキング]一覧に戻る」（L235-237）
- **問題**: 同じ機能が2箇所にある

### 3.3 ヘッダーナビゲーションの階層
- **公開向け**: 「ランキング」→ `/rankings/public`
- **個人向け**: 「マイランキング」→ `/rankings`
- **問題**: 「ランキング」という名前が公開ランキングを指すのか分かりにくい

## 推奨される改善案

### 1. ボタン/リンクの統一ルール
- **作成系アクション**: btn-primaryで統一（現状維持）
- **ページ間ナビゲーション**: 
  - 重要度高: btn-secondary
  - 通常: テキストリンク
- **詳細表示**: テキストリンクで統一

### 2. 用語の統一
- 「マイランキング」で統一（「ランキング管理」は使わない）
- 公開ランキングは「みんなのランキング」に変更を検討
- 助詞は「〜を作成」「〜を見る」で統一

### 3. ナビゲーション構造の改善
- 公開/非公開の切り替えをタブUIで実装
- ブレッドクラムと戻るリンクの重複を解消
- ヘッダーのラベルを明確化（「みんなのランキング」「マイランキング」）