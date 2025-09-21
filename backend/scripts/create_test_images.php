<?php
// テスト画像を作成するスクリプト

// ダミー画像を作成（100x100の画像）
function createTestImage($filename, $text) {
    $image = imagecreatetruecolor(100, 100);
    $bgColor = imagecolorallocate($image, rand(100, 255), rand(100, 255), rand(100, 255));
    $textColor = imagecolorallocate($image, 0, 0, 0);

    imagefilledrectangle($image, 0, 0, 100, 100, $bgColor);
    imagestring($image, 5, 10, 40, $text, $textColor);

    imagejpeg($image, $filename, 90);
    imagedestroy($image);

    echo "Created: $filename\n";
}

// ディレクトリ確認
$dirs = [
    'storage/app/public/images/shops/thumbnail',
    'storage/app/public/images/shops/small',
    'storage/app/public/images/shops/medium',
    'storage/app/public/images/shops/large',
    'storage/app/public/images/reviews/thumbnail',
    'storage/app/public/images/reviews/small',
    'storage/app/public/images/reviews/medium',
    'storage/app/public/images/reviews/large',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// ShopImage用のテスト画像を作成（3件）
for ($i = 1; $i <= 3; $i++) {
    $filename = "shop-test-$i-" . time() . ".jpg";

    // 各サイズの画像を作成
    createTestImage("storage/app/public/images/shops/thumbnail/$filename", "Shop$i-T");
    createTestImage("storage/app/public/images/shops/small/$filename", "Shop$i-S");
    createTestImage("storage/app/public/images/shops/medium/$filename", "Shop$i-M");
    createTestImage("storage/app/public/images/shops/large/$filename", "Shop$i-L");

    echo "Created shop test images: $filename\n";
}

// ReviewImage用のテスト画像を作成（3件）
for ($i = 1; $i <= 3; $i++) {
    $filename = "review-test-$i-" . time() . ".jpg";

    // 各サイズの画像を作成
    createTestImage("storage/app/public/images/reviews/thumbnail/$filename", "Rev$i-T");
    createTestImage("storage/app/public/images/reviews/small/$filename", "Rev$i-S");
    createTestImage("storage/app/public/images/reviews/medium/$filename", "Rev$i-M");
    createTestImage("storage/app/public/images/reviews/large/$filename", "Rev$i-L");

    echo "Created review test images: $filename\n";
}

echo "\nTest images created successfully!\n";