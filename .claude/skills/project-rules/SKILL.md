---
name: プロジェクトルール遵守
description: CLAUDE.mdの重要ルールの再確認。作業開始前、コミット前、ユーザーとの対話時に使用。
---

# プロジェクトルール遵守

このSkillは、CLAUDE.md（グローバル + プロジェクト固有）の重要なルールをまとめたものです。

---

## コミュニケーションルール

### 基本方針

- ✅ **日本語で対話**
- ✅ **正確な情報を伝える**
  - IT技術者が相手なので「完璧」「完了」などの嘘は厳禁
  - 終わっていないのに「完了しました」と虚偽報告しない
  - 不確実なことは「〜かもしれません」と明示

- ❌ **無駄な謝罪不要**
  - tokenの無駄遣いをしない

- ❌ **嘘をつかない**
  - 「二度としません」「次回から気をつけます」は禁止
  - 再発防止策を具体的に提示する

### 報告の仕方

```
# ❌ 悪い例
「完璧に動作します」
「全て完了しました」
「次回から気をつけます」

# ✅ 良い例
「テストは通過しましたが、エッジケースの確認が必要です」
「実装は完了しました。品質チェックを実行してもよろしいでしょうか？」
「このエラーを防ぐため、XXXの確認を追加しました」
```

---

## コミットルール

### ソースコードのコミット

**必ずユーザーの判断を仰ぐ**

#### 手順

1. **テスト結果を提示**
   ```bash
   # PHP変更時
   composer pint
   ./vendor/bin/phpstan analyse --memory-limit=1024M
   php artisan test --parallel

   # JS/Vue/TS変更時
   npm run lint
   npm run format
   npm run type-check
   ```

2. **変更内容の説明**
   - 何を変更したか
   - なぜ変更したか
   - 影響範囲

3. **ユーザーの承認を待つ**
   ```
   「テスト結果とコード品質チェックの結果は上記の通りです。
   コミットしてもよろしいでしょうか？」
   ```

### ドキュメントのコミット

**自己判断でOK**

- `.md` ファイル等のドキュメント
- `docs/` 配下のファイル
- コメントのみの変更

ただし、重要な変更の場合はユーザーに報告する。

### コミットメッセージ

- **日本語を使用**（なるべく）
- 変更の本質を簡潔に表現
- 例:
  - `feat: 店舗一覧ソート機能実装`
  - `fix: MySQL互換性修正`
  - `docs: 開発ワークフロー更新`

---

## 品質チェック

### 実行タイミング

- ソースコード変更後
- コミット前
- プルリクエスト作成前

### PHP修正時（backend/）

```bash
cd backend

# 1. コードフォーマット
composer pint

# 2. 静的解析
./vendor/bin/phpstan analyse --memory-limit=1024M

# 3. テスト（新機能/修正時のみ）
php artisan test --parallel
```

### JS/Vue/TS修正時（frontend/）

```bash
cd frontend

# 1. ESLint
npm run lint

# 2. Prettier
npm run format

# 3. TypeScript型チェック
npm run type-check
```

### 該当する変更のみ実行

- PHP ファイルを触っていないのに `composer pint` は不要
- JS ファイルを触っていないのに `npm run lint` は不要

---

## プロジェクト固有の技術情報

### Tech Stack

- **Frontend**: Vue.js 3 + Nuxt.js 3 (SPA) / Tailwind CSS / TypeScript
- **Backend**: Laravel 12 API / Filament (管理画面) / Intervention Image
- **Database**: MySQL (本番) / SQLite (開発・テスト)
- **Cache**: Redis (Rate Limiter専用)
- **Auth**: OAuth (Google) → JWT / ハイブリッド認証

### Laravel 12 注意事項

```php
// ✅ 正しい: 平文パスワード渡し（自動ハッシュ化）
User::create([
    'password' => 'plain-text-password',  // 'hashed' キャスト使用時
]);

// ❌ 間違い: updateOrCreate では手動ハッシュ化が必要な場合あり
User::updateOrCreate([...], [
    'password' => 'plain-text-password',  // ハッシュ化されない可能性
]);
```

### Filament本番要件

- `User` モデルに `FilamentUser` インターフェース実装必須
- nginx設定: `location ^~ /admin` でSPA catch-allより優先度を高くする
- 管理者2FA必須（FilamentAdminMiddleware実装済み）

---

## ファイル構造

```
maji-kichi-meshi/
├── backend/          # Laravel API
│   ├── app/
│   ├── database/
│   └── routes/
├── frontend/         # Vue/Nuxt SPA
│   ├── components/
│   ├── pages/
│   └── plugins/
└── docs/             # 設計書類（プロジェクトルート直下のみ！）
    ├── concept.md
    ├── technical-specs.md
    ├── database-er-diagram.md
    └── features/
```

---

## よくある間違い防止

### 1. コミット前確認忘れ

```
# ❌ 間違い
「実装しました。コミットします」

# ✅ 正しい
「実装しました。品質チェックを実行します」
→ [テスト実行]
→ 「テスト結果は上記の通りです。コミットしてもよろしいでしょうか？」
```

### 2. 虚偽の完了報告

```
# ❌ 間違い
「完璧に動作します」（実際にはエッジケース未確認）

# ✅ 正しい
「基本機能は動作確認済みです。以下のエッジケースは未確認です: ...」
```

### 3. 無駄な謝罪

```
# ❌ 間違い
「申し訳ございません。次回から気をつけます」

# ✅ 正しい
「エラーの原因はXXXでした。今後はYYYで防ぎます」
```

---

## MCP Chrome DevTools 制約

### スクリーンショット撮影の制限

```javascript
// ❌ 絶対禁止: fullPage
take_screenshot({ fullPage: true })  // セッションクラッシュの原因

// ✅ 正しい: ビューポートのみ
take_screenshot()

// ✅ 正しい: 圧縮形式
take_screenshot({ format: "jpeg", quality: 70 })
```

**理由**: `fullPage: true` を使うと、大きなページでAPI制限（8000px）を超えてセッションがクラッシュする。

---

## チェックリスト

作業前に以下を確認:

- [ ] 日本語で対話しているか？
- [ ] 正確な情報を伝えているか？（嘘をついていないか？）
- [ ] コミット前に品質チェックを実行したか？
- [ ] ソースコードコミット時にユーザーの承認を得たか？
- [ ] 「完璧」「完了」などの不正確な表現を使っていないか？
- [ ] 無駄な謝罪をしていないか？

---

## まとめ

### 最重要ルール

1. **正確な情報** - 嘘をつかない、推測を事実として伝えない
2. **ユーザー承認** - ソースコードコミット前に必ず確認
3. **品質チェック** - コミット前に該当するチェックを実行
4. **日本語対話** - IT技術者向けの正確な日本語

**ユーザーはIT技術者。正確な情報とプロフェッショナルな対応を求めている。**
