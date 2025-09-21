# 画像遅延生成機能 本番デプロイメントチェックリスト

**作成日**: 2025-09-22
**目的**: 本番デプロイ時の必須確認項目（忘れると機能しない重要事項を含む）

## 🚨 最重要事項（これを忘れると遅延生成が動作しません）

### ⚠️ largeディレクトリのリネーム（絶対に忘れない）

```bash
# 本番環境ではlargeディレクトリが存在し、originalは存在しない
# largeをoriginalにリネームする必要がある（超重要）

cd /var/www/maji-kichi-backend/storage/app/public/images/
sudo mv shops/large shops/original      # ← これ忘れると動かない！
sudo mv reviews/large reviews/original   # ← これ忘れると動かない！

# 権限確認
sudo chown -R www-data:www-data shops/original reviews/original
```

**なぜ必要？**
- 新システムは`original`ディレクトリから各サイズを生成
- 本番環境には`large`しか存在しない
- リネームしないと元画像が見つからずエラーになる

---

## 📋 デプロイ前チェックリスト

### 1. ローカル環境での確認
- [ ] パターンAテスト（完全シミュレーション）実施済み
- [ ] ロールバック→再マイグレーションのテスト成功
- [ ] データ移行コマンドの冪等性確認（3回実行してエラーなし）

### 2. バックアップ準備
- [ ] データベースバックアップコマンド準備
- [ ] 画像ファイルバックアップコマンド準備
- [ ] バックアップ保存先の空き容量確認

---

## 🚀 デプロイ実行チェックリスト

### Phase 1: 事前準備
```bash
# SSHログイン
ssh sakura-vps

# バックアップ作成
mysqldump -u maji_kichi_user -p maji_kichi_meshi > ~/backup_db_$(date +%Y%m%d_%H%M%S).sql
tar -czf ~/backup_images_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/maji-kichi-backend/storage/app/public/images/
```
- [ ] SSHログイン成功
- [ ] データベースバックアップ作成
- [ ] 画像ファイルバックアップ作成
- [ ] バックアップファイル名記録: ________________

### Phase 2: 必須リネーム作業（超重要）
```bash
cd /var/www/maji-kichi-backend/storage/app/public/images/

# 現在の状態確認
ls -la shops/     # large, thumbnail が存在することを確認
ls -la reviews/   # large, thumbnail が存在することを確認

# リネーム実行
sudo mv shops/large shops/original
sudo mv reviews/large reviews/original

# 結果確認
ls -la shops/     # original, thumbnail になっていることを確認
ls -la reviews/   # original, thumbnail になっていることを確認

# 権限設定
sudo chown -R www-data:www-data shops/original reviews/original
```
- [ ] shops/large → shops/original リネーム完了
- [ ] reviews/large → reviews/original リネーム完了
- [ ] 権限設定完了

### Phase 3: コードデプロイ
```bash
# デプロイディレクトリへ移動
cd ~/deployment/maji-kichi-meshi/

# 最新コード取得
git pull origin main

# デプロイスクリプト実行
./deploy.sh
```
- [ ] git pull成功
- [ ] deploy.sh実行成功（マイグレーション自動実行）

### Phase 4: データ移行
```bash
cd /var/www/maji-kichi-backend

# データ移行コマンド実行
sudo -u www-data php artisan shop-images:migrate-data
sudo -u www-data php artisan images:fix-uuid
```
- [ ] shop-images:migrate-data 成功（件数: _____）
- [ ] images:fix-uuid 成功（件数: _____）

---

## ✅ デプロイ後確認チェックリスト

### 1. API動作確認
```bash
# レビューAPI（画像URLs確認）
curl https://maji-kichi-meshi.takemitsu.net/api/reviews/1 | jq '.data.images'

# 画像配信API（遅延生成）
curl -I https://maji-kichi-meshi.takemitsu.net/api/images/reviews/small/[filename]
```
- [ ] レビューAPIでurls配列が返る
- [ ] 画像配信APIで200 OKが返る

### 2. フロントエンド確認
- [ ] 店舗詳細ページで画像表示
- [ ] レビュー一覧で画像表示
- [ ] 新規レビュー投稿で画像アップロード

### 3. ファイル生成確認
```bash
cd /var/www/maji-kichi-backend/storage/app/public/images/

# 初回アクセス後、新しいサイズが生成されているか確認
ls -la reviews/small/    # 遅延生成されたファイル
ls -la reviews/medium/   # 遅延生成されたファイル
```
- [ ] small/mediumディレクトリにファイル生成確認

### 4. エラーログ確認
```bash
tail -f /var/www/maji-kichi-backend/storage/logs/laravel-$(date +%Y-%m-%d).log
```
- [ ] エラーなし、または想定内のエラーのみ

---

## 🔄 ロールバック手順（必要な場合）

### 1. マイグレーションロールバック
```bash
cd /var/www/maji-kichi-backend
sudo -u www-data php artisan migrate:rollback --step=3
```

### 2. ディレクトリ名を元に戻す
```bash
cd /var/www/maji-kichi-backend/storage/app/public/images/
sudo mv shops/original shops/large
sudo mv reviews/original reviews/large
```

### 3. データベースリストア（必要な場合）
```bash
mysql -u maji_kichi_user -p maji_kichi_meshi < ~/backup_db_[timestamp].sql
```

---

## 📝 デプロイ記録

| 項目 | 内容 |
|------|------|
| デプロイ日時 | |
| 実行者 | |
| バックアップファイル | DB: ____________ <br> Images: ____________ |
| マイグレーション結果 | |
| データ移行件数 | ShopImage: _____ <br> ReviewImage: _____ |
| 問題発生 | なし / あり（詳細：___________） |
| ロールバック実施 | なし / あり（理由：___________） |

---

## ⚠️ よくある失敗と対処法

### 1. 「original画像が見つかりません」エラー
**原因**: largeディレクトリのリネームを忘れた
**対処**:
```bash
cd /var/www/maji-kichi-backend/storage/app/public/images/
sudo mv shops/large shops/original
sudo mv reviews/large reviews/original
```

### 2. データ移行コマンドが0件処理
**原因**: すでに移行済み（冪等性により正常）
**対処**: 問題なし、次に進む

### 3. 権限エラー
**原因**: www-dataユーザーの権限不足
**対処**:
```bash
sudo chown -R www-data:www-data /var/www/maji-kichi-backend/storage/
```

---

**重要**: このチェックリストを印刷するか、別画面で開いてチェックしながら作業すること。
特に「largeディレクトリのリネーム」は絶対に忘れないこと！