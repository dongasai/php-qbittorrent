<?php
declare(strict_types=1);

namespace PhpQbittorrent\Util;

use PhpQbittorrent\Exception\ValidationException;

/**
 * 验证助手
 *
 * 提供常用的数据验证方法
 */
final class ValidationHelper
{
    /**
     * 验证URL格式
     *
     * @param mixed $value 要验证的值
     * @param bool $requireScheme 是否要求协议
     * @return string 验证通过的URL
     * @throws ValidationException 验证失败时抛出异常
     */
    public static function url($value, bool $requireScheme = true): string
    {
        if (!is_string($value)) {
            throw ValidationException::invalidFormat('url', 'string', gettype($value));
        }

        if (empty($value)) {
            throw ValidationException::missingParameter('url');
        }

        if ($requireScheme && !preg_match('/^https?:\/\//', $value)) {
            $value = 'http://' . $value;
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            throw ValidationException::invalidParameter('url', 'URL格式无效', $value);
        }

        return $value;
    }

    /**
     * 验证邮箱格式
     *
     * @param mixed $value 要验证的值
     * @return string 验证通过的邮箱
     * @throws ValidationException 验证失败时抛出异常
     */
    public static function email($value): string
    {
        if (!is_string($value)) {
            throw ValidationException::invalidFormat('email', 'string', gettype($value));
        }

        if (empty($value)) {
            throw ValidationException::missingParameter('email');
        }

        $value = filter_var(trim($value), FILTER_SANITIZE_EMAIL);

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::invalidParameter('email', '邮箱格式无效', $value);
        }

        return $value;
    }

    /**
     * 验证字符串长度
     *
     * @param mixed $value 要验证的值
     * @param int $min 最小长度
     * @param int|null $max 最大长度
     * @param string $fieldName 字段名
     * @return string 验证通过的字符串
     * @throws ValidationException 验证失败时抛出异常
     */
    public static function stringLength($value, int $min = 0, ?int $max = null, string $fieldName = 'value'): string
    {
        if (!is_string($value)) {
            throw ValidationException::invalidFormat($fieldName, 'string', gettype($value));
        }

        $length = mb_strlen($value, 'UTF-8');

        if ($length < $min) {
            throw ValidationException::outOfRange($fieldName, $value, $min, $max);
        }

        if ($max !== null && $length > $max) {
            throw ValidationException::outOfRange($fieldName, $value, $min, $max);
        }

        return $value;
    }

    /**
     * 验证整数
     *
     * @param mixed $value 要验证的值
     * @param int|null $min 最小值
     * @param int|null $max 最大值
     * @param string $fieldName 字段名
     * @return int 验证通过的整数
     * @throws ValidationException 验证失败时抛出异常
     */
    public static function integer($value, ?int $min = null, ?int $max = null, string $fieldName = 'value'): int
    {
        if (!is_numeric($value)) {
            throw ValidationException::invalidFormat($fieldName, 'integer', gettype($value));
        }

        $intValue = (int) $value;

        if ((string) $intValue !== (string) $value) {
            throw ValidationException::invalidFormat($fieldName, 'integer', $value);
        }

        if ($min !== null && $intValue < $min) {
            throw ValidationException::outOfRange($fieldName, $intValue, $min, $max);
        }

        if ($max !== null && $intValue > $max) {
            throw ValidationException::outOfRange($fieldName, $intValue, $min, $max);
        }

        return $intValue;
    }

    /**
     * 验证浮点数
     *
     * @param mixed $value 要验证的值
     * @param float|null $min 最小值
     * @param float|null $max 最大值
     * @param string $fieldName 字段名
     * @return float 验证通过的浮点数
     * @throws ValidationException 验证失败时抛出异常
     */
    public static function float($value, ?float $min = null, ?float $max = null, string $fieldName = 'value'): float
    {
        if (!is_numeric($value)) {
            throw ValidationException::invalidFormat($fieldName, 'float', gettype($value));
        }

        $floatValue = (float) $value;

        if ($min !== null && $floatValue < $min) {
            throw ValidationException::outOfRange($fieldName, $floatValue, $min, $max);
        }

        if ($max !== null && $floatValue > $max) {
            throw ValidationException::outOfRange($fieldName, $floatValue, $min, $max);
        }

        return $floatValue;
    }

    /**
     * 验证布尔值
     *
     * @param mixed $value 要验证的值
     * @param string $fieldName 字段名
     * @return bool 验证通过的布尔值
     * @throws ValidationException 验证失败时抛出异常
     */
    public static function boolean($value, string $fieldName = 'value'): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower($value);
            if (in_array($value, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($value, ['false', '0', 'no', 'off'], true)) {
                return false;
            }
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        throw ValidationException::invalidFormat($fieldName, 'boolean', gettype($value));
    }

    /**
     * 验证枚举值
     *
     * @param mixed $value 要验证的值
     * @param array $allowedValues 允许的值列表
     * @param string $fieldName 字段名
     * @return mixed 验证通过的值
     * @throws ValidationException 验证失败时抛出异常
     */
    public static function enum($value, array $allowedValues, string $fieldName = 'value')
    {
        if (!in_array($value, $allowedValues, true)) {
            throw ValidationException::invalidEnumValue($fieldName, $value, $allowedValues);
        }

        return $value;
    }

    /**
     * 验证正则表达式
     *
     * @param mixed $value 要验证的值
     * @param string $pattern 正则表达式模式
     * @param string $fieldName 字段名
     * @return string 验证通过的字符串
     * @throws ValidationException 验证失败时抛出异常
     */
    public static function regex($value, string $pattern, string $fieldName = 'value'): string
    {
        if (!is_string($value)) {
            throw ValidationException::invalidFormat($fieldName, 'string', gettype($value));
        }

        if (!preg_match($pattern, $value)) {
            throw ValidationException::invalidParameter(
                $fieldName,
                '格式不符合要求',
                $value
            );
        }

        return $value;
    }

    /**
     * 验证IP地址
     *
     * @param mixed $value 要验证的值
     * @param bool $allowPrivate 是否允许私有IP
     * @param string $fieldName 字段名
     * @return string 验证通过的IP地址
     * @throws ValidationException 验证失败时抛出异常
     */
    public static function ip($value, bool $allowPrivate = true, string $fieldName = 'value'): string
    {
        if (!is_string($value)) {
            throw ValidationException::invalidFormat($fieldName, 'string', gettype($value));
        }

        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        if ($allowPrivate) {
            $flags = FILTER_FLAG_NO_RES_RANGE;
        }

        if (!filter_var($value, FILTER_VALIDATE_IP, $flags)) {
            throw ValidationException::invalidParameter($fieldName, 'IP地址格式无效', $value);
        }

        return $value;
    }

    /**
     * 验证文件路径
     *
     * @param mixed $value 要验证的值
     * @param bool $mustExist 是否必须存在
     * @param string $fieldName 字段名
     * @return string 验证通过的路径
     * @throws ValidationException 验证失败时抛出异常
     */
    public static function filePath($value, bool $mustExist = false, string $fieldName = 'path'): string
    {
        if (!is_string($value)) {
            throw ValidationException::invalidFormat($fieldName, 'string', gettype($value));
        }

        if (empty($value)) {
            throw ValidationException::missingParameter($fieldName);
        }

        // 检查路径格式
        if (preg_match('/["<>|?*]/', $value)) {
            throw ValidationException::invalidParameter($fieldName, '路径包含非法字符', $value);
        }

        if ($mustExist && !file_exists($value)) {
            throw ValidationException::invalidParameter($fieldName, '路径不存在', $value);
        }

        return $value;
    }

    /**
     * 验证数组
     *
     * @param mixed $value 要验证的值
     * @param int|null $minCount 最小元素数量
     * @param int|null $maxCount 最大元素数量
     * @param string $fieldName 字段名
     * @return array 验证通过的数组
     * @throws ValidationException 验证失败时抛出异常
     */
    public static function array($value, ?int $minCount = null, ?int $maxCount = null, string $fieldName = 'value'): array
    {
        if (!is_array($value)) {
            throw ValidationException::invalidFormat($fieldName, 'array', gettype($value));
        }

        $count = count($value);

        if ($minCount !== null && $count < $minCount) {
            throw ValidationException::outOfRange("{$fieldName}_count", $count, $minCount, $maxCount);
        }

        if ($maxCount !== null && $count > $maxCount) {
            throw ValidationException::outOfRange("{$fieldName}_count", $count, $minCount, $maxCount);
        }

        return $value;
    }

    /**
     * 验证必需参数
     *
     * @param array $data 数据数组
     * @param array $requiredFields 必需字段列表
     * @throws ValidationException 缺少必需参数时抛出异常
     */
    public static function requiredFields(array $data, array $requiredFields): void
    {
        $missing = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw ValidationException::invalidConfig([
                'missing_required_fields' => $missing
            ]);
        }
    }

    /**
     * 批量验证
     *
     * @param array $data 要验证的数据
     * @param array $rules 验证规则
     * @return array 验证后的数据
     * @throws ValidationException 验证失败时抛出异常
     */
    public static function batch(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $rule) {
            if (!is_array($rule)) {
                $rule = ['type' => $rule];
            }

            $type = $rule['type'] ?? 'string';
            $required = $rule['required'] ?? false;
            $default = $rule['default'] ?? null;

            // 检查必需字段
            if ($required && !array_key_exists($field, $data)) {
                $errors[$field] = "字段 '{$field}' 是必需的";
                continue;
            }

            // 处理不存在但非必需的字段
            if (!array_key_exists($field, $data)) {
                if ($default !== null) {
                    $validated[$field] = $default;
                }
                continue;
            }

            $value = $data[$field];

            try {
                // 根据类型进行验证
                switch ($type) {
                    case 'string':
                        $validated[$field] = self::stringLength(
                            $value,
                            $rule['min'] ?? 0,
                            $rule['max'] ?? null,
                            $field
                        );
                        break;

                    case 'integer':
                        $validated[$field] = self::integer(
                            $value,
                            $rule['min'] ?? null,
                            $rule['max'] ?? null,
                            $field
                        );
                        break;

                    case 'float':
                        $validated[$field] = self::float(
                            $value,
                            $rule['min'] ?? null,
                            $rule['max'] ?? null,
                            $field
                        );
                        break;

                    case 'boolean':
                        $validated[$field] = self::boolean($value, $field);
                        break;

                    case 'email':
                        $validated[$field] = self::email($value);
                        break;

                    case 'url':
                        $validated[$field] = self::url($value, $rule['require_scheme'] ?? true);
                        break;

                    case 'enum':
                        $validated[$field] = self::enum($value, $rule['values'] ?? [], $field);
                        break;

                    case 'array':
                        $validated[$field] = self::array(
                            $value,
                            $rule['min_count'] ?? null,
                            $rule['max_count'] ?? null,
                            $field
                        );
                        break;

                    case 'regex':
                        $validated[$field] = self::regex($value, $rule['pattern'], $field);
                        break;

                    default:
                        $validated[$field] = $value;
                }

                // 自定义验证规则
                if (isset($rule['custom']) && is_callable($rule['custom'])) {
                    $validated[$field] = call_user_func($rule['custom'], $validated[$field]);
                }

            } catch (ValidationException $e) {
                $errors[$field] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(
                '批量验证失败',
                'BATCH_VALIDATION_ERROR',
                $errors
            );
        }

        return $validated;
    }
}