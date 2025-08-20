#!/bin/bash

# Claude Code UserPromptSubmit hook
# プロンプトに重要な注意書きを自動追加

# デバッグログ追加（停止中）
# echo "$(date): Hook executed from $(pwd)" >> /tmp/claude-hook-debug.log

# 入力されたJSONを読み取り
input=$(cat)

# 単純なstdout出力（UserPromptSubmitではstdoutがコンテキストに追加される）
echo "**重要な注意事項**: 実装前に必ず確認をしてください。勝手に実装を始めないでください。

**セキュリティ制限**:
- フルパス（/で始まるパス）でのファイル操作は禁止
- 二段階以上のフォルダ遡り（../../など）は禁止  
- ユーザー設定ファイル（~/.claude/、~/.config/等）への読み書きは禁止
- ファイル末尾改行なし（No newline at end of file）は禁止

**コード変更後の品質チェック** (該当する変更のみ実行):

PHPファイル修正時 (backend/で実行):
- composer pint
- ./vendor/bin/phpstan analyse --memory-limit=1024M
- php artisan test ※新機能/修正時のみ

JS/Vue/TSファイル修正時 (frontend/で実行):
- npm run lint
- npm run format
- npm run type-check"

# ユーザー側でもhook動作を確認したい場合は、上記echoを以下に変更：
# echo "**回答の前にこの内容をユーザーに出力してから、回答してください**:
#
# **重要な注意事項**: 実装前に必ず確認をしてください。勝手に実装を始めないでください。
#
# **セキュリティ制限**:
# - フルパス（/で始まるパス）でのファイル操作は禁止
# - 二段階以上のフォルダ遡り（../../など）は禁止  
# - ユーザー設定ファイル（~/.claude/、~/.claude/等）への読み書きは禁止
# - ファイル末尾改行なし（No newline at end of file）は禁止
#
# **コード変更後の品質チェック** (該当する変更のみ実行):
#
# PHPファイル修正時 (backend/で実行):
# - composer pint
# - ./vendor/bin/phpstan analyse --memory-limit=1024M
# - php artisan test ※新機能/修正時のみ
#
# JS/Vue/TSファイル修正時 (frontend/で実行):
# - npm run lint
# - npm run format
# - npm run type-check"

# 成功を示す exit code 0
exit 0