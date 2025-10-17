---
name: ファイルパス解決ルール
description: ファイル・ディレクトリ操作時のパス解決規則。ファイル作成、docs更新、移動、mkdir、ディレクトリ操作時に使用。
---

# ファイルパス解決ルール

## ⚠️ 絶対に守ること

### プロジェクト構造の理解

```
/Users/takemitsusuzuki/work/personal/maji-kichi-meshi/  ← プロジェクトルート
├── backend/           ← Laravel API
├── frontend/          ← Vue/Nuxt SPA
├── docs/              ← ドキュメント（プロジェクトルート直下のみ！）
├── CLAUDE.md
└── .claude/
    └── skills/
```

### docs/ の扱い

- `docs/` は**プロジェクトルート直下にのみ存在**
- `backend/docs/` や `frontend/docs/` は**絶対に作成禁止**
- これらは**ゴミディレクトリ**であり、作成したら即座に削除する

---

## 必須確認手順

### ルール1: docs/ パス指定時は必ず pwd 確認

ユーザーが `docs/` で始まるパスを指定したら:

```bash
# ステップ1: 現在位置を確認
pwd

# ステップ2: 現在位置に応じた対応
# もし /Users/.../maji-kichi-meshi/backend にいたら:
#   → プロジェクトルートに移動 or フルパス使用

# もし /Users/.../maji-kichi-meshi/frontend にいたら:
#   → プロジェクトルートに移動 or フルパス使用

# もし /Users/.../maji-kichi-meshi にいたら:
#   → そのまま相対パスで操作可能
```

### ❌ 間違った例

```bash
# backend/ にいる状態で:
cd /Users/takemitsusuzuki/work/personal/maji-kichi-meshi/backend

# ユーザー: "docs/features/xxx の plan.md を更新して"
mkdir -p docs/features/xxx  # ← backend/docs/ ができてしまう！
```

### ✅ 正しい例

```bash
# backend/ にいる状態で:
pwd  # まず確認
# → /Users/.../maji-kichi-meshi/backend

# パターンA: プロジェクトルートに移動
cd /Users/takemitsusuzuki/work/personal/maji-kichi-meshi
mkdir -p docs/features/xxx

# パターンB: フルパスで操作（移動不要）
mkdir -p /Users/takemitsusuzuki/work/personal/maji-kichi-meshi/docs/features/xxx
```

---

## ルール2: フルパス指定時はフルパスで操作

ユーザーがフルパスを指定したら、**全ての操作をフルパスで実行**する。

### ❌ 間違った例

```bash
# ユーザー: "/Users/takemitsusuzuki/work/personal/maji-kichi-meshi/docs/features/xxx の plan.md を更新"

# 間違い: 相対パスに変換してしまう
ls docs/features/xxx/  # ← カレントディレクトリ基準になる
```

### ✅ 正しい例

```bash
# ユーザー: "/Users/takemitsusuzuki/work/personal/maji-kichi-meshi/docs/features/xxx の plan.md を更新"

# 正しい: フルパスのまま操作
ls /Users/takemitsusuzuki/work/personal/maji-kichi-meshi/docs/features/xxx/
```

---

## ルール3: ディレクトリ作成前の確認

新しいディレクトリを作成する前に、必ず以下を確認:

### 確認手順

```bash
# 1. フルパスで存在確認
ls /Users/takemitsusuzuki/work/personal/maji-kichi-meshi/docs/features/xxx/ 2>/dev/null

# 2. 存在しない場合
if [ $? -ne 0 ]; then
    echo "ディレクトリが存在しません。作成してもよろしいですか？"
    # ユーザーの承認を待つ
fi

# 3. 承認後に作成
mkdir -p /Users/takemitsusuzuki/work/personal/maji-kichi-meshi/docs/features/xxx
```

### 特に注意が必要なケース

- Glob ツールで見つからなかった場合
  - → すぐに mkdir しない
  - → フルパスで ls コマンドで再確認
  - → 本当に存在しないか確認してから作成

---

## ルール4: ゴミディレクトリの検出と削除

もし誤って以下を作成してしまったら、**即座に削除**:

```bash
# ゴミディレクトリの例
backend/docs/
frontend/docs/

# 削除コマンド
rm -rf backend/docs
rm -rf frontend/docs

# ユーザーに報告
echo "誤って backend/docs/ を作成してしまいましたが、削除しました。"
```

---

## よくある間違いパターン

### パターン1: カレントディレクトリを意識しない

```bash
# 現在: backend/
# ユーザー: "docs/features/xxx を作成"

# ❌ 間違い
mkdir -p docs/features/xxx  # → backend/docs/features/xxx ができる

# ✅ 正しい
cd /Users/takemitsusuzuki/work/personal/maji-kichi-meshi
mkdir -p docs/features/xxx
```

### パターン2: Glob で見つからない → すぐ mkdir

```bash
# Glob で見つからなかった

# ❌ 間違い
mkdir -p docs/features/xxx  # カレントディレクトリ基準で作ってしまう

# ✅ 正しい
pwd  # まず現在位置確認
ls /Users/takemitsusuzuki/work/personal/maji-kichi-meshi/docs/features/xxx/ 2>/dev/null
# 存在確認してから作成
```

### パターン3: フルパスを相対パスに変換

```bash
# ユーザー指定: /Users/.../maji-kichi-meshi/docs/features/xxx

# ❌ 間違い
# "相対パスの docs/features/xxx だな" と解釈してしまう

# ✅ 正しい
# フルパスのまま操作する
```

---

## 実践チェックリスト

docs/ 関連の操作をする前に、以下を確認:

- [ ] `pwd` で現在位置を確認したか？
- [ ] backend/ または frontend/ にいる場合、プロジェクトルートへの移動 or フルパス使用を検討したか？
- [ ] ユーザーがフルパスを指定した場合、フルパスのまま操作しているか？
- [ ] ディレクトリ作成前に、存在確認を行ったか？
- [ ] Glob で見つからなかった場合、ls で再確認したか？

---

## まとめ

### 原則

1. **不明な場合は `pwd` で現在位置確認**
2. **docs/ 操作時は必ずプロジェクトルートを意識**
3. **フルパス指定時はフルパスで操作**
4. **ゴミディレクトリを作らない**
5. **作成してしまったら即座に削除して報告**

### プロジェクトルートの絶対パス（暗記推奨）

```
/Users/takemitsusuzuki/work/personal/maji-kichi-meshi/
```

このパスを常に意識して、docs/ は必ずこの直下にあることを確認する。
