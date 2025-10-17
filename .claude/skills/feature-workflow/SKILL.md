---
name: 機能開発ワークフロー
description: 新機能開発時のdocs/features/管理方法。機能開発、新機能、タスク作成、plan作成、progress更新時に使用。
---

# 機能開発ワークフロー

このSkillは、`docs/features/` を使った機能開発の標準的な進め方を定義します。

---

## ファイル構成（2ファイルのみ）

```
docs/features/feature-name/
├── plan.md           # 実装前の計画（要件、設計、完了基準）
└── progress.md       # 進捗管理（タスク、課題、完了報告）
```

**シンプルに保つ:**
- 細かいタスクファイル（01-xxx.md, 02-xxx.md）は不要
- README.md, completion.md, implementation-review.md も不要
- 全て plan.md と progress.md に集約

---

## plan.md テンプレート

```markdown
# 機能名

## 概要
何を作るか（1-2行）

## 要件
- 必須機能
- 制約条件
- 対象ページ・URL（全て列挙）

## 技術設計
### API設計
- エンドポイント一覧
- リクエスト・レスポンス形式

### データベース変更
- 新規テーブル or カラム追加
- マイグレーション内容

### 影響範囲
- 既存機能への影響
- 変更が必要なファイル

## テスト計画（API実装時は必須）
（APIテスト要件 Skill 参照）

## 実装前チェック
不明点や確認事項があれば記載:
- [ ] XXX の仕様は？
- [ ] YYY の実装方法は？

不明点がなければこのセクションは省略可。

## 完了基準
- [ ] 機能が動作する
- [ ] テストが全て通る
- [ ] 品質チェック完了
- [ ] ドキュメント更新（必要に応じて）
```

**ポイント:**
- **What（何を）** に集中、**How（どう）** は実装者に任せる
- 対象ページ・URLは**全て**列挙（実装漏れ防止）
- 不明点は推測せず、計画段階で質問

---

## progress.md テンプレート

```markdown
# 進捗管理

## タスク一覧

| タスク | ステータス | 担当 | 完了日時 | 備考 |
|--------|-----------|------|----------|------|
| API実装 | ✅ 完了 | Claude | 2025-01-17 | 3エンドポイント追加 |
| フロントエンド | 🔄 実行中 | Claude | - | |
| テスト | ⏳ 未着手 | Claude | - | |

**ステータス:**
- ⏳ 未着手
- 🔄 実行中
- ✅ 完了

## 発見した課題・対応方法

### 課題1: XXX の問題
- **問題**: ...
- **対応**: ...
- **結果**: ...

## 完了報告（最後に記入）

### 実装内容サマリー
- 追加したファイル
- 変更したファイル
- 主要な実装内容

### テスト結果
```bash
php artisan test --parallel
# 結果: 全て成功
```

### 残課題（あれば）
- 今後の改善点
- 未対応の機能
```

**ポイント:**
- 厳格な更新義務なし（適宜更新）
- 完了時には必ず記録（完了日時、サマリー）
- 途中経過は任意（柔軟に運用）

---

## 既存 features の参照方法

新機能を作成する際は、既存の features/ を参考にする:

```bash
# 既存 features を確認
ls -la /Users/takemitsusuzuki/work/personal/maji-kichi-meshi/docs/features/

# 例: like-and-wishlist を参考にする
cat docs/features/like-and-wishlist/plan.md
cat docs/features/like-and-wishlist/progress.md
```

**参考になる features:**
- `like-and-wishlist` - お気に入り・ウィッシュリスト機能
- `test-coverage-improvement` - テストカバレッジ改善
- その他、実装済みの features

---

## よくある失敗パターン

### 1. 実装漏れ

**問題:**
- 対象ページが複数あるのに一部のみ実装
- 公開ページと認証ページの混同

**対策:**
- plan.md で**対象ページ・URL を全て列挙**
- 各ページの認証要件を明記
- 不明点は推測せず質問

**例:**

```markdown
## 要件

### 対象ページ（全て実装）
- `/rankings` - 公開ページ（認証不要）
- `/rankings/{id}` - 詳細ページ（認証不要）
- `/user/rankings` - マイランキング（認証必要）
- `/user/rankings/{id}/edit` - 編集ページ（認証必要）

### 認証要件
- 公開ページ: 認証不要、全ユーザーが閲覧可能
- マイページ: JWT認証必須、自分のデータのみ
```

### 2. progress.md 未更新

**問題:**
- 完了したのに progress.md が更新されていない
- 次の担当者が進捗を把握できない

**対策:**
- **完了時には必ず記録**（完了日時、サマリー）
- 途中経過は任意だが、完了報告は必須

### 3. テスト不足

**問題:**
- API実装時にテストが不十分
- auth系、数字系のテストが欠けている

**対策:**
- **API実装時は「APIテスト要件」Skill を参照**
- plan.md にテスト計画セクションを追加
- 完了基準に「テスト全成功」を含める

---

## 実装開始の流れ

### ユーザーからの依頼

```
ユーザー: 「お気に入り機能を追加したい」
```

### Claude の対応

1. **既存 features を参考に plan.md 作成**
   ```bash
   # like-and-wishlist を参考
   cat docs/features/like-and-wishlist/plan.md
   ```

2. **plan.md に記載**
   - 概要、要件、技術設計
   - 対象ページ・URL（全て列挙）
   - テスト計画（API実装時）
   - 完了基準

3. **不明点があれば質問**
   - 推測で実装しない
   - 実装前チェックセクションに記載

4. **ユーザー承認後、progress.md 作成**
   - タスク一覧
   - ステータス管理開始

5. **実装開始**
   - コード実装 + テスト
   - 適宜 progress.md 更新

6. **完了報告**
   - progress.md に完了サマリー記録
   - テスト結果提示
   - ユーザーに報告

---

## 完了基準

機能開発が「完了」とみなされるのは:

- [ ] plan.md の完了基準が全て満たされている
- [ ] テストが全て実装・成功している
- [ ] 品質チェック完了（pint, phpstan, test --parallel / lint, type-check）
- [ ] progress.md に完了報告が記録されている

**テスト未実装や progress.md 未更新での「完了」報告は厳禁**

---

## テンプレート作成例

### 新機能開始時

```bash
# ディレクトリ作成
mkdir -p docs/features/new-feature

# plan.md 作成（テンプレート使用）
# 既存の features を参考にする
cat docs/features/like-and-wishlist/plan.md > docs/features/new-feature/plan.md

# 内容を新機能用に修正

# progress.md 作成
# 既存の features を参考にする
cat docs/features/like-and-wishlist/progress.md > docs/features/new-feature/progress.md

# 内容を新機能用に修正
```

---

## まとめ

### ファイル構成
- **2ファイルのみ**: plan.md, progress.md
- 細かいタスクファイルは不要

### plan.md
- 要件・設計・完了基準を記載
- 対象ページ・URL を全て列挙
- 不明点は推測せず質問

### progress.md
- タスク、課題、完了報告を集約
- 完了時には必ず記録
- 途中経過は柔軟に運用

### 既存 features を参考に
- `docs/features/like-and-wishlist/` などを参考
- テンプレートとして活用

**シンプルに、確実に、実装漏れなく。**
