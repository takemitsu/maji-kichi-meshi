---
name: API テスト要件
description: API実装時の厳格なテスト要件。API作成、エンドポイント追加、認証、バリデーション、テスト実装時に使用。
---

# API テスト要件（t-wada流）

このSkillは、API実装時に**必ず実装すべきテスト**を定義します。

---

## ⚠️ 絶対ルール

**テストが全て実装・成功するまで「実装完了」とみなさない**

以下のテストケースを**全て実装**してから、初めて「完了」と報告する。

---

## 必須テストケース

### 1. 認証・認可テスト（Auth系）

**よくあるバグ**: 未認証でもアクセスできる、他人のデータが見える

```php
// ✅ 必須テスト

test('未認証でアクセス → 401', function () {
    $response = $this->getJson('/api/endpoint');
    $response->assertStatus(401);
});

test('認証済みでアクセス → 200', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->getJson('/api/endpoint');
    $response->assertStatus(200);
});

test('他人のリソースにアクセス → 403', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson("/api/users/{$other->id}/private-data");
    $response->assertStatus(403);
});

test('トークン期限切れ → 401', function () {
    // JWT期限切れのシミュレーション
    $expiredToken = 'expired.jwt.token';
    $response = $this->withHeader('Authorization', "Bearer {$expiredToken}")
        ->getJson('/api/endpoint');
    $response->assertStatus(401);
});

test('不正なトークン → 401', function () {
    $response = $this->withHeader('Authorization', 'Bearer invalid-token')
        ->getJson('/api/endpoint');
    $response->assertStatus(401);
});
```

---

### 2. バリデーションテスト（数字系・境界値）

**よくあるバグ**: 0や負数、NULLでエラー

#### 数値フィールド（例: rating, age, price）

```php
// ✅ 必須テスト

test('正常値（範囲内）', function () {
    // 例: rating 1-5
    foreach ([1, 2, 3, 4, 5] as $rating) {
        $response = $this->postJson('/api/reviews', [
            'rating' => $rating,
            'comment' => 'テスト',
        ]);
        $response->assertStatus(201);
    }
});

test('境界値: 0 → 422', function () {
    $response = $this->postJson('/api/reviews', ['rating' => 0]);
    $response->assertStatus(422)
        ->assertJsonValidationErrors('rating');
});

test('境界値: 最大値+1 → 422', function () {
    // rating 上限が5の場合
    $response = $this->postJson('/api/reviews', ['rating' => 6]);
    $response->assertStatus(422)
        ->assertJsonValidationErrors('rating');
});

test('負数 → 422', function () {
    $response = $this->postJson('/api/reviews', ['rating' => -1]);
    $response->assertStatus(422)
        ->assertJsonValidationErrors('rating');
});

test('NULL → 422', function () {
    $response = $this->postJson('/api/reviews', ['rating' => null]);
    $response->assertStatus(422)
        ->assertJsonValidationErrors('rating');
});

test('文字列（型違い）→ 422', function () {
    $response = $this->postJson('/api/reviews', ['rating' => 'abc']);
    $response->assertStatus(422)
        ->assertJsonValidationErrors('rating');
});

test('小数点 → 422', function () {
    // integer フィールドの場合
    $response = $this->postJson('/api/reviews', ['rating' => 3.5]);
    $response->assertStatus(422)
        ->assertJsonValidationErrors('rating');
});

test('INT境界値: PHP_INT_MAX', function () {
    $response = $this->postJson('/api/endpoint', ['count' => PHP_INT_MAX]);
    // 仕様に応じて 201 or 422
    $response->assertStatus(422);
});
```

#### ID・外部キー（例: user_id, shop_id）

```php
test('存在するID → 成功', function () {
    $shop = Shop::factory()->create();
    $response = $this->postJson('/api/reviews', ['shop_id' => $shop->id]);
    $response->assertStatus(201);
});

test('存在しないID → 422', function () {
    $response = $this->postJson('/api/reviews', ['shop_id' => 99999]);
    $response->assertStatus(422)
        ->assertJsonValidationErrors('shop_id');
});

test('IDが0 → 422', function () {
    $response = $this->postJson('/api/reviews', ['shop_id' => 0]);
    $response->assertStatus(422);
});

test('IDが負数 → 422', function () {
    $response = $this->postJson('/api/reviews', ['shop_id' => -1]);
    $response->assertStatus(422);
});
```

---

### 3. 正常系・異常系

```php
// ✅ 必須テスト

test('正常系: ハッピーパス', function () {
    $response = $this->postJson('/api/endpoint', [
        'field1' => 'valid_value',
        'field2' => 123,
    ]);
    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'field1', 'field2']]);
});

test('異常系: 必須パラメータ不足 → 422', function () {
    $response = $this->postJson('/api/endpoint', []);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['field1', 'field2']);
});

test('異常系: 存在しないリソース → 404', function () {
    $response = $this->getJson('/api/resources/99999');
    $response->assertStatus(404);
});

test('異常系: 重複データ → 422', function () {
    // 例: unique制約違反
    Shop::factory()->create(['name' => '重複店舗']);
    $response = $this->postJson('/api/shops', ['name' => '重複店舗']);
    $response->assertStatus(422)
        ->assertJsonValidationErrors('name');
});
```

---

### 4. エッジケース

```php
// ✅ 必須テスト

test('空配列', function () {
    $response = $this->postJson('/api/endpoint', ['items' => []]);
    // 仕様に応じて 201 or 422
    $response->assertStatus(422);
});

test('大量データ（ページング）', function () {
    Shop::factory()->count(100)->create();
    $response = $this->getJson('/api/shops?per_page=10');
    $response->assertStatus(200)
        ->assertJsonCount(10, 'data');
});

test('特殊文字（XSS対策）', function () {
    $response = $this->postJson('/api/reviews', [
        'comment' => '<script>alert("XSS")</script>',
    ]);
    $response->assertStatus(201);

    // レスポンスでエスケープされているか確認
    $response->assertJsonPath('data.comment',
        htmlspecialchars('<script>alert("XSS")</script>', ENT_QUOTES, 'UTF-8')
    );
});

test('長文（最大文字数）', function () {
    $longText = str_repeat('あ', 1001); // 1000文字制限の場合
    $response = $this->postJson('/api/reviews', ['comment' => $longText]);
    $response->assertStatus(422)
        ->assertJsonValidationErrors('comment');
});
```

---

## プロジェクト固有のテストパターン

### JWT認証（マジキチメシ）

```php
test('JWT: 有効なトークン → 200', function () {
    $user = User::factory()->create();
    $token = auth()->login($user);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/user/profile');
    $response->assertStatus(200);
});

test('JWT: トークンなし → 401', function () {
    $response = $this->getJson('/api/user/profile');
    $response->assertStatus(401);
});

test('JWT: 期限切れトークン → 401', function () {
    // JWT_TTL を過ぎたトークンのシミュレーション
    // 実装方法: Carbon::setTestNow() で時間を進める
    $user = User::factory()->create();
    $token = auth()->login($user);

    Carbon::setTestNow(now()->addMinutes(config('jwt.ttl') + 1));

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/user/profile');
    $response->assertStatus(401);

    Carbon::setTestNow(); // リセット
});
```

### MySQL/SQLite 互換性

```php
test('ソート・集計の互換性', function () {
    // MySQL と SQLite で動作が異なる可能性のあるクエリ
    Shop::factory()->count(10)->create();

    $response = $this->getJson('/api/shops?sort=rating');
    $response->assertStatus(200)
        ->assertJsonStructure(['data' => [['id', 'name', 'rating']]]);
});
```

---

## テスト完了基準チェックリスト

API実装時は、以下を**全て**実施してから「完了」と報告する:

### 認証・認可
- [ ] 未認証 → 401
- [ ] 認証済み → 200/201
- [ ] 他人のリソースアクセス → 403
- [ ] トークン期限切れ → 401
- [ ] 不正なトークン → 401

### 数値フィールド（該当する場合）
- [ ] 正常値（範囲内）
- [ ] 境界値: 0
- [ ] 境界値: 最大値+1
- [ ] 負数
- [ ] NULL
- [ ] 文字列（型違い）
- [ ] 小数点（integerの場合）

### ID・外部キー（該当する場合）
- [ ] 存在するID → 成功
- [ ] 存在しないID → 422
- [ ] 0, 負数 → 422

### 正常系・異常系
- [ ] ハッピーパス
- [ ] 必須パラメータ不足 → 422
- [ ] 存在しないリソース → 404
- [ ] 重複データ → 422

### エッジケース
- [ ] 空配列
- [ ] 大量データ（ページング）
- [ ] 特殊文字（XSS対策）
- [ ] 長文（最大文字数）

### プロジェクト固有
- [ ] JWT認証パターン
- [ ] MySQL/SQLite 互換性（該当する場合）

### 実行確認
- [ ] `php artisan test --parallel` 全成功

---

## よくあるテスト不足パターン

### ❌ NG: 正常系のみ

```php
// これだけでは不十分
test('ユーザー取得', function () {
    $response = $this->getJson('/api/users/1');
    $response->assertStatus(200);
});
```

### ✅ OK: 認証・異常系も網羅

```php
test('ユーザー取得: 未認証 → 401', function () { ... });
test('ユーザー取得: 認証済み → 200', function () { ... });
test('ユーザー取得: 存在しないID → 404', function () { ... });
test('ユーザー取得: 他人のプライベートデータ → 403', function () { ... });
```

---

## まとめ

### テスト実装の原則

1. **認証テストは必須** - 未認証・権限なしをテスト
2. **境界値テストは必須** - 0・負数・NULL・型違い
3. **異常系は正常系と同じくらい重要** - 404・422・403
4. **エッジケースも忘れずに** - 空配列・特殊文字・大量データ

### 完了基準

**「テストが全て実装・成功」= 上記チェックリスト全て完了**

テスト未実装での「実装完了」報告は厳禁。
