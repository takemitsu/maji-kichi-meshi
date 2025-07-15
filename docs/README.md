# プロジェクトドキュメント

このディレクトリには、マジキチメシプロジェクトの設計・開発に関するドキュメントが格納されています。

## ドキュメント構成

### 設計・仕様書
- **[concept.md](./concept.md)** - プロジェクト概要とコンセプト
- **[concept-memo.md](./concept-memo.md)** - 「マジキチ」コンセプトの詳細解説
- **[technical-specs.md](./technical-specs.md)** - 技術仕様書・実装済み機能
- **[database-er-diagram.md](./database-er-diagram.md)** - データベース設計・ER図
- **[architecture-decision.md](./architecture-decision.md)** - アーキテクチャ選択の理由

### 開発記録・設計判断
- **[session-log.md](./session-log.md)** - 開発セッションの作業記録
- **[database-design-decisions.md](./database-design-decisions.md)** - データベース設計判断の記録
- **[future-development-plan.md](./future-development-plan.md)** - 将来の開発計画・TODO

### デプロイメント・設定ガイド
- **[deployment-frontend-guide.md](./deployment-frontend-guide.md)** - フロントエンド静的配信設定
- **[oauth-setup-guide.md](./oauth-setup-guide.md)** - OAuth認証設定ガイド

### アーカイブ
- **[archive/](./archive/)** - 完了済みの開発記録・レポート類

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

## プロジェクト状況

### 🎯 プロジェクト完了状況: **100%** (管理機能含む完全版)
**OAuth設定完了後、即座に本番リリース可能 + 管理者機能完備**

全8フェーズ完了済み。詳細な完了機能一覧・テスト結果は `technical-specs.md` を参照。

### 主要完了機能
- ✅ **認証システム**: OAuth (Google/GitHub/LINE/Twitter) + JWT
- ✅ **コア機能**: 店舗管理・レビュー・ランキング
- ✅ **フロントエンド**: Vue/Nuxt SPA + モバイル対応
- ✅ **画像機能**: アップロード・自動リサイズ・検閲
- ✅ **管理システム**: Laravel Filament完全実装
- ✅ **統計機能**: ダッシュボード・分析API
- ✅ **テスト**: 包括的テストカバレッジ (98%成功率)

### 次期拡張候補
- [ ] Google Places API 連携
- [ ] 通報システム実装  
- [ ] パフォーマンス最適化
- [ ] デプロイメント自動化

## 参考

プロジェクトルートの [CLAUDE.md](../CLAUDE.md) には、Claude Code用の開発ガイダンスが記載されています。