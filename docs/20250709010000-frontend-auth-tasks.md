# フロントエンド認証系対応タスク

**作成日**: 2025-07-09 01:00:00  
**対象**: Vue.js + Nuxt.js 3 フロントエンドプロジェクト  
**担当**: フロントエンド担当Claude  
**依頼者**: バックエンド担当Claude

## 🎯 対応概要

フロントエンドコードレビューで発見された認証系の問題について、フロントエンド側で対応すべき項目をまとめました。バックエンド側ではOAuthコールバック処理の修正等のAPI仕様変更を並行して実施予定です。

## 📋 対応必要項目

### 🔴 **最優先対応 (Critical)**

#### 1. バックエンド仕様変更への対応
**現状**: バックエンドのOAuthコールバック処理が修正され、URLパラメータ形式が変更
```typescript
// 変更前のフロントエンド実装
if (route.query.token && route.query.user) {
  const token = route.query.token as string
  const userData = JSON.parse(decodeURIComponent(route.query.user as string))
}

// 変更後のバックエンド仕様
// ?access_token=xxx&user_id=123&user_name=xxx&user_email=xxx&success=true
```

**対応内容**:
- URLパラメータ名の変更対応（`token` → `access_token`）
- ユーザーデータ取得方法の変更（JSON文字列 → 個別パラメータ）
- `/auth/callback.vue` ページの新規作成
- `auth.client.ts` プラグインの修正
- エラーケース対応（`error=oauth_failed`）

#### 2. OAuth認証フローの完全実装
**現状**: 基本構造のみ存在、実際の認証処理が未完成
```typescript
// pages/auth/callback.vue - 現在の実装
// コールバック処理が不完全
```

**対応内容**:
- OAuth認証プロバイダー選択画面の実装
- 各プロバイダー（Google, GitHub, LINE, Twitter）の認証ボタン
- 認証後のコールバック処理完全実装
- 認証エラー時のエラーハンドリング

#### 3. JWT トークン管理の改善
**現状**: 基本的なトークン保存のみ
```typescript
// stores/auth.ts - 現在の実装
// トークンの有効期限チェックが不完全
```

**対応内容**:
- トークンの有効期限チェック機能
- 自動ログアウト機能（トークン期限切れ時）
- トークンリフレッシュ機能（必要に応じて）
- セキュアなトークン保存方法の実装

#### 4. 認証状態管理の強化
**現状**: 基本的な状態管理のみ
```typescript
// stores/auth.ts - 現在の実装
// 認証状態の詳細管理が不十分
```

**対応内容**:
- 認証状態の詳細管理（ログイン中、認証期限切れ、エラー等）
- 認証状態に応じたUI表示制御
- ルート保護ミドルウェアの改善

### 🟡 **重要対応 (High)**

#### 5. 型安全性の改善
**現状**: 多くの箇所で `any` 型を使用
```typescript
// 現在の問題箇所
list: () => apiFetch<{ data: any[] }>('/categories')
```

**対応内容**:
- 認証関連の型定義作成
- API レスポンスの型定義
- 型安全なAPI呼び出し

#### 6. エラーハンドリングの統一
**現状**: 認証エラーの処理が不統一
```typescript
// 現在の実装
// エラーハンドリングが各所でバラバラ
```

**対応内容**:
- 認証エラーの統一処理
- ユーザーフレンドリーなエラーメッセージ
- エラー状態の適切な表示

### 🟢 **推奨対応 (Medium)**

#### 7. セキュリティ強化
**対応内容**:
- CSRF対策の実装
- XSS対策の強化
- セキュアなCookie設定

#### 8. UX改善
**対応内容**:
- ローディング状態の表示
- 認証処理中のフィードバック
- 直感的な認証フロー

## 🔧 技術的な詳細

### OAuth認証フロー
```
1. ユーザーが認証プロバイダーを選択
2. 該当プロバイダーの認証画面にリダイレクト
3. 認証後、コールバックURLに戻る
4. 認証コードを取得してJWTトークンを要求
5. トークンを保存して認証状態を更新
```

### 期待するAPI仕様
```typescript
// 認証後のレスポンス例
interface AuthResponse {
  access_token: string;
  token_type: string;
  expires_in: number;
  user: {
    id: number;
    name: string;
    email: string;
    avatar?: string;
  };
}
```

### 認証状態管理
```typescript
// stores/auth.ts での状態管理
interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
}
```

## 📚 参考資料

- **フロントエンドコードレビュー**: `/docs/20250709005500-frontend-code-review.md`
- **バックエンドレビューレポート**: `/docs/20250709005500-backend-review-report.md`
- **統合テストレポート**: `/docs/20250709005500-integration-test-report.md`
- **技術仕様書**: `/docs/technical-specs.md`

## 💡 実装時の注意点

1. **バックエンドとの連携**: OAuth コールバック処理は並行してバックエンド側でも修正されます
2. **セキュリティ**: 認証トークンの取り扱いには十分注意してください
3. **UX**: 認証処理中のユーザー体験を重視してください
4. **テスト**: 認証関連の処理は必ずテストを作成してください

## 📞 連絡・質問

認証系の仕様に関する質問や、バックエンドとの連携が必要な場合は、バックエンド担当Claudeまでご連絡ください。

---

**この対応により、マジキチメシプロジェクトの認証システムが完全に機能し、ユーザーにとって使いやすく安全なアプリケーションになることを期待しています。**