# Serena セットアップガイド

Serena は AI コーディングアシスタント用の MCP (Model Context Protocol) サーバーです。プロジェクトのセマンティック解析と効率的なコードナビゲーションを提供します。

## 概要

Serena を使用することで、Claude Code などの AI アシスタントが以下の機能を利用できるようになります：

- **セマンティック検索**: 関数・クラス・変数の定義と参照をすばやく検索
- **コードナビゲーション**: シンボルベースの効率的なコード移動
- **スマートリファクタリング**: 構造を理解した安全なコード変更
- **プロジェクト全体の理解**: ファイル間の関係性把握

## セットアップ手順

### 1. Serena のインストール

```bash
# uv がインストールされていることを確認
uv --version
```

### 2. Claude Code への MCP サーバー追加

```bash
# プロジェクトディレクトリで実行
claude mcp add serena-mcp-server "uvx --from git+https://github.com/oraios/serena serena-mcp-server --context ide-assistant --project $(pwd)"
```

### 3. プロジェクトの初期化・動作確認

```bash
# プロジェクトディレクトリで実行
uvx --from git+https://github.com/oraios/serena serena project health-check
```

このコマンドで以下が自動実行されます：
- プロジェクトの Serena への登録
- プロジェクトのアクティベート
- 言語サーバー（PHP: Intelephense）の起動
- シンボルインデックスの作成
- 全ツールの動作確認

### 4. セットアップ完了確認

health-check が成功すると以下のメッセージが表示されます：

```
✅ Health check passed - All tools working correctly
```

## Serena のコマンド一覧

### プロジェクト管理 (`serena project`)

| コマンド | 説明 |
|---------|------|
| `health-check` | プロジェクトの動作確認・セットアップ |
| `index` | プロジェクトのシンボルインデックス作成 |
| `index-file <file>` | 単一ファイルのインデックス作成 |
| `generate-yml` | プロジェクト設定ファイル生成 |
| `is_ignored_path <path>` | パスが除外対象かチェック |

### 設定管理 (`serena config`)

| コマンド | 説明 |
|---------|------|
| `edit` | 設定ファイル (`serena_config.yml`) を編集 |

### ツール管理 (`serena tools`)

| コマンド | 説明 |
|---------|------|
| `list` | 利用可能なツール一覧表示 |
| `description <tool>` | 特定ツールの説明表示 |

### その他

| コマンド | 説明 |
|---------|------|
| `print-system-prompt` | プロジェクト用システムプロンプト表示 |
| `start-mcp-server` | MCPサーバー起動 |

## 生成されるファイル・ディレクトリ

```
.serena/
├── cache/
│   └── php/
│       └── document_symbols_cache_*.pkl  # シンボルキャッシュ
└── logs/
    └── health-checks/
        └── health_check_*.log            # ヘルスチェックログ
```

## トラブルシューティング

### Health Check が失敗する場合

1. **uv がインストールされているか確認**
   ```bash
   uv --version
   ```

2. **プロジェクトディレクトリで実行しているか確認**
   ```bash
   pwd  # プロジェクトルートにいることを確認
   ```

3. **ログファイルを確認**
   ```bash
   cat .serena/logs/health-checks/health_check_*.log
   ```

### 言語サーバーエラーが発生する場合

PHP プロジェクトの場合、Intelephense が自動的に使用されます。大きなファイルに関する警告は正常な動作です：

```
file:///.../autoload_classmap.php is over the maximum file size of 1000000 bytes.
```

### キャッシュをクリアしたい場合

```bash
rm -rf .serena/cache
uvx --from git+https://github.com/oraios/serena serena project health-check
```

## よく使うワークフロー

### 1. 初回セットアップ
```bash
claude mcp add serena-mcp-server "uvx --from git+https://github.com/oraios/serena serena-mcp-server --context ide-assistant --project $(pwd)"
uvx --from git+https://github.com/oraios/serena serena project health-check
```

### 2. プロジェクト構造の確認
```bash
uvx --from git+https://github.com/oraios/serena serena tools list
```

### 3. 設定のカスタマイズ
```bash
uvx --from git+https://github.com/oraios/serena serena config edit
```

## 参考リンク

- [Serena 公式リポジトリ](https://github.com/oraios/serena)
- [Serena README](https://github.com/oraios/serena/blob/main/README.md)
- [Claude Code ドキュメント](https://docs.anthropic.com/en/docs/claude-code)

## プロジェクトでの Serena 利用状況

### セットアップ完了日
2025-08-20

### 対象プロジェクト
- **プロジェクト名**: maji-kichi-meshi
- **言語**: PHP (Laravel), TypeScript (Nuxt.js)
- **ファイル数**: 145ファイル
- **検出シンボル数**: 290個

### 利用可能な機能
- ✅ セマンティック検索
- ✅ シンボル検索・参照検索
- ✅ パターン検索 (499マッチ検出)
- ✅ プロジェクト全体インデックス
- ✅ Laravel/Nuxt.js 構造理解