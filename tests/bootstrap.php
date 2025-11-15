<?php
declare(strict_types=1);

/**
 * PHPUnit 测试引导文件
 * 
 * 加载环境变量和执行其他测试初始化操作
 */

// 加载环境变量文件
function loadEnvFile(string $file): void
{
    if (!file_exists($file)) {
        return;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // 跳过注释行
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        // 解析环境变量
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // 移除引号（如果存在）
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            
            // 设置环境变量
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            
            // 如果还没有设置，也设置为 putenv
            if (getenv($key) === false) {
                putenv("{$key}={$value}");
            }
        }
    }
}

// 优先加载 .env.testing，如果不存在则加载 .env
if (file_exists(__DIR__ . '/../.env.testing')) {
    loadEnvFile(__DIR__ . '/../.env.testing');
} else{
    echo "缺少环境变量.";
}


// 设置默认的测试环境变量
if (!getenv('RUN_NETWORK_TESTS')) {
    putenv('RUN_NETWORK_TESTS=0');
    $_ENV['RUN_NETWORK_TESTS'] = '0';
    $_SERVER['RUN_NETWORK_TESTS'] = '0';
}