# データベース設計 - ER図

## テーブル一覧

### ユーザー関連
- `users` - ユーザー情報
- `oauth_providers` - OAuth連携情報

### 店舗関連
- `shops` - 店舗情報
- `categories` - カテゴリマスタ
- `shop_categories` - 店舗カテゴリ中間テーブル

### レビュー関連
- `reviews` - 店舗レビュー
- `review_images` - レビュー画像

### ランキング関連
- `rankings` - ユーザー別ランキング

## ER図

```mermaid
erDiagram
    users ||--o{ oauth_providers : "1対多"
    users ||--o{ reviews : "1対多"
    users ||--o{ rankings : "1対多"
    
    shops ||--o{ reviews : "1対多"
    shops ||--o{ rankings : "1対多"
    shops ||--o{ shop_categories : "1対多"
    
    categories ||--o{ shop_categories : "1対多"
    categories ||--o{ rankings : "1対多"
    
    reviews ||--o{ review_images : "1対多"
    
    users {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        timestamp created_at
        timestamp updated_at
    }
    
    oauth_providers {
        bigint id PK
        bigint user_id FK
        string provider
        string provider_id
        string provider_token
        timestamp created_at
        timestamp updated_at
    }
    
    shops {
        bigint id PK
        string name
        text description
        string address
        decimal latitude
        decimal longitude
        string phone
        string website
        string google_place_id UK
        boolean is_closed
        timestamp created_at
        timestamp updated_at
    }
    
    categories {
        bigint id PK
        string name UK
        string slug UK
        string type
        timestamp created_at
        timestamp updated_at
    }
    
    shop_categories {
        bigint id PK
        bigint shop_id FK
        bigint category_id FK
        timestamp created_at
        timestamp updated_at
    }
    
    reviews {
        bigint id PK
        bigint user_id FK
        bigint shop_id FK
        int rating
        string repeat_intention
        text memo
        date visited_at
        timestamp created_at
        timestamp updated_at
    }
    
    review_images {
        bigint id PK
        bigint review_id FK
        string original_path
        string large_path
        string medium_path
        string thumbnail_path
        timestamp created_at
        timestamp updated_at
    }
    
    rankings {
        bigint id PK
        bigint user_id FK
        bigint shop_id FK
        bigint category_id FK
        int rank_position
        timestamp created_at
        timestamp updated_at
    }
```

## テーブル詳細

### categories テーブルの初期データ
```sql
-- 基本カテゴリ
INSERT INTO categories (name, slug, type) VALUES
('ラーメン', 'ramen', 'basic'),
('定食・食堂', 'teishoku', 'basic'),
('居酒屋・バー', 'izakaya', 'basic'),
('カフェ・喫茶店', 'cafe', 'basic'),
('ファストフード', 'fastfood', 'basic'),
('その他', 'others', 'basic'),

-- 時間帯タグ
('ランチ営業', 'lunch', 'time'),
('深夜営業', 'late-night', 'time'),
('朝営業', 'morning', 'time'),

-- 特別カテゴリ（ランキング用）
('総合', 'overall', 'ranking');
```

### repeat_intention の値
- `また行く` - また行きたい
- `わからん` - 微妙・どちらとも言えない
- `行かない` - もう行かない