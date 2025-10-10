# プロジェクトドキュメント

マジキチメシプロジェクトの設計・開発に関するドキュメント集です。

## 📚 ドキュメントカテゴリ

### 設計・仕様書
プロジェクトの基本設計と技術仕様

- **[concept.md](./concept.md)** - プロジェクト概要・コンセプト・ターゲットユーザー
- **[concept-memo.md](./concept-memo.md)** - 「マジキチ」コンセプトの詳細解説
- **[technical-specs.md](./technical-specs.md)** - 技術仕様・API仕様・実装状況（全9フェーズ完了）
- **[database-er-diagram.md](./database-er-diagram.md)** - データベース設計・ER図・全9テーブル
- **[architecture-decision.md](./architecture-decision.md)** - SPA + API アーキテクチャ選択理由

### 開発ガイド
開発プロセスと設計判断の記録

- **[development-workflow.md](./development-workflow.md)** - 機能開発の標準ワークフロー（`docs/features/`使用）
- **[database-design-decisions.md](./database-design-decisions.md)** - データベース設計判断の記録
- **[session-log.md](./session-log.md)** - 開発セッションの作業記録
- **[future-development-plan.md](./future-development-plan.md)** - 将来の開発計画・TODO

### デプロイメントガイド
本番環境へのデプロイメント手順

- **[20250729-production-deployment-guide.md](./20250729-production-deployment-guide.md)** - 本番デプロイメント完全ガイド
- **[deployment-frontend-guide.md](./deployment-frontend-guide.md)** - フロントエンド静的配信設定

### アーカイブ
完了済みの開発記録・レポート類

- **[archive/](./archive/)** - 過去の開発セッション記録・タスク完了報告

### ファイル命名規則

#### 時刻付きファイルの命名規則
時刻付きファイル（reports, messages, tasks等）は以下の形式で命名する：

```
YYYYmmddHHMMSS-[type]-[description].md
```

**対象ファイルタイプ：**
- `message` - チーム間メッセージ
- `report` - レビュー・テスト結果等の報告書
- `task` - タスク・対応事項リスト
- `review` - コードレビュー・評価結果

**タイムゾーン：** JST (日本標準時)

**例：**
```
20250709110400-report-sync-completion.md
20250709105500-message-to-frontend.md
20250709103000-task-auth-implementation.md
20250709102000-review-backend-code.md
```

#### 固定ファイル
以下のファイルは命名規則対象外（固定名）：
- `README.md`, `CLAUDE.md`
- `concept.md`, `technical-specs.md`, `database-er-diagram.md`
- その他の設計書・仕様書類

## 🎯 プロジェクト状況

**100%完了** - 本番デプロイ済み（全9フェーズ完了）

### 完了機能
- ✅ 認証システム (OAuth + JWT)
- ✅ コア機能 (店舗・レビュー・ランキング)
- ✅ フロントエンド (Vue/Nuxt SPA + モバイル対応)
- ✅ 画像機能 (アップロード・自動リサイズ・検閲)
- ✅ 管理システム (Laravel Filament)
- ✅ 統計機能 (ダッシュボード・分析API)
- ✅ テストカバレッジ (98%成功率)

詳細: `technical-specs.md`

### 次期拡張候補
- Google Places API 連携
- 通報システム実装
- パフォーマンス最適化
- デプロイメント自動化

## 参考

プロジェクトルートの [CLAUDE.md](../CLAUDE.md) には、Claude Code用の開発ガイダンスが記載されています。