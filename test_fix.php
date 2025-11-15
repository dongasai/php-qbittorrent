<?php
require_once 'vendor/autoload.php';

use PhpQbittorrent\Model\UserInfo;

// 创建一个测试用户，设置创建时间为10天前
$createdTime = time() - (10 * 86400);
$user = new UserInfo(
    username: 'testuser',
    createdTime: $createdTime
);

// 测试 getAccountAge 方法
$accountAge = $user->getAccountAge();

echo "账户年龄: {$accountAge} 天\n";
echo "类型: " . gettype($accountAge) . "\n";

// 验证返回值是否为整数
if (is_int($accountAge)) {
    echo "✓ 返回值类型正确（整数）\n";
} else {
    echo "✗ 返回值类型错误\n";
}

// 测试空值情况
$userWithNullTime = new UserInfo(username: 'testuser2');
$nullAccountAge = $userWithNullTime->getAccountAge();

echo "\n空值测试结果: ";
var_dump($nullAccountAge);

if ($nullAccountAge === null) {
    echo "✓ 空值处理正确\n";
} else {
    echo "✗ 空值处理错误\n";
}