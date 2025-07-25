# Progress.md チェックスクリプト

progress.mdの更新状況を自動チェックするための仕組み

## 🔍 手動チェックコマンド

### 全機能の進捗確認
```bash
# 全progress.mdファイルの存在確認
find docs/features -name "progress.md" -exec echo "=== {} ===" \; -exec cat {} \;

# 未更新のprogress.mdを検出
find docs/features -name "progress.md" -exec grep -L "✅ 完了\|🔄 実行中" {} \;
```

### 特定機能の詳細チェック
```bash
# 特定機能のprogress.md確認
cat docs/features/user-filter/progress.md

# 完了基準チェック（✅の数を数える）
grep -c "✅" docs/features/user-filter/progress.md
```

## 🚨 チェック項目

### 必須項目チェック
1. **progress.mdファイルの存在**
2. **タスク進捗テーブルの存在**  
3. **ステータス記号の使用** (⏳/🔄/✅/❌)
4. **完了日時の記録**（✅完了のタスクに対して）
5. **実装記録セクションの存在**

### 品質チェック
1. **全タスクのステータス更新**
2. **完了基準チェックリスト埋め込み**
3. **課題・対応方法の記録**
4. **引き継ぎ事項の記録**

## ⚠️ 検出すべき問題パターン

### 重大な問題
- progress.mdファイル自体が存在しない
- 全タスクが「⏳ 未着手」のまま（実装完了しているのに）
- 完了タスクに完了日時がない
- 実装記録が空

### 軽微な問題  
- 課題記録が不十分
- 引き継ぎ事項の記載なし
- ステータス記号の不統一

## 🤖 将来的な自動化案

### Git Hookでの自動チェック
```bash
# pre-commit hookでprogress.md更新をチェック
#!/bin/bash
if [ -f "docs/features/*/progress.md" ]; then
    # progress.mdの更新確認ロジック
    echo "progress.md更新を確認中..."
fi
```

### CI/CDでの自動レビュー
- GitHub Actionsでprogress.md更新チェック
- プルリクエスト時の自動コメント
- 未更新の場合は警告表示

## 📋 レビュアー用チェックリスト

コピペ用のチェックリスト：

```markdown
### 📋 progress.md確認チェックリスト

- [ ] progress.mdファイルが存在する
- [ ] 全タスクのステータスが最新（未着手/実行中/完了）
- [ ] 完了タスクに完了日時が記録されている  
- [ ] 実装記録セクションに作業ログがある
- [ ] 課題・対応方法が記録されている
- [ ] 完了基準チェックリストが埋められている
- [ ] 引き継ぎ事項が記録されている

❌ 上記のいずれかが不備の場合、レビュー不合格
```