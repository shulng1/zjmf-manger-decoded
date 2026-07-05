<?php

namespace app\Service\Cache;

use think\facade\Cache;

/**
 * 统一缓存服务
 * 封装 ThinkPHP Cache，提供统一的缓存操作接口
 */
class CacheService
{
    private static $prefix = '';
    private static $defaultTTL = 3600;

    /**
     * 设置缓存前缀
     */
    public static function setPrefix(string $prefix): void
    {
        self::$prefix = $prefix;
    }

    /**
     * 设置默认过期时间（秒）
     */
    public static function setDefaultTTL(int $ttl): void
    {
        self::$defaultTTL = $ttl;
    }

    /**
     * 获取完整缓存 key
     */
    private static function getKey(string $key): string
    {
        return self::$prefix !== '' ? self::$prefix . $key : $key;
    }

    /**
     * 获取缓存
     * @param string $key 缓存 key
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $value = Cache::get(self::getKey($key));
        return $value !== null ? $value : $default;
    }

    /**
     * 获取缓存（json 解码为数组）
     */
    public static function getJson(string $key, $default = []): array
    {
        $value = Cache::get(self::getKey($key));
        if ($value === null) {
            return $default;
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $default;
    }

    /**
     * 设置缓存
     * @param string $key 缓存 key
     * @param mixed $value 缓存值
     * @param int $ttl 过期时间（秒），0 为永久
     * @return bool
     */
    public static function set(string $key, $value, int $ttl = 0): bool
    {
        $ttl = $ttl > 0 ? $ttl : self::$defaultTTL;
        return Cache::set(self::getKey($key), $value, $ttl);
    }

    /**
     * 设置缓存（数组自动 json 编码）
     */
    public static function setJson(string $key, array $value, int $ttl = 0): bool
    {
        return self::set($key, json_encode($value), $ttl);
    }

    /**
     * 删除缓存
     */
    public static function delete(string $key): bool
    {
        return Cache::delete(self::getKey($key));
    }

    /**
     * 批量删除缓存
     */
    public static function deleteKeys(array $keys): void
    {
        foreach ($keys as $key) {
            Cache::delete(self::getKey($key));
        }
    }

    /**
     * 检查缓存是否存在
     */
    public static function has(string $key): bool
    {
        return Cache::has(self::getKey($key));
    }

    /**
     * 清空所有缓存（谨慎使用）
     */
    public static function clear(): bool
    {
        return Cache::clear();
    }

    /**
     * 获取或设置缓存（不存在则执行回调并缓存结果）
     * @param string $key 缓存 key
     * @param callable $callback 回调函数，返回要缓存的值
     * @param int $ttl 过期时间（秒）
     * @return mixed
     */
    public static function remember(string $key, callable $callback, int $ttl = 0)
    {
        $value = self::get($key);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::set($key, $value, $ttl);
        return $value;
    }

    /**
     * 自增
     */
    public static function inc(string $key, int $step = 1): int
    {
        return Cache::inc(self::getKey($key), $step);
    }

    /**
     * 自减
     */
    public static function dec(string $key, int $step = 1): int
    {
        return Cache::dec(self::getKey($key), $step);
    }
}
