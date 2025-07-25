# バックエンドAPI実装

ユーザーフィルタ機能のバックエンドAPI実装タスクです。

## 実装対象

### 1. ReviewController の拡張
**ファイル**: `backend/app/Http/Controllers/Api/ReviewController.php`

#### 修正箇所: `index()` メソッド
```php
public function index(Request $request)
{
    $query = Review::with(['user', 'shop', 'images'])
        ->where('status', 'approved');

    // 既存フィルタ
    if ($request->has('shop_id')) {
        $query->where('shop_id', $request->shop_id);
    }

    // 新規: ユーザーフィルタ追加
    if ($request->has('user_id')) {
        $query->where('user_id', $request->user_id);
    }

    // 既存のソート・ページネーション
    return ReviewResource::collection(
        $query->orderBy('created_at', 'desc')
              ->paginate(10)
    );
}
```

#### バリデーション追加
```php
$request->validate([
    'shop_id' => 'sometimes|exists:shops,id',
    'user_id' => 'sometimes|exists:users,id',  // 新規追加
]);
```

### 2. RankingController の拡張
**ファイル**: `backend/app/Http/Controllers/Api/RankingController.php`

#### 修正箇所: `index()` メソッド
```php
public function index(Request $request)
{
    $query = Ranking::with(['user', 'category', 'items.shop']);

    // 既存フィルタ
    if ($request->has('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    // 新規: ユーザーフィルタ追加
    if ($request->has('user_id')) {
        $query->where('user_id', $request->user_id);
    }

    // 既存のソート・ページネーション
    return RankingResource::collection(
        $query->orderBy('created_at', 'desc')
              ->paginate(10)
    );
}
```

### 3. User情報取得API追加
**新規エンドポイント**: `GET /api/users/{user}/info`

#### ルート追加
**ファイル**: `backend/routes/api.php`
```php
// Public routes に追加
Route::get('/users/{user}/info', [UserController::class, 'info']);
```

#### UserController 新規作成/修正
**ファイル**: `backend/app/Http/Controllers/Api/UserController.php`
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Get basic user info for public display
     */
    public function info(User $user)
    {
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'created_at' => $user->created_at,
            // プライベート情報は除外
        ]);
    }
}
```

## テスト実装

### 1. ReviewApiTest 拡張
**ファイル**: `backend/tests/Feature/ReviewApiTest.php`

```php
/** @test */
public function can_filter_reviews_by_user()
{
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop = Shop::factory()->create();
    
    // user1 のレビュー2件
    Review::factory(2)->create(['user_id' => $user1->id, 'shop_id' => $shop->id]);
    // user2 のレビュー1件
    Review::factory(1)->create(['user_id' => $user2->id, 'shop_id' => $shop->id]);

    $response = $this->getJson("/api/reviews?user_id={$user1->id}");

    $response->assertStatus(200)
             ->assertJsonCount(2, 'data');
}

/** @test */
public function returns_error_for_invalid_user_id()
{
    $response = $this->getJson('/api/reviews?user_id=99999');
    
    $response->assertStatus(422);
}
```

### 2. RankingApiTest 拡張
**ファイル**: `backend/tests/Feature/RankingApiTest.php`

```php
/** @test */
public function can_filter_rankings_by_user()
{
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $category = Category::factory()->create();
    
    // user1 のランキング2件
    Ranking::factory(2)->create(['user_id' => $user1->id, 'category_id' => $category->id]);
    // user2 のランキング1件
    Ranking::factory(1)->create(['user_id' => $user2->id, 'category_id' => $category->id]);

    $response = $this->getJson("/api/rankings?user_id={$user1->id}");

    $response->assertStatus(200)
             ->assertJsonCount(2, 'data');
}
```

### 3. UserApiTest 新規作成
**ファイル**: `backend/tests/Feature/UserApiTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_get_user_info()
    {
        $user = User::factory()->create(['name' => 'Test User']);

        $response = $this->getJson("/api/users/{$user->id}/info");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $user->id,
                     'name' => 'Test User',
                 ])
                 ->assertJsonMissing([
                     'email' => $user->email, // プライベート情報は除外
                 ]);
    }

    /** @test */
    public function returns_404_for_nonexistent_user()
    {
        $response = $this->getJson('/api/users/99999/info');

        $response->assertStatus(404);
    }
}
```

## パフォーマンス対策

### インデックス確認
既存のインデックス設定を確認:
```sql
-- reviews テーブル
SHOW INDEX FROM reviews WHERE Column_name = 'user_id';

-- rankings テーブル  
SHOW INDEX FROM rankings WHERE Column_name = 'user_id';
```

必要に応じてマイグレーション追加:
```php
Schema::table('reviews', function (Blueprint $table) {
    $table->index('user_id');
});
```

## 完了チェックリスト

- [ ] ReviewController の user_id フィルタ実装
- [ ] RankingController の user_id フィルタ実装
- [ ] UserController::info() メソッド実装
- [ ] ルート追加 (`/api/users/{user}/info`)
- [ ] バリデーション追加（両コントローラ）
- [ ] テスト実装・実行成功
- [ ] インデックス確認・最適化
- [ ] 既存テストが通ることを確認

## 注意事項

### セキュリティ
- User::info() では機密情報（email, role等）を返さない
- バリデーションで存在チェック必須
- レート制限は既存のミドルウェアを活用

### 後方互換性
- 既存のAPIエンドポイントの動作は変更しない
- user_id がない場合は従来通りの動作