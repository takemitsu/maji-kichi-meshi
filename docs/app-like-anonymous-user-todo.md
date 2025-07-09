# アプリライク匿名ユーザー設計 TODO

## 概要
現代的なアプリの標準パターンに従った設計：
- **アクセス即利用**: 初回訪問時に自動的に匿名ユーザーとして登録
- **プロフィール後付け**: 使いながら徐々にプロフィール充実
- **OAuth = 同期手段**: アカウント作成ではなくデバイス間同期のツール

## 設計思想
```
従来Web: ゲスト → ログイン → 正式ユーザー
新設計:   アクセス → 即匿名ユーザー → プロフィール充実 → OAuth同期
```

---

## Phase 1: 自動匿名ユーザー登録システム

### 1.1 データベース設計
- [ ] **ユーザーテーブル拡張**
  - `users` テーブル: `is_anonymous` カラム追加 (boolean, default: true)
  - `device_id` カラム追加 (UUID, localStorage連携用)
  - `display_name` カラム追加 (nullable, 「匿名ユーザー001」等)
  - `oauth_provider_id` を nullable に変更

- [ ] **匿名ユーザー生成ルール**
  - 初回アクセス時に自動生成
  - `display_name`: 「匿名ユーザー{連番}」
  - `device_id`: UUID v4生成
  - `is_anonymous`: true

### 1.2 認証システム改造
- [ ] **自動ログイン機能**
  - `middleware/autoAuth.ts` 作成
  - localStorage の device_id チェック
  - 存在しない場合 → 匿名ユーザー自動作成
  - JWT自動発行・保存

- [ ] **既存認証との統合**
  - `stores/auth.ts` 拡張
  - 匿名状態 vs OAuth連携済み状態の管理
  - `isAnonymous` フラグ追加

---

## Phase 2: プロフィール後付けシステム

### 2.1 プロフィール編集画面
- [ ] **プロフィール画面作成**
  - `/pages/profile/index.vue` 作成
  - 現在の状態表示（匿名 or 連携済み）
  - 表示名編集フォーム
  - アイコン設定（将来）

- [ ] **匿名ユーザー向けUI**
  - 「匿名ユーザー001」表示
  - 「表示名を変更」ボタン
  - 「他デバイスと同期するには」案内

### 2.2 表示名変更機能
- [ ] **バックエンドAPI**
  - `PUT /api/profile/display-name` 
  - 匿名ユーザーでも表示名変更可能
  - 重複チェック・バリデーション

- [ ] **フロントエンド実装**
  - 表示名編集フォーム
  - リアルタイム検証
  - 変更完了フィードバック

---

## Phase 3: OAuth同期システム

### 3.1 OAuth の役割転換
- [ ] **OAuth機能再定義**
  - ❌ アカウント作成
  - ✅ プロフィール連携
  - ✅ デバイス間同期
  - ✅ データバックアップ

- [ ] **OAuth連携API**
  - `POST /api/profile/connect-oauth` 
  - 匿名ユーザー → OAuth連携済みユーザー
  - `is_anonymous`: false に変更
  - プロフィール情報の自動入力

### 3.2 デバイス間同期
- [ ] **同期仕組み**
  - OAuth連携済みユーザーの `device_id` 更新
  - 新デバイスでOAuth → 既存データと統合
  - 複数デバイス対応

- [ ] **同期UI**
  - 「他デバイスと同期」ボタン
  - 同期状態の表示
  - 同期完了通知

---

## Phase 4: UI/UX 全面改修

### 4.1 ヘッダー・ナビゲーション
- [ ] **ログイン概念の削除**
  - 「ログイン」ボタン削除
  - 「プロフィール」ボタン追加
  - 匿名状態の表示

- [ ] **ユーザー状態表示**
  - 匿名ユーザー: 「匿名ユーザー001」
  - 連携済み: OAuth由来の名前・アイコン
  - 同期状態インジケーター

### 4.2 投稿時のUX
- [ ] **投稿者名表示**
  - レビュー・ランキングに表示名表示
  - 「匿名ユーザー001」「田中太郎」など統一
  - 投稿時の「この名前で投稿されます」案内

- [ ] **プロフィール誘導**
  - 投稿後: 「表示名を変更しませんか？」
  - 複数投稿後: 「他デバイスと同期しませんか？」
  - 自然な誘導タイミング

### 4.3 初回訪問体験
- [ ] **ウェルカムメッセージ**
  - 「匿名ユーザー001として登録しました」
  - 「すぐに投稿を始められます」
  - 「後でプロフィールを設定できます」

- [ ] **チュートリアル**
  - 初回投稿ガイド
  - プロフィール設定案内
  - OAuth同期の説明

---

## Phase 5: 既存機能の適応

### 5.1 トップページ改修
- [ ] **「ログインして始めましょう」削除**
  - 既に匿名ユーザーとして登録済み
  - 「あなたのランキングを作成」
  - 「プロフィールを設定」CTA

### 5.2 投稿機能の調整
- [ ] **投稿時の表示**
  - 「匿名ユーザー001として投稿」
  - 投稿完了後の名前変更提案
  - OAuth同期の自然な提案

### 5.3 一覧画面の調整
- [ ] **投稿者名表示**
  - 匿名ユーザー: 「匿名ユーザー001」
  - 連携済み: OAuth由来の名前
  - 統一された表示ルール

---

## Phase 6: 管理・運用

### 6.1 匿名ユーザー管理
- [ ] **管理画面対応**
  - 匿名ユーザー一覧表示
  - OAuth連携率の監視
  - 非アクティブユーザーの処理

### 6.2 データクリーンアップ
- [ ] **不要データ削除**
  - 長期間非アクティブな匿名ユーザー
  - 投稿のない匿名ユーザー
  - 自動削除ルール

---

## 技術仕様

### 自動匿名ユーザー登録フロー
```javascript
// middleware/autoAuth.ts
export default defineNuxtRouteMiddleware(async () => {
  const deviceId = localStorage.getItem('device_id')
  
  if (!deviceId) {
    // 新規デバイス → 匿名ユーザー自動作成
    const response = await $api.auth.createAnonymousUser()
    localStorage.setItem('device_id', response.device_id)
    localStorage.setItem('jwt_token', response.token)
  } else {
    // 既存デバイス → JWT検証・更新
    await $api.auth.validateToken()
  }
})
```

### OAuth同期フロー
```javascript
// OAuth連携時の処理
async function connectOAuth(provider) {
  const result = await $api.auth.oauth(provider)
  
  // 匿名ユーザー → OAuth連携済みユーザー
  await $api.profile.connectOAuth({
    device_id: localStorage.getItem('device_id'),
    oauth_data: result
  })
  
  // is_anonymous: false に更新
  authStore.user.is_anonymous = false
}
```

### データベーススキーマ
```sql
-- users テーブル拡張
ALTER TABLE users ADD COLUMN is_anonymous BOOLEAN DEFAULT true;
ALTER TABLE users ADD COLUMN device_id UUID UNIQUE;
ALTER TABLE users ADD COLUMN display_name VARCHAR(255);
ALTER TABLE users ALTER COLUMN name DROP NOT NULL;

-- OAuth連携を任意に
ALTER TABLE oauth_providers ALTER COLUMN user_id DROP NOT NULL;
```

---

## UX フロー例

### 初回訪問ユーザー
```
1. サイトアクセス
2. 自動的に「匿名ユーザー001」として登録
3. 即座に投稿可能
4. 投稿後「表示名を変更しませんか？」
5. 複数投稿後「他デバイスと同期しませんか？」
```

### リピートユーザー
```
1. サイトアクセス
2. localStorage の device_id で自動ログイン
3. 「匿名ユーザー001」として継続利用
4. 好きなタイミングでプロフィール充実
```

### デバイス同期ユーザー
```
1. 新デバイスでアクセス
2. OAuth連携選択
3. 「既存データと同期しますか？」
4. 同期完了、全デバイスで利用可能
```

---

## マイルストーン

- **Week 1**: Phase 1 完了 (自動匿名ユーザー登録)
- **Week 2**: Phase 2 完了 (プロフィール後付け)
- **Week 3**: Phase 3 完了 (OAuth同期)
- **Week 4**: Phase 4 完了 (UI/UX改修)
- **Week 5**: Phase 5 完了 (既存機能適応)
- **Week 6**: Phase 6 完了 (管理・運用)

## 期待される効果

### ユーザー体験
- ✅ 即座に価値提供
- ✅ 摩擦のない開始体験
- ✅ 段階的なエンゲージメント
- ✅ 自然な機能発見

### ビジネス効果
- ✅ 離脱率大幅減少
- ✅ 投稿率向上
- ✅ ユーザー定着率向上
- ✅ OAuth連携率向上

### 技術的利点
- ✅ Cookie不要設計維持
- ✅ プライバシーファースト
- ✅ モバイルアプリ対応容易
- ✅ 既存システムとの互換性

---

## 注意事項

1. **既存ユーザーへの影響**: 現在のOAuthユーザーとの互換性確保
2. **プライバシー**: 匿名ユーザーデータの適切な管理
3. **パフォーマンス**: 自動登録によるユーザー数増加対策
4. **セキュリティ**: device_id の偽装・重複対策
5. **UX設計**: 複雑になりすぎない自然な誘導

この設計により、マジキチメシは現代的なアプリと同等の使いやすさを提供できます。