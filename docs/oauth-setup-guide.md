# OAuth設定ガイド（LINE・Twitter・Google・GitHub）

## 📋 概要
マジキチメシアプリのOAuth認証設定手順書です。各プロバイダー（LINE、Twitter、Google、GitHub）のアプリ登録からClient ID・Secretの取得まで詳細に解説します。

## 🎯 前提条件
- 各サービスのアカウント（LINE、Twitter、Google、GitHub）
- 本番ドメイン（例：`takemitsu.net`）
- SSL証明書設定済み（HTTPS必須）

## 🚀 設定一覧

### 1. LINE Developers設定

#### 1-1. LINE Developersコンソールアクセス
1. [LINE Developers](https://developers.line.biz/ja/) にアクセス
2. LINEアカウントでログイン
3. 「プロバイダー」を作成（初回のみ）

#### 1-2. LINEログインチャネル作成
1. 「新しいチャネル」作成
2. **チャネル種類**: `LINEログイン` を選択
3. **チャネル名**: `マジキチメシ`
4. **チャネル説明**: `吉祥寺地域の店舗ランキング・レビューアプリ`
5. **アプリタイプ**: `ウェブアプリ`

#### 1-3. チャネル基本設定
```
チャネル名: マジキチメシ
チャネル説明: 吉祥寺地域の店舗ランキング・レビューアプリ
アプリタイプ: ウェブアプリ
```

#### 1-4. リダイレクトURL設定
1. チャネル設定 → 「LINEログイン設定」
2. **コールバックURL**に以下を設定:
```
https://takemitsu.net/auth/callback
https://www.takemitsu.net/auth/callback
```

#### 1-5. Client ID・Secret取得
1. チャネル設定 → 「チャネル基本設定」
2. **Channel ID** → これがClient ID
3. **Channel Secret** → これがClient Secret

```bash
# Laravel .env設定例
LINE_CLIENT_ID=1234567890
LINE_CLIENT_SECRET=abcdef1234567890abcdef1234567890
```

#### 1-6. 権限スコープ設定
1. 「LINEログイン設定」
2. **スコープ**: `profile` `openid` にチェック

---

### 2. Twitter API (X API) 設定

#### 2-1. Twitter Developer Portal アクセス
1. [Twitter Developer Portal](https://developer.x.com/) にアクセス
2. Twitterアカウントでログイン
3. 「Sign up for Free Account」でDeveloper登録

#### 2-2. アプリケーション作成
1. 「Create App」クリック
2. **App name**: `マジキチメシ`
3. **App description**: `吉祥寺地域の店舗ランキング・レビューアプリ`
4. **Website URL**: `https://takemitsu.net`（⚠️ 重要：twitter.com等は禁止）

#### 2-3. OAuth 2.0設定
1. App Settings → 「User authentication settings」
2. **OAuth 2.0**: `ON`
3. **Type of App**: `Web App`
4. **Callback URI / Redirect URL**:
```
https://takemitsu.net/auth/callback
```
5. **Website URL**: `https://takemitsu.net`

#### 2-4. Client ID・Secret取得
1. 「Keys and tokens」タブ
2. **OAuth 2.0 Client ID and Client Secret**セクション
3. **Client ID**: 表示されている値
4. **Client Secret**: 「Regenerate」で生成

```bash
# Laravel .env設定例
TWITTER_CLIENT_ID=VGhpc0lzQW5FeGFtcGxl
TWITTER_CLIENT_SECRET=VGhpc0lzQW5FeGFtcGxlU2VjcmV0S2V5
```

#### ⚠️ Twitter重要な注意事項
- **Website URL**は必ず自社ドメインを入力（twitter.com等は禁止）
- **2025年4月30日**以降、v1.1 media uploadが廃止予定
- **Freeプラン**：月1,500ツイート制限、1環境のみ

---

### 3. Google OAuth設定

#### 3-1. Google Cloud Console アクセス
1. [Google Cloud Console](https://console.cloud.google.com/) にアクセス
2. Googleアカウントでログイン
3. 「プロジェクトを作成」または既存プロジェクト選択

#### 3-2. OAuth同意画面設定
1. 「APIとサービス」→「OAuth同意画面」
2. **User Type**: `外部` を選択
3. **アプリ名**: `マジキチメシ`
4. **ユーザーサポートメール**: 自分のGmailアドレス
5. **承認済みドメイン**: `takemitsu.net`
6. **デベロッパーの連絡先情報**: 自分のメールアドレス

#### 3-3. 認証情報作成
1. 「認証情報」→「認証情報を作成」→「OAuth クライアント ID」
2. **アプリケーションの種類**: `ウェブ アプリケーション`
3. **名前**: `マジキチメシWebアプリ`
4. **承認済みのリダイレクト URI**:
```
https://takemitsu.net/auth/callback
https://www.takemitsu.net/auth/callback
```

#### 3-4. Client ID・Secret取得
作成完了後、ダウンロードまたは画面からコピー:

```bash
# Laravel .env設定例
GOOGLE_CLIENT_ID=123456789-abcdefg.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-abcdefghijklmnopqrstuvwx
```

---

### 4. GitHub OAuth設定

#### 4-1. GitHub Developer Settings アクセス
1. GitHubにログイン
2. Settings → Developer settings → OAuth Apps
3. 「New OAuth App」クリック

#### 4-2. OAuth App作成
```
Application name: マジキチメシ
Homepage URL: https://takemitsu.net
Application description: 吉祥寺地域の店舗ランキング・レビューアプリ
Authorization callback URL: https://takemitsu.net/auth/callback
```

#### 4-3. Client ID・Secret取得
作成完了後、アプリ詳細画面で確認:

```bash
# Laravel .env設定例
GITHUB_CLIENT_ID=Iv1.a629723bfa6a1234
GITHUB_CLIENT_SECRET=1234567890abcdef1234567890abcdef12345678
```

---

## 🔧 Laravel Backend設定

### config/services.php確認
```php
<?php

return [
    // Google OAuth
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/auth/google/callback',
    ],

    // GitHub OAuth
    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/auth/github/callback',
    ],

    // LINE OAuth
    'line' => [
        'client_id' => env('LINE_CLIENT_ID'),
        'client_secret' => env('LINE_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/auth/line/callback',
    ],

    // Twitter OAuth
    'twitter' => [
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/auth/twitter/callback',
    ],
];
```

### .env本番設定
```bash
# 本番環境 .env
APP_URL=https://takemitsu.net
FRONTEND_URL=https://takemitsu.net

# Google OAuth
GOOGLE_CLIENT_ID=123456789-abcdefg.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-abcdefghijklmnopqrstuvwx

# GitHub OAuth  
GITHUB_CLIENT_ID=Iv1.a629723bfa6a1234
GITHUB_CLIENT_SECRET=1234567890abcdef1234567890abcdef12345678

# LINE OAuth
LINE_CLIENT_ID=1234567890
LINE_CLIENT_SECRET=abcdef1234567890abcdef1234567890

# Twitter OAuth
TWITTER_CLIENT_ID=VGhpc0lzQW5FeGFtcGxl
TWITTER_CLIENT_SECRET=VGhpc0lzQW5FeGFtcGxlU2VjcmV0S2V5
```

---

## ✅ 動作確認手順

### 1. ローカル環境での事前確認
```bash
# フロントエンド確認
curl -I http://localhost:3000/login
# 期待: ログインページ表示

# バックエンドAPI確認  
curl http://localhost:8000/api/auth/google
# 期待: Google OAuth URLへリダイレクト
```

### 2. 本番環境での動作確認
各プロバイダーのテスト手順:

#### Google認証テスト
1. `https://takemitsu.net/login` にアクセス
2. 「Google でログイン」ボタンクリック
3. Google認証画面が表示されることを確認
4. 認証後、正しくコールバックされることを確認

#### GitHub認証テスト
1. 「GitHub でログイン」ボタンクリック
2. GitHub認証画面が表示されることを確認
3. 認証後、正しくコールバックされることを確認

#### LINE認証テスト
1. 「LINE でログイン」ボタンクリック
2. LINE認証画面が表示されることを確認
3. 認証後、正しくコールバックされることを確認

#### Twitter認証テスト
1. 「Twitter でログイン」ボタンクリック
2. Twitter認証画面が表示されることを確認
3. 認証後、正しくコールバックされることを確認

---

## 🚨 トラブルシューティング

### よくあるエラーと対処法

#### 1. `Invalid redirect_uri` エラー
**原因**: コールバックURLの設定ミス
**対処法**: 
- 各プロバイダーの設定でURL確認
- HTTPSプロトコル確認
- ドメイン名の正確性確認

#### 2. `Client ID not found` エラー
**原因**: Client IDの設定ミス
**対処法**: 
- .env設定値確認
- 設定キャッシュクリア: `php artisan config:clear`

#### 3. `Client Secret invalid` エラー
**原因**: Client Secretの設定ミス
**対処法**: 
- プロバイダー画面でSecret再生成
- .env設定更新

#### 4. CORS エラー
**原因**: フロントエンド・バックエンド間のドメイン不一致
**対処法**: 
- Laravel CORS設定確認
- nginx設定でCORSヘッダー確認

#### 5. LINE認証特有のエラー
**エラー**: `Invalid redirect_uri value`
**対処法**: LINE Developersでコールバック URL登録確認

#### 6. Twitter認証特有のエラー
**エラー**: Website URL registration warning
**対処法**: Website URLを必ず自社ドメインに設定

---

## 🔒 セキュリティ注意事項

### 1. Secret情報の管理
- **Client Secret**は絶対に公開リポジトリにコミットしない
- `.env`ファイルは`.gitignore`に必ず含める
- 本番環境では環境変数で管理

### 2. HTTPS必須
- OAuth認証はHTTPS環境でのみ動作
- SSL証明書の有効期限確認
- Mixed Content警告の回避

### 3. ドメイン制限
- 本番ドメインのみに制限
- ワイルドカードドメインは避ける
- 開発環境と本番環境の分離

### 4. 定期的なSecret更新
- 3-6ヶ月ごとのSecret更新推奨
- 漏洩発覚時の即座更新
- 更新時の全トークン無効化認識

---

## 📚 参考リンク

### 公式ドキュメント
- [LINE Developers - LINEログイン](https://developers.line.biz/ja/docs/line-login/)
- [Twitter Developer Platform](https://developer.x.com/)
- [Google Cloud Console - OAuth](https://console.cloud.google.com/)
- [GitHub Developer - OAuth Apps](https://github.com/settings/developers)

### Laravel Socialite
- [Laravel Socialite Documentation](https://laravel.com/docs/socialite)

---

**作成者**: フロントエンド担当Claude  
**作成日**: 2025-07-10  
**対象**: マジキチメシアプリ OAuth設定  
**環境**: 本番リリース用