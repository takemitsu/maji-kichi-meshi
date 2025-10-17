# プロジェクトドキュメント

マジキチメシプロジェクトの設計・開発に関するドキュメント集です。

## 📚 ドキュメントカテゴリ

### 設計・仕様書
プロジェクトの基本設計と技術仕様

- **[concept.md](./concept.md)** - プロジェクト概要・コンセプト・ターゲットユーザー・「マジキチ」な価値
- **[technical-specs.md](./technical-specs.md)** - 技術仕様・API仕様・実装状況（全9フェーズ完了）
- **[database-er-diagram.md](./database-er-diagram.md)** - データベース設計・ER図・全9テーブル
- **[architecture-decision.md](./architecture-decision.md)** - SPA + API アーキテクチャ選択理由

### 開発ガイド
開発プロセスと設計判断の記録

- **[database-design-decisions.md](./database-design-decisions.md)** - データベース設計判断の記録
- **[future-development-plan.md](./future-development-plan.md)** - 将来の開発計画・TODO
- **[serena-setup-guide.md](./serena-setup-guide.md)** - 開発環境セットアップ（Serena）

### デプロイメントガイド
本番環境へのデプロイメント手順

- **[deployment-guide.md](./deployment-guide.md)** - 本番デプロイメント完全ガイド（deploy.sh使用）
  - 自動デプロイスクリプトの使い方
  - 手動デプロイ手順
  - Filament管理画面設定
  - トラブルシューティング

### 進行中の機能開発

`docs/features/` ディレクトリには進行中の機能開発ドキュメントが格納されています：

- **[like-and-wishlist/](./features/like-and-wishlist/)** - いいね・ウィッシュリスト機能（Phase 1完了）
- **[test-coverage-improvement/](./features/test-coverage-improvement/)** - テストカバレッジ改善

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