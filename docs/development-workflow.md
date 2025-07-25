# 機能開発の標準ワークフロー

マジキチメシプロジェクトにおける新機能開発の標準的な進め方を定義します。

## 機能開発ディレクトリ構成

新機能の開発は `docs/features/` ディレクトリを使用してタスク管理を行います。

```
docs/features/
├── feature-name/
│   ├── README.md           # 機能概要・仕様・要件定義
│   ├── 01-task-name.md     # 個別タスク（連番）
│   ├── 02-task-name.md     # 個別タスク
│   ├── 03-task-name.md     # 個別タスク
│   └── progress.md         # 進捗状況・完了記録
└── another-feature/
    ├── README.md
    └── ...
```

### ファイル構成の詳細

#### `README.md` - 機能概要
- **目的**: 機能の概要・仕様・要件を記載
- **内容**:
  - 機能の目的・背景
  - 主要な要件・仕様
  - 技術的制約・考慮事項
  - 完了基準
  - 関連する既存機能・影響範囲

#### `XX-task-name.md` - 個別タスク
- **命名**: 2桁連番 + タスク名（例: `01-user-profile-api.md`）
- **内容**:
  - タスクの詳細説明
  - 実装すべき機能・API
  - テスト要件
  - 注意点・参考情報

#### `progress.md` - 進捗管理
- **目的**: 全体進捗・完了状況の記録
- **内容**:
  - タスク一覧とステータス
  - 完了日時の記録
  - 発見した課題・対応方法
  - 次フェーズへの引き継ぎ事項

## 開発フロー

### 1. 機能企画・要件定義
```bash
# 新機能ディレクトリ作成
mkdir docs/features/user-profile-pages

# 機能概要作成
touch docs/features/user-profile-pages/README.md
```

### 2. タスク分解・計画
- `README.md` に機能仕様を記載
- 個別タスクに分解（`01-*.md`, `02-*.md`...）
- `progress.md` でタスク一覧・進捗管理開始

### 3. 開発実行
- タスク順序に従って順次実装
- 各タスク完了時に `progress.md` を更新
- コードレビュー・テスト実施

### 4. 完了・統合
- 全タスク完了後、機能テスト実施
- `progress.md` に最終結果・引き継ぎ事項記録
- 必要に応じて `technical-specs.md` に機能追加

## 具体例: ユーザープロフィールページ機能

```
docs/features/user-profile-pages/
├── README.md                    # 機能概要・要件
├── 01-user-profile-api.md       # バックエンドAPI実装
├── 02-frontend-pages.md         # フロントエンドページ作成
├── 03-integration-test.md       # 統合テスト・動作確認
└── progress.md                  # 進捗管理
```

### README.md の例
```markdown
# ユーザープロフィールページ機能

## 概要
他のユーザーのプロフィール・レビュー・ランキングを閲覧できる機能

## 要件
- `/users/{user_id}` でプロフィール表示
- `/users/{user_id}/reviews` でレビュー一覧
- `/users/{user_id}/rankings` でランキング一覧

## 技術要件
- Laravel API追加
- Vue.js ページ追加  
- 認証不要（公開情報のみ）

## 完了基準
- [ ] API実装・テスト完了
- [ ] フロントエンド実装完了
- [ ] 統合テスト・動作確認完了
```

## ツール・コマンド

### 進捗確認
```bash
# 全機能の進捗確認
find docs/features -name "progress.md" -exec echo "=== {} ===" \; -exec cat {} \;

# 特定機能の詳細確認
cat docs/features/user-profile-pages/README.md
```

### テンプレート作成
```bash
# 新機能用ディレクトリ・ファイル一括作成例
mkdir docs/features/new-feature
touch docs/features/new-feature/{README.md,progress.md}
```

## 注意事項

### ファイル管理
- **固定ファイル**: `README.md`, `progress.md`
- **可変ファイル**: `XX-*.md` (タスク内容に応じて追加・削除)
- **アーカイブ**: 完了した機能は `docs/features/` に残し、参考資料として活用

### 品質管理
- 各タスクには必ずテスト要件を含める
- コードレビューの実施を前提とする
- 既存機能への影響を必ず検討・記録する

### メンテナンス
- 月1回程度、完了済み機能の整理・アーカイブを検討
- 開発フロー自体の改善提案も `progress.md` に記録

---

このワークフローに従って機能開発を進めることで、**一貫性のある開発プロセス**と**適切なドキュメント管理**を実現します。